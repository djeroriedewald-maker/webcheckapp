<?php

namespace App\Services\Scanners;

class BrokenLinksScanner
{
    use HasSafeCall;
    private const TIMEOUT   = 5;
    private const MAX_LINKS = 15;
    private const MAX_HTML  = 524288; // 512 KB

    public function scan(string $host): array
    {
        $checks = [];

        $html = $this->safe(fn() => $this->fetchPage($host), '');
        if (! $html) {
            return [
                'category' => 'Broken Links',
                'icon'     => 'link',
                'score'    => null,
                'checks'   => [[
                    'id'          => 'broken_links_fetch',
                    'label'       => 'Page fetch',
                    'status'      => 'warn',
                    'description' => 'Could not fetch the page to check for broken links.',
                ]],
            ];
        }

        $links = $this->extractInternalLinks($html, $host);
        if (empty($links)) {
            return [
                'category' => 'Broken Links',
                'icon'     => 'link',
                'score'    => null,
                'checks'   => [[
                    'id'          => 'broken_links_none',
                    'label'       => 'Internal links',
                    'status'      => 'pass',
                    'description' => 'No checkable internal links found on the homepage.',
                ]],
            ];
        }

        $toCheck = array_slice($links, 0, self::MAX_LINKS);
        $broken  = [];
        $ok      = 0;

        foreach ($toCheck as $url) {
            $code = $this->safe(fn() => $this->checkUrl($url), 0);
            if ($code >= 400) {
                $broken[] = ['url' => $url, 'code' => $code];
            } elseif ($code >= 200) {
                $ok++;
            }
        }

        $checked = count($toCheck);

        if (empty($broken)) {
            $checks[] = [
                'id'          => 'broken_links_result',
                'label'       => 'Broken internal links',
                'status'      => 'pass',
                'description' => "All {$checked} sampled internal links returned successful responses.",
            ];
        } else {
            foreach ($broken as $b) {
                $path    = parse_url($b['url'], PHP_URL_PATH) ?? $b['url'];
                $checks[] = [
                    'id'             => 'broken_link_' . md5($b['url']),
                    'label'          => "Broken link — HTTP {$b['code']}",
                    'status'         => 'warn',
                    'description'    => "HTTP {$b['code']} response at: {$path}",
                    'recommendation' => 'Fix or remove this broken link to improve user experience and SEO.',
                ];
            }
            if ($ok > 0) {
                $checks[] = [
                    'id'          => 'broken_links_ok',
                    'label'       => 'Working links',
                    'status'      => 'pass',
                    'description' => "{$ok} of {$checked} sampled internal links are working correctly.",
                ];
            }
        }

        return [
            'category' => 'Broken Links',
            'icon'     => 'link',
            'score'    => null,
            'checks'   => $checks,
        ];
    }

    private function extractInternalLinks(string $html, string $host): array
    {
        $links = [];
        if (! preg_match_all('/<a\b[^>]+href=["\']([^"\'#?][^"\']*)["\'][^>]*>/i', $html, $m)) {
            return [];
        }

        foreach ($m[1] as $href) {
            if (str_starts_with($href, '//')) {
                $href = 'https:' . $href;
            } elseif (str_starts_with($href, '/')) {
                $href = "https://{$host}{$href}";
            } elseif (! str_starts_with($href, 'http')) {
                continue;
            }

            $parsed = parse_url($href);
            if (! isset($parsed['host'])) {
                continue;
            }

            // Only same-host links
            $linkHost = strtolower($parsed['host']);
            $baseHost = strtolower($host);
            if ($linkHost !== $baseHost
                && $linkHost !== 'www.' . $baseHost
                && 'www.' . $linkHost !== $baseHost) {
                continue;
            }

            $normalized = strtok($href, '?#');
            if (! in_array($normalized, $links, true)) {
                $links[] = $normalized;
            }
        }

        return $links;
    }

    private function fetchPage(string $host): string
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT * 2,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_RANGE          => '0-' . (self::MAX_HTML - 1),
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return $body ?: '';
    }

    private function checkUrl(string $url): int
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
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }

}
