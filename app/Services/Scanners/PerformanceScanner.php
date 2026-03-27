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
                'description' => sprintf('Time To First Byte: %.0f ms (measured from our scanner server) — excellent.', $ttfb * 1000),
            ];
        } elseif ($ttfb !== null && $ttfb < 1.8) {
            $score += 15;
            $checks[] = [
                'id'             => 'perf_ttfb',
                'label'          => 'Fast server response time (TTFB)',
                'status'         => 'warn',
                'description'    => sprintf('Time To First Byte: %.0f ms (measured from our scanner server) — acceptable but aim for under 800 ms.', $ttfb * 1000),
                'recommendation' => 'Improve TTFB via server-side caching, a CDN, or optimizing database queries.',
            ];
        } elseif ($ttfb !== null && $ttfb < 3.0) {
            $score += 5;
            $checks[] = [
                'id'             => 'perf_ttfb',
                'label'          => 'Fast server response time (TTFB)',
                'status'         => 'warn',
                'description'    => sprintf('Time To First Byte: %.0f ms (measured from our scanner server) — slow. Google recommends under 800 ms.', $ttfb * 1000),
                'recommendation' => 'Consider upgrading your hosting, enabling server-side caching (OPcache, Redis), or moving to a CDN.',
            ];
        } elseif ($ttfb !== null) {
            $checks[] = [
                'id'             => 'perf_ttfb',
                'label'          => 'Fast server response time (TTFB)',
                'status'         => 'fail',
                'description'    => sprintf('Time To First Byte: %.0f ms (measured from our scanner server) — very slow. This will hurt SEO and user experience.', $ttfb * 1000),
                'recommendation' => 'A TTFB above 3 seconds indicates a serious server performance problem. Investigate caching, hosting, and database query performance.',
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
        // Must use GET (not HEAD) — most servers only compress responses that have a body.
        // We limit the download to the first 4 KB via Range to keep it fast.
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_RANGE          => '0-4095',
            CURLOPT_HTTPHEADER     => ['Accept-Encoding: gzip, deflate, br'],
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

        // Accept 200 (normal) or 204 (empty but valid)
        return in_array($code, [200, 204]);
    }

    private function checkSitemap(string $host): bool
    {
        // First: check robots.txt for an explicit Sitemap: directive
        $robotsContent = $this->fetchRobotsTxtContent($host);
        if ($robotsContent && preg_match('/^Sitemap:\s*(https?:\/\/\S+)/mi', $robotsContent, $m)) {
            if ($this->urlReturns200(trim($m[1]))) {
                return true;
            }
        }

        // Fallback: check common sitemap locations
        $urls = [
            "https://{$host}/sitemap.xml",
            "https://{$host}/sitemap_index.xml",
            "https://{$host}/wp-sitemap.xml",   // WordPress 5.5+ default
        ];

        foreach ($urls as $url) {
            if ($this->urlReturns200($url)) {
                return true;
            }
        }

        return false;
    }

    private function fetchRobotsTxtContent(string $host): ?string
    {
        $ch = curl_init("https://{$host}/robots.txt");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_RANGE          => '0-8191',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code === 200 && $body) ? $body : null;
    }

    private function urlReturns200(string $url): bool
    {
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

        return $code === 200;
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
