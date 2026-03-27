<?php

namespace App\Services\Scanners;

class ContentScanner
{
    private const TIMEOUT = 6;

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        $html = $this->safe(fn() => $this->fetchHtml($host), '');

        // --- Check 1: Mixed content ---
        // Mixed content = resources (not navigation links) loaded over HTTP on an HTTPS page.
        // We check: src= (scripts/images/iframes), action= (forms), <link href= (stylesheets).
        // We do NOT flag <a href="http://..."> — those are navigation links, not mixed content.
        $maxScore += 30;
        $mixedContent = $this->safe(fn() => $this->checkMixedContent($html, $host), ['found' => false, 'count' => 0]);
        if (! $mixedContent['found']) {
            $score += 30;
            $checks[] = [
                'id'          => 'content_mixed',
                'label'       => 'No mixed content detected',
                'status'      => 'pass',
                'description' => 'No insecure HTTP resources (scripts, images, stylesheets) found in the page HTML.',
            ];
        } else {
            $checks[] = [
                'id'             => 'content_mixed',
                'label'          => 'No mixed content detected',
                'status'         => 'fail',
                'description'    => "Found {$mixedContent['count']} resource(s) loaded over HTTP on this HTTPS page. Browsers will block or warn about these.",
                'recommendation' => 'Update all resource URLs (src, action, stylesheet href) to use HTTPS.',
            ];
        }

        // --- Check 2: CMS admin panel exposure ---
        $maxScore += 25;
        $adminExposed = $this->safe(fn() => $this->checkAdminExposure($host), ['exposed' => false, 'path' => null]);
        if (! $adminExposed['exposed']) {
            $score += 25;
            $checks[] = [
                'id'          => 'content_admin',
                'label'       => 'CMS admin panel not publicly accessible',
                'status'      => 'pass',
                'description' => 'No publicly accessible CMS admin interface found at common paths.',
            ];
        } else {
            $checks[] = [
                'id'             => 'content_admin',
                'label'          => 'CMS admin panel not publicly accessible',
                'status'         => 'warn',
                'description'    => "A CMS admin panel is directly accessible at {$adminExposed['path']}. Ensure it requires strong authentication.",
                'recommendation' => 'Restrict admin access by IP address, or add two-factor authentication.',
            ];
        }

        // --- Check 3: CMS / WordPress version exposure ---
        $maxScore += 20;
        $wordpress = $this->safe(fn() => $this->detectWordPress($html), ['detected' => false]);
        if ($wordpress['detected']) {
            if ($wordpress['version_exposed']) {
                $checks[] = [
                    'id'             => 'content_wp',
                    'label'          => 'CMS version not exposed',
                    'status'         => 'warn',
                    'description'    => "WordPress detected. Version \"{$wordpress['version']}\" is exposed in the page source, which helps attackers target known vulnerabilities.",
                    'recommendation' => 'Remove the generator meta tag and strip ?ver= parameters from script/style URLs.',
                ];
            } else {
                $score += 20;
                $checks[] = [
                    'id'          => 'content_wp',
                    'label'       => 'CMS version not exposed',
                    'status'      => 'pass',
                    'description' => 'WordPress detected but no version information is exposed in the page source.',
                ];
            }
        } else {
            $score += 20;
            $checks[] = [
                'id'          => 'content_wp',
                'label'       => 'CMS version not exposed',
                'status'      => 'pass',
                'description' => 'No CMS version information found in the page source.',
            ];
        }

