<?php

namespace App\Services\Scanners;

class PerformanceScanner
{
    private const TIMEOUT = 6;

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        // --- Check 1: Time To First Byte (TTFB) ---
        // We measure TTFB (server processing time), not full download time.
        // Measured from our scanner server — indicative, not absolute.
        $maxScore += 25;
        $ttfb = $this->safe(fn() => $this->measureTtfb($host), null);
        if ($ttfb !== null && $ttfb < 0.8) {
            $score += 25;
            $checks[] = [
                'id'          => 'perf_ttfb',
                'label'       => 'Fast server response time (TTFB)',
                'status'      => 'pass',
                'description' => sprintf('Time To First Byte: %.0f ms — server is responding quickly.', $ttfb * 1000),
            ];
        } elseif ($ttfb !== null && $ttfb < 2.0) {
            $score += 12;
            $checks[] = [
                'id'             => 'perf_ttfb',
                'label'          => 'Fast server response time (TTFB)',
                'status'         => 'warn',
                'description'    => sprintf('Time To First Byte: %.0f ms — aim for under 800ms.', $ttfb * 1000),
                'recommendation' => 'Improve TTFB via server-side caching, a CDN, or optimizing database queries.',
            ];
        } elseif ($ttfb !== null) {
            $checks[] = [
                'id'             => 'perf_ttfb',
                'label'          => 'Fast server response time (TTFB)',
                'status'         => 'fail',
                'description'    => sprintf('Time To First Byte: %.0f ms — this is very slow.', $ttfb * 1000),
                'recommendation' => 'A TTFB above 2 seconds indicates a server performance problem. Investigate caching, hosting, and query performance.',
            ];
        } else {
            $checks[] = [
                'id'          => 'perf_ttfb',
                'label'       => 'Fast server response time (TTFB)',
                'status'      => 'fail',
                'description' => 'Could not measure server response time.',
            ];
        }

        // --- Check 2: Response compression (gzip / Brotli) ---
        $maxScore += 25;
        $compression = $this->safe(fn() => $this->checkCompression($host), ['enabled' => false, 'encoding' => null]);
        if ($compression['enabled']) {
            $score += 25;
            $checks[] = [
                'id'          => 'perf_compression',
                'label'       => 'Response compression enabled',
                'status'      => 'pass',
                'description' => "Compression is enabled ({$compression['encoding']}) — reduces transfer size and speeds up page loads.",
            ];
        } else {
            $checks[] = [
                'id'             => 'perf_compression',
                'label'          => 'Response compression enabled',
                'status'         => 'fail',
                'description'    => 'No gzip or Brotli compression detected.',
                'recommendation' => 'Enable gzip or Brotli compression on your web server. This typically reduces HTML/CSS/JS size by 60-80%.',
            ];
        }

        // --- Check 3: robots.txt ---
        $maxScore += 25;
        $robots = $this->safe(fn() => $this->checkRobotsTxt($host), false);
        if ($robots) {
            $score += 25;
            $checks[] = [
                'id'          => 'perf_robots',
                'label'       => 'robots.txt present',
                'status'      => 'pass',
                'description' => 'A robots.txt file was found and is accessible.',
            ];
        } else {
            $checks[] = [
                'id'             => 'perf_robots',
                'label'          => 'robots.txt present',
                'status'         => 'warn',
                'description'    => 'No robots.txt file found.',
                'recommendation' => 'Create a robots.txt file to guide search engine crawlers and prevent indexing of sensitive paths.',
            ];
        }

        // --- Check 4: XML sitemap ---
        $maxScore += 25;
        $sitemap = $this->safe(fn() => $this->checkSitemap($host), false);
        if ($sitemap) {
            $score += 25;
            $checks[] = [
                'id'          => 'perf_sitemap',
                'label'       => 'XML sitemap present',
                'status'      => 'pass',
                'description' => 'An XML sitemap was found — helps search engines discover and index your pages.',
            ];
        } else {
            $checks[] = [
                'id'             => 'perf_sitemap',
                'label'          => 'XML sitemap present',
                'status'         => 'warn',
                'description'    => 'No sitemap.xml found at common locations (/sitemap.xml, /sitemap_index.xml).',
                'recommendation' => 'Create and submit an XML sitemap to Google Search Console to improve search indexing.',
            ];
        }

        return [
            'category' => 'Performance & SEO',
            'icon'     => 'bolt',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    /**
     * Measure Time To First Byte (TTFB).
     * CURLINFO_STARTTRANSFER_TIME = time from start until the first byte is received.
     */
    private function measureTtfb(string $host): ?float
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => false,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        curl_exec($ch);
        $errno = curl_errno($ch);
        $ttfb  = curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME);
        curl_close($ch);

        if ($errno || $ttfb <= 0) {
            return null;
        }

        return (float) $ttfb;
    }

    private function checkCompression(string $host): array
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);

        $encoding = null;
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$encoding) {
            if (preg_match('/^HTTP\//i', $header)) {
                $encoding = null; // reset on new response
            } elseif (preg_match('/^content-encoding:\s*(.+)/i', $header, $m)) {
                $encoding = trim($m[1]);
            }
            return strlen($header);
        });

        // Send Accept-Encoding so the server knows we support compression
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Encoding: gzip, deflate, br']);
        curl_exec($ch);
        curl_close($ch);

        return ['enabled' => $encoding !== null, 'encoding' => $encoding];
    }

    private function checkRobotsTxt(string $host): bool
    {
        $ch = curl_init("https://{$host}/robots.txt");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code === 200;
    }

    private function checkSitemap(string $host): bool
    {
        $urls = [
            "https://{$host}/sitemap.xml",
            "https://{$host}/sitemap_index.xml",
        ];

        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY         => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200) {
                return true;
            }
        }

        return false;
    }

    private function safe(callable $fn, mixed $default): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return $default;
        }
    }
}
