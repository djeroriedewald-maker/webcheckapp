<?php

namespace App\Services\Scanners;

class RobotsScanner
{
    use HasSafeCall;
    private const TIMEOUT = 6;

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        // 1. robots.txt exists and is valid
        $maxScore += 25;
        $robotsBody  = $this->safe(fn() => $this->fetchUrl("https://{$host}/robots.txt"), null);
        $robotsValid = $robotsBody !== null && stripos($robotsBody, 'user-agent') !== false;

        if ($robotsValid) {
            $score += 25;
            $checks[] = [
                'id'          => 'robots_exists',
                'label'       => 'robots.txt present',
                'status'      => 'pass',
                'description' => 'A valid robots.txt file was found.',
            ];
        } elseif ($robotsBody !== null) {
            $score += 10;
            $checks[] = [
                'id'             => 'robots_exists',
                'label'          => 'robots.txt present',
                'status'         => 'warn',
                'description'    => 'A robots.txt file exists but does not appear to be valid (no User-agent directive found).',
                'recommendation' => 'Ensure robots.txt starts with User-agent: directives.',
            ];
        } else {
            $checks[] = [
                'id'             => 'robots_exists',
                'label'          => 'robots.txt present',
                'status'         => 'warn',
                'description'    => 'No robots.txt file found at /robots.txt.',
                'recommendation' => "Create a robots.txt file to guide search engine crawlers. Minimum:\nUser-agent: *\nDisallow:",
            ];
        }

        // 2. Not blocking all crawlers
        if ($robotsValid) {
            $maxScore += 20;
            // Check for pattern: User-agent: * followed by Disallow: / (blocking all)
            $isBlocking = (bool) preg_match('/user-agent\s*:\s*\*.*?disallow\s*:\s*\//is', $robotsBody);
            if (! $isBlocking) {
                $score += 20;
                $checks[] = [
                    'id'          => 'robots_not_blocking',
                    'label'       => 'Crawlers not fully blocked',
                    'status'      => 'pass',
                    'description' => 'robots.txt does not fully block all search engine crawlers.',
                ];
            } else {
                $checks[] = [
                    'id'             => 'robots_not_blocking',
                    'label'          => 'Crawlers not fully blocked',
                    'status'         => 'warn',
                    'description'    => 'robots.txt appears to block all crawlers (Disallow: /). This will prevent search engine indexing.',
                    'recommendation' => 'If this is a production site, remove or restrict the broad Disallow: / rule to allow indexing.',
                ];
            }
        }

        // 3. Sitemap referenced in robots.txt
        $maxScore += 20;
        $sitemapUrl = null;
        if ($robotsValid && preg_match('/^sitemap\s*:\s*(https?:\/\/[^\s]+)/im', $robotsBody, $m)) {
            $sitemapUrl = trim($m[1]);
            $score += 20;
            $checks[] = [
                'id'          => 'robots_sitemap_ref',
                'label'       => 'Sitemap referenced in robots.txt',
                'status'      => 'pass',
                'description' => "robots.txt references sitemap: {$sitemapUrl}",
            ];
        } else {
            $checks[] = [
                'id'             => 'robots_sitemap_ref',
                'label'          => 'Sitemap referenced in robots.txt',
                'status'         => 'warn',
                'description'    => 'robots.txt does not reference a sitemap.',
                'recommendation' => 'Add a Sitemap: https://yourdomain.com/sitemap.xml line to robots.txt.',
            ];
        }

        // 4. Sitemap accessible
        $maxScore += 20;
        $sitemapToCheck = $sitemapUrl ?? "https://{$host}/sitemap.xml";
        $sitemapBody    = $this->safe(fn() => $this->fetchUrl($sitemapToCheck), null);
        $sitemapOk      = $sitemapBody !== null
            && (str_contains($sitemapBody, '<urlset') || str_contains($sitemapBody, '<sitemapindex'));

        if ($sitemapOk) {
            $score += 20;
            $urlCount = preg_match_all('/<url>/', $sitemapBody);
            $checks[] = [
                'id'          => 'robots_sitemap',
                'label'       => 'Sitemap accessible',
                'status'      => 'pass',
                'description' => 'A valid XML sitemap is accessible'
                    . ($urlCount > 0 ? " containing {$urlCount} URLs." : '.'),
            ];
        } else {
            // Try alternate path
            $altBody = $this->safe(fn() => $this->fetchUrl("https://{$host}/sitemap_index.xml"), null);
            if ($altBody && str_contains($altBody, '<sitemapindex')) {
                $score += 20;
                $checks[] = [
                    'id'          => 'robots_sitemap',
                    'label'       => 'Sitemap accessible',
                    'status'      => 'pass',
                    'description' => 'A valid sitemap index was found at /sitemap_index.xml.',
                ];
            } else {
                $checks[] = [
                    'id'             => 'robots_sitemap',
                    'label'          => 'Sitemap accessible',
                    'status'         => 'warn',
                    'description'    => 'No accessible XML sitemap found at /sitemap.xml or /sitemap_index.xml.',
                    'recommendation' => 'Create an XML sitemap and submit it to Google Search Console and Bing Webmaster Tools.',
                ];
            }
        }

        // 5. security.txt
        $maxScore += 15;
        $secTxt = $this->safe(fn() => $this->fetchUrl("https://{$host}/.well-known/security.txt"), null);
        if ($secTxt && stripos($secTxt, 'Contact:') !== false) {
            $score += 15;
            $checks[] = [
                'id'          => 'robots_security_txt',
                'label'       => 'security.txt present',
                'status'      => 'pass',
                'description' => 'A security.txt file was found at /.well-known/security.txt — security researchers can report vulnerabilities.',
            ];
        } else {
            $checks[] = [
                'id'             => 'robots_security_txt',
                'label'          => 'security.txt present',
                'status'         => 'warn',
                'description'    => 'No security.txt found at /.well-known/security.txt.',
                'recommendation' => 'Create a security.txt file (RFC 9116) with Contact: and Expires: fields to enable responsible vulnerability disclosure.',
            ];
        }

        return [
            'category' => 'Robots & Sitemap',
            'icon'     => 'document-text',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function fetchUrl(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_RANGE          => '0-65535',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($code >= 200 && $code < 400 && $body) ? $body : null;
    }

}