        // --- Check 4: Directory listing ---
        $maxScore += 25;
        $dirListing = $this->safe(fn() => $this->checkDirectoryListing($host), false);
        if (! $dirListing) {
            $score += 25;
            $checks[] = [
                'id'          => 'content_dirlisting',
                'label'       => 'Directory listing disabled',
                'status'      => 'pass',
                'description' => 'Directory listing is not enabled — files cannot be browsed directly.',
            ];
        } else {
            $checks[] = [
                'id'             => 'content_dirlisting',
                'label'          => 'Directory listing disabled',
                'status'         => 'fail',
                'description'    => 'Directory listing is enabled — anyone can browse your server files.',
                'recommendation' => 'Disable directory listing: add "Options -Indexes" to your Apache config, or "autoindex off" in Nginx.',
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
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
            // Limit download size to 500KB — enough for checking HTML source
            CURLOPT_BUFFERSIZE     => 512000,
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        // Only return the first 500KB to avoid memory issues with very large pages
        return $html ? substr($html, 0, 512000) : '';
    }

    private function checkMixedContent(string $html, string $host): array
    {
        // Only flag active/passive resource-loading over HTTP, NOT navigation links (<a href>).
        // Patterns checked:
        //   - src="http://..."         (scripts, images, iframes, audio, video)
        //   - action="http://..."      (form submissions)
        //   - <link ... href="http://..." (stylesheets, preloads)
        //
        // We exclude http://host (same-host references are not external mixed content).

        // Build an exclusion pattern that covers both apex and www variants,
        // so http://example.com and http://www.example.com are not flagged as mixed content.
        $apexHost     = preg_replace('/^www\./i', '', $host);
        $escapedApex  = preg_quote($apexHost, '/');
        $exclusion    = '(?:www\.)?' . $escapedApex;

        $count = 0;

        // src= and action= attributes (all elements)
        $count += preg_match_all(
            '/(?:src|action)=["\']http:\/\/(?!' . $exclusion . ')[^"\']*["\']/i',
            $html
        );

        // <link href="http://..."> — only <link> elements (stylesheets, preloads), not <a href>
        $count += preg_match_all(
            '/<link[^>]+href=["\']http:\/\/(?!' . $exclusion . ')[^"\']*["\']/i',
            $html
        );

        return ['found' => $count > 0, 'count' => $count];
    }

    private function checkAdminExposure(string $host): array
    {
        // Only check CMS-specific admin panel paths.
        // We flag only HTTP 200 — redirects (to login pages) are normal and NOT an issue.
        $paths = ['/wp-admin', '/wp-login.php', '/administrator', '/admin', '/admin.php', '/phpmyadmin'];

        foreach ($paths as $path) {
            $ch = curl_init("https://{$host}{$path}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY         => true,
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200) {
                return ['exposed' => true, 'path' => $path];
            }
        }

        return ['exposed' => false, 'path' => null];
    }

    private function detectWordPress(string $html): array
    {
        $isWordPress = str_contains($html, '/wp-content/') || str_contains($html, '/wp-includes/');

        if (! $isWordPress) {
            return ['detected' => false];
        }

        // Check for version in generator meta tag
        if (preg_match('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']WordPress\s*([\d.]+)/i', $html, $m)) {
            return ['detected' => true, 'version_exposed' => true, 'version' => $m[1]];
        }

        // Check for version in script/style URLs (?ver=x.x.x)
        // Limit this to wp-content paths to avoid false positives from other scripts
        if (preg_match('/\/wp-(?:content|includes)\/[^"\']*\?ver=([\d.]+)/i', $html, $m)) {
            return ['detected' => true, 'version_exposed' => true, 'version' => $m[1]];
        }

        return ['detected' => true, 'version_exposed' => false, 'version' => null];
    }

    private function checkDirectoryListing(string $host): bool
    {
        // Test common static asset directories for open directory listings.
        // We check for Apache/Nginx directory listing signatures.
        $paths = ['/images/', '/assets/', '/uploads/', '/files/'];

        foreach ($paths as $path) {
            $ch = curl_init("https://{$host}{$path}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => false,
                // Only download enough to detect the listing signature
                CURLOPT_RANGE          => '0-4095',
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (in_array($code, [200, 206]) && $body) {
                $bodyLower = strtolower($body);
            }
            if (in_array($code, [200, 206]) && $body && (
                str_contains($bodyLower, 'index of /') ||
                str_contains($bodyLower, 'directory listing for') ||
                str_contains($bodyLower, '<title>index of')
            )) {
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
