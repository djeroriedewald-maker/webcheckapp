<?php

namespace App\Services\Scanners;

class CookieComplianceScanner
{
    use HasSafeCall;

    private const TIMEOUT = 8;

    // Known tracking/analytics cookies
    private const TRACKING_PATTERNS = [
        '_ga', '_gid', '_gat', '__utma', '__utmb', '__utmc', '__utmz', // Google Analytics
        '_fbp', '_fbc', 'fr',                                          // Facebook
        '_tt_',                                                         // TikTok
        '_pin_unauth',                                                  // Pinterest
        '_gcl_',                                                        // Google Ads
        'IDE', 'DSID', 'NID',                                          // DoubleClick
        '_uetsid', '_uetvid',                                           // Microsoft/Bing
        'hubspotutk', '__hs',                                          // HubSpot
    ];

    public function scan(string $host): array
    {
        $checks = [];
        $score  = 0;
        $max    = 40;

        // Fetch the page and collect all cookies
        $ch = curl_init("https://{$host}/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response ?? '', $headerSize);
        $headers = substr($response ?? '', 0, $headerSize);
        curl_close($ch);

        // Parse cookies from headers
        preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headers, $cookieMatches);
        $cookies = [];
        foreach ($cookieMatches[1] ?? [] as $c) {
            $name = strtok($c, '=');
            $cookies[] = $name;
        }

        // Check 1: Cookie consent banner
        $hasConsentBanner = false;
        if ($body) {
            $consentPatterns = [
                'cookie-consent', 'cookie-banner', 'cookie-notice', 'cookie-popup',
                'cookieconsent', 'cc-banner', 'gdpr-cookie', 'cookie-law',
                'CookieConsent', 'onetrust', 'cookiebot', 'klaro',
                'cookie policy', 'we use cookies', 'wij gebruiken cookies',
                'accept cookies', 'cookies accepteren',
            ];
            foreach ($consentPatterns as $pattern) {
                if (stripos($body, $pattern) !== false) {
                    $hasConsentBanner = true;
                    break;
                }
            }
        }

        if ($hasConsentBanner) {
            $score += 15;
            $checks[] = [
                'id'          => 'cookie_consent_present',
                'label'       => 'Cookie consent mechanism detected',
                'status'      => 'pass',
                'description' => 'A cookie consent banner or notice was detected on the page, which is required under GDPR/ePrivacy.',
            ];
        } else {
            $hasTrackingCookies = false;
            foreach ($cookies as $name) {
                foreach (self::TRACKING_PATTERNS as $pattern) {
                    if (stripos($name, $pattern) !== false) {
                        $hasTrackingCookies = true;
                        break 2;
                    }
                }
            }

            if ($hasTrackingCookies) {
                $checks[] = [
                    'id'             => 'cookie_consent_missing',
                    'label'          => 'No cookie consent with tracking cookies',
                    'status'         => 'fail',
                    'description'    => 'Tracking cookies are set without a visible cookie consent mechanism. This violates GDPR/ePrivacy requirements.',
                    'recommendation' => 'Implement a cookie consent banner that blocks non-essential cookies until the user gives explicit consent.',
                ];
            } else {
                $score += 10;
                $checks[] = [
                    'id'          => 'cookie_consent_not_needed',
                    'label'       => 'No tracking cookies, no consent needed',
                    'status'      => 'pass',
                    'description' => 'No tracking cookies detected on the homepage. Cookie consent may not be required if only functional cookies are used.',
                ];
            }
        }

        // Check 2: Third-party cookies / tracking
        $trackingCookies = [];
        foreach ($cookies as $name) {
            foreach (self::TRACKING_PATTERNS as $pattern) {
                if (stripos($name, $pattern) !== false) {
                    $trackingCookies[] = $name;
                    break;
                }
            }
        }

        if (empty($trackingCookies)) {
            $score += 10;
            $checks[] = [
                'id'          => 'cookie_no_tracking',
                'label'       => 'No tracking cookies on initial load',
                'status'      => 'pass',
                'description' => 'No known tracking or analytics cookies are set on the initial page load (before consent).',
            ];
        } else {
            $checks[] = [
                'id'             => 'cookie_tracking_preload',
                'label'          => count($trackingCookies) . ' tracking cookie(s) set before consent',
                'status'         => 'fail',
                'description'    => 'The following tracking cookies are set on initial load: ' . implode(', ', array_slice($trackingCookies, 0, 5)) . '. Under GDPR, tracking cookies require explicit consent before being placed.',
                'recommendation' => 'Defer all tracking scripts and cookies until the user has given explicit consent via the cookie banner.',
            ];
        }

        // Check 3: Total cookie count
        $totalCookies = count($cookies);
        if ($totalCookies <= 3) {
            $score += 10;
            $checks[] = [
                'id'          => 'cookie_count_low',
                'label'       => "Only {$totalCookies} cookie(s) on initial load",
                'status'      => 'pass',
                'description' => "The site sets {$totalCookies} cookie(s) on the initial page load, suggesting minimal cookie usage.",
            ];
        } elseif ($totalCookies <= 10) {
            $score += 5;
            $checks[] = [
                'id'             => 'cookie_count_moderate',
                'label'          => "{$totalCookies} cookies on initial load",
                'status'         => 'warn',
                'description'    => "The site sets {$totalCookies} cookies on the initial page load. Consider whether all are necessary before consent.",
                'recommendation' => 'Review all cookies and defer non-essential ones until after user consent.',
            ];
        } else {
            $checks[] = [
                'id'             => 'cookie_count_high',
                'label'          => "{$totalCookies} cookies on initial load",
                'status'         => 'fail',
                'description'    => "The site sets {$totalCookies} cookies on the initial page load. This high number suggests many non-essential cookies before consent.",
                'recommendation' => 'Audit all cookies, categorize them, and ensure only strictly necessary cookies are set before consent.',
            ];
        }

        // Check 4: Cookie expiration (check for overly long-lived cookies)
        preg_match_all('/^Set-Cookie:\s*(.+)$/mi', $headers, $fullCookieMatches);
        $longLived = 0;
        foreach ($fullCookieMatches[1] ?? [] as $cookieLine) {
            if (preg_match('/max-age\s*=\s*(\d+)/i', $cookieLine, $ageMatch)) {
                $days = (int)$ageMatch[1] / 86400;
                if ($days > 365) {
                    $longLived++;
                }
            } elseif (preg_match('/expires\s*=\s*(.+?)(?:;|$)/i', $cookieLine, $expMatch)) {
                $expires = @strtotime(trim($expMatch[1]));
                if ($expires && ($expires - time()) > 365 * 86400) {
                    $longLived++;
                }
            }
        }

        if ($longLived === 0) {
            $score += 5;
            $checks[] = [
                'id'          => 'cookie_expiry_ok',
                'label'       => 'No excessively long-lived cookies',
                'status'      => 'pass',
                'description' => 'All cookies have reasonable expiration times (under 1 year).',
            ];
        } else {
            $checks[] = [
                'id'             => 'cookie_expiry_long',
                'label'          => "{$longLived} cookie(s) with expiry > 1 year",
                'status'         => 'warn',
                'description'    => "{$longLived} cookie(s) have an expiration longer than 1 year. GDPR guidance suggests cookies should not persist longer than necessary.",
                'recommendation' => 'Reduce cookie lifetimes to the minimum necessary period. Analytics cookies should typically expire within 6-13 months.',
            ];
        }

        return [
            'category' => 'Cookie Compliance',
            'icon'     => 'shield-check',
            'score'    => $max > 0 ? (int) round(($score / $max) * 100) : 100,
            'checks'   => $checks,
        ];
    }
}
