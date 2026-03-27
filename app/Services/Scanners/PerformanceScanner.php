<?php

namespace App\Services\Scanners;

class PerformanceScanner
{
    private function safe(callable $fn, mixed $default): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return $default;
        }
    }

    public function scan(string $host): array
    {
        $checks = [];
        $score = 0;
        $maxScore = 0;

        // Response time
        $maxScore += 25;
        $responseTime = $this->safe(fn() => $this->measureResponseTime($host), null);
        if ($responseTime !== null && $responseTime < 1.0) {
            $score += 25;
            $checks[] = [
                'id'          => 'perf_response',
                'label'       => 'Fast response time',
                'status'      => 'pass',
                'description' => sprintf('Server responded in %.2f seconds.', $responseTime),
            ];
        } elseif ($responseTime !== null && $responseTime < 3.0) {
            $score += 12;
            $checks[] = [
                'id'          => 'perf_response',
                'label'       => 'Fast response time',
                'status'      => 'warn',
                'description' => sprintf('Server responded in %.2f seconds (aim for under 1s).', $responseTime),
                'recommendation' => 'Optimize server response time through caching, CDN, or server upgrades.',
            ];
        } else {
            $checks[] = [
                'id'          => 'perf_response',
                'label'       => 'Fast response time',
                'status'      => 'fail',
                'description' => $responseTime !== null
                    ? sprintf('Slow response time: %.2f seconds.', $responseTime)
                    : 'Could not measure response time.',
                'recommendation' => 'Response time exceeds 3 seconds. Investigate server performance.',
            ];
        }

        // GZIP / Brotli compression
        $maxScore += 25;
        $compression = $this->safe(fn() => $this->checkCompression($host), ['enabled' => false, 'encoding' => null]);
        if ($compression['enabled']) {
            $score += 25;
            $checks[] = [
                'id'          => 'perf_compression',
                'label'       => 'Response compression enabled',
                'status'      => 'pass',
                'description' => "Compression enabled ({$compression['encoding']}).",
            ];
        } else {
            $checks[] = [
                'id'          => 'perf_compression',
                'label'       => 'Response compression enabled',
                'status'      => 'fail',
                'description' => 'No compression (gzip/brotli) detected.',
                'recommendation' => 'Enable gzip or Brotli compression on your web server to reduce transfer sizes.',
            ];
        }

        // robots.txt
        $maxScore += 25;
        $robots = $this->safe(fn() => $this->checkRobotsTxt($host), false);
        if ($robots) {
            $score += 25;
            $checks[] = [
                'id'          => 'perf_robots',
                'label'       => 'robots.txt present',
                'status'      => 'pass',
                'description' => 'A robots.txt file was found.',
            ];
        } else {
            $checks[] = [
                'id'          => 'perf_robots',
                'label'       => 'robots.txt present',
                'status'      => 'warn',
                'description' => 'No robots.txt file found.',
                'recommendation' => 'Create a robots.txt file to guide search engine crawlers.',
            ];
        }

        // sitemap.xml
        $maxScore += 25;
        $sitemap = $this->safe(fn() => $this->checkSitemap($host), false);
        if ($sitemap) {
            $score += 25;
            $checks[] = [
                'id'          => 'perf_sitemap',
                'label'       => 'XML sitemap present',
                'status'      => 'pass',
                'description' => 'An XML sitemap was found.',
            ];
        } else {
            $checks[] = [
                'id'          => 'perf_sitemap',
                'label'       => 'XML sitemap present',
                'status'      => 'warn',
                'description' => 'No sitemap.xml found.',
                'recommendation' => 'Create and submit an XML sitemap to help search engines index your content.',
            ];
        }

        return [
            'category' => 'Performance & SEO',
            'icon'     => 'bolt',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function measureResponseTime(string $host): ?float
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 8,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 3,
        ]);
        curl_exec($ch);
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);
        curl_close($ch);

        return $error ? null : (float) $time;
    }

    private function checkCompression(string $host): array
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT         => 5,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPHEADER      => ['Accept-Encoding: gzip, deflate, br'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (preg_match('/content-encoding:\s*(.+)/i', $response, $matches)) {
            return ['enabled' => true, 'encoding' => trim($matches[1])];
        }

        return ['enabled' => false, 'encoding' => null];
    }

    private function checkRobotsTxt(string $host): bool
    {
        $ch = curl_init("https://{$host}/robots.txt");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 5,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_NOBODY          => true,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code === 200;
    }

    private function checkSitemap(string $host): bool
    {
        foreach (["https://{$host}/sitemap.xml", "https://{$host}/sitemap_index.xml"] as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_TIMEOUT         => 5,
                CURLOPT_CONNECTTIMEOUT  => 5,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_NOBODY          => true,
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
}
