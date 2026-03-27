<?php

namespace App\Services\Scanners;

class ContentScanner
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

        $html = $this->safe(fn() => $this->fetchHtml($host), '');

        // Mixed content
        $maxScore += 30;
        $mixedContent = $this->safe(fn() => $this->checkMixedContent($html, $host), ['found' => false, 'count' => 0]);
        if (! $mixedContent['found']) {
            $score += 30;
            $checks[] = [
                'id'          => 'content_mixed',
                'label'       => 'No mixed content',
                'status'      => 'pass',
                'description' => 'No insecure (HTTP) resources found on the HTTPS page.',
            ];
        } else {
            $checks[] = [
                'id'          => 'content_mixed',
                'label'       => 'No mixed content',
                'status'      => 'fail',
                'description' => "Found {$mixedContent['count']} insecure resource(s) loaded over HTTP.",
                'recommendation' => 'Update all resource URLs to use HTTPS to prevent mixed content warnings.',
            ];
        }

        // Admin panel exposure
        $maxScore += 25;
        $adminExposed = $this->safe(fn() => $this->checkAdminExposure($host), ['exposed' => false, 'path' => null]);
        if (! $adminExposed['exposed']) {
            $score += 25;
            $checks[] = [
                'id'          => 'content_admin',
                'label'       => 'Admin panel not exposed',
                'status'      => 'pass',
                'description' => 'No publicly accessible admin panel found at common paths.',
            ];
        } else {
            $checks[] = [
                'id'          => 'content_admin',
                'label'       => 'Admin panel not exposed',
                'status'      => 'warn',
                'description' => "Admin panel accessible at: {$adminExposed['path']}.",
                'recommendation' => 'Restrict access to admin areas via IP whitelisting or authentication.',
            ];
        }

        // WordPress detection
        $maxScore += 20;
        $wordpress = $this->safe(fn() => $this->detectWordPress($html, $host), ['detected' => false]);
        if ($wordpress['detected']) {
            if ($wordpress['version_exposed']) {
                $checks[] = [
                    'id'          => 'content_wp',
                    'label'       => 'WordPress version not exposed',
                    'status'      => 'warn',
                    'description' => "WordPress detected. Version {$wordpress['version']} may be exposed in meta tags or script URLs.",
                    'recommendation' => 'Remove the WordPress version from meta tags and script/style URLs.',
                ];
            } else {
                $score += 20;
                $checks[] = [
                    'id'          => 'content_wp',
                    'label'       => 'WordPress version not exposed',
                    'status'      => 'pass',
                    'description' => 'WordPress detected but version is not publicly exposed.',
                ];
            }
        } else {
            $score += 20;
            $checks[] = [
                'id'          => 'content_wp',
                'label'       => 'CMS version not exposed',
                'status'      => 'pass',
                'description' => 'No CMS version information found in page source.',
            ];
        }

        // Directory listing
        $maxScore += 25;
        $dirListing = $this->safe(fn() => $this->checkDirectoryListing($host), false);
        if (! $dirListing) {
            $score += 25;
            $checks[] = [
                'id'          => 'content_dirlisting',
                'label'       => 'Directory listing disabled',
                'status'      => 'pass',
                'description' => 'Directory listing is not enabled on the web server.',
            ];
        } else {
            $checks[] = [
                'id'          => 'content_dirlisting',
                'label'       => 'Directory listing disabled',
                'status'      => 'fail',
                'description' => 'Directory listing appears to be enabled.',
                'recommendation' => 'Disable directory listing in your web server configuration (Options -Indexes for Apache).',
            ];
        }

        return [
            'category' => 'Content & CMS',
            'icon'     => 'globe-alt',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function fetchHtml(string $host): string
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 8,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 3,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        return $html ?: '';
    }

    private function checkMixedContent(string $html, string $host): array
    {
        $count = preg_match_all(
            '/(?:src|href|action)=["\']http:\/\/[^"\']+["\']/i',
            $html,
            $matches
        );

        return ['found' => $count > 0, 'count' => $count];
    }

    private function checkAdminExposure(string $host): array
    {
        $paths = ['/wp-admin', '/admin', '/administrator', '/wp-login.php', '/login'];

        foreach ($paths as $path) {
            $ch = curl_init("https://{$host}{$path}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_NOBODY          => true,
                CURLOPT_TIMEOUT         => 3,
                CURLOPT_CONNECTTIMEOUT  => 3,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_FOLLOWLOCATION  => false,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (in_array($code, [200, 301, 302])) {
                return ['exposed' => true, 'path' => $path];
            }
        }

        return ['exposed' => false, 'path' => null];
    }

    private function detectWordPress(string $html, string $host): array
    {
        $isWordPress = str_contains($html, 'wp-content') || str_contains($html, 'wp-includes');

        if (! $isWordPress) {
            return ['detected' => false];
        }

        $versionExposed = false;
        $version = null;

        if (preg_match('/<meta name=["\']generator["\'] content=["\']WordPress ([\d.]+)/i', $html, $m)) {
            $versionExposed = true;
            $version = $m[1];
        } elseif (preg_match('/\?ver=([\d.]+)/i', $html, $m)) {
            $versionExposed = true;
            $version = $m[1];
        }

        return ['detected' => true, 'version_exposed' => $versionExposed, 'version' => $version];
    }

    private function checkDirectoryListing(string $host): bool
    {
        $testPaths = ['/images/', '/assets/', '/css/', '/js/'];

        foreach ($testPaths as $path) {
            $ch = curl_init("https://{$host}{$path}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && (
                str_contains($body, 'Index of /') ||
                str_contains($body, 'Directory listing for')
            )) {
                return true;
            }
        }

        return false;
    }
}
