<?php

namespace App\Services\Scanners;

class PrivacyScanner
{
    private const TIMEOUT = 8;

    public function scan(string $host): array
    {
        $checks = [];

        $html = $this->safe(fn() => $this->fetchPage($host), '');

        // 1. Cookie consent / GDPR banner
        $hasConsent = $this->detectCookieConsent($html);
        if ($hasConsent) {
            $checks[] = [
                'id'          => 'privacy_cookie_consent',
                'label'       => 'Cookie consent notice',
                'status'      => 'pass',
                'description' => 'A cookie consent notice or GDPR banner was detected on the page.',
            ];
        } else {
            $checks[] = [
                'id'             => 'privacy_cookie_consent',
                'label'          => 'Cookie consent notice',
                'status'         => 'warn',
                'description'    => 'No cookie consent notice detected. If this site sets non-essential cookies, a GDPR-compliant consent banner is legally required in the EU.',
                'recommendation' => 'Add a cookie consent banner (e.g. CookieYes, Cookiebot, or a custom implementation) and obtain explicit consent before loading analytics or marketing cookies.',
            ];
        }

        // 2. Privacy policy link
        $hasPrivacy = $this->detectPrivacyPolicy($html);
        if ($hasPrivacy) {
            $checks[] = [
                'id'          => 'privacy_policy_link',
                'label'       => 'Privacy policy',
                'status'      => 'pass',
                'description' => 'A privacy policy link was found on the page.',
            ];
        } else {
            $checks[] = [
                'id'             => 'privacy_policy_link',
                'label'          => 'Privacy policy',
                'status'         => 'warn',
                'description'    => 'No privacy policy link detected. A privacy policy is legally required under GDPR (EU), CCPA (California), and most modern privacy laws.',
                'recommendation' => 'Add a privacy policy page and link to it from the footer. The policy must explain what data you collect, why, and how users can request deletion.',
            ];
        }

        // 3. Third-party tracking scripts
        $trackers = $this->detectTrackers($html);
        if (empty($trackers)) {
            $checks[] = [
                'id'          => 'privacy_trackers',
                'label'       => 'Third-party trackers',
                'status'      => 'pass',
                'description' => 'No known third-party tracking scripts detected on the page.',
            ];
        } else {
            $trackerList = implode(', ', $trackers);
            $checks[] = [
                'id'             => 'privacy_trackers',
                'label'          => 'Third-party trackers',
                'status'         => 'warn',
                'description'    => 'Tracking scripts detected: ' . $trackerList . '. These collect user data and require explicit consent under GDPR.',
                'recommendation' => 'Load tracking scripts only after the user has given consent. Use a consent management platform or tag manager to conditionally activate trackers.',
            ];
        }

        return [
            'category' => 'Privacy & GDPR',
            'icon'     => 'eye-slash',
            'score'    => null,
            'checks'   => $checks,
        ];
    }

    private function fetchPage(string $host): string
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_RANGE          => '0-131071', // first 128 KB
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        return $body ?: '';
    }

    private function detectCookieConsent(string $html): bool
    {
        if (! $html) return false;

        $lower = strtolower($html);

        $keywords = [
            'cookieconsent', 'cookie-consent', 'cookie_consent',
            'cookie-law', 'cookie-notice', 'cookie-banner', 'cookie-popup',
            'cookiebot', 'onetrust', 'cookieyes', 'cookiefirst', 'didomi',
            'cc-banner', 'cookie-policy', 'gdpr-consent', 'gdprconsent',
            'trustarcbar', 'cookie-agree', 'accept-cookies', 'cookies-accepted',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        // Also check for common consent-related phrases
        return (bool) preg_match('/\baccept\s+(all\s+)?cookies?\b/i', $html)
            || (bool) preg_match('/\bwe\s+use\s+cookies?\b/i', $html)
            || (bool) preg_match('/\bcookie\s+(preferences?|settings?)\b/i', $html);
    }

    private function detectPrivacyPolicy(string $html): bool
    {
        if (! $html) return false;

        $lower = strtolower($html);

        $keywords = [
            'privacy policy', 'privacy-policy', 'privacybeleid',
            'privacyverklaring', 'datenschutzerklärung', 'datenschutz',
            'politique de confidentialité', 'política de privacidad',
            'informativa sulla privacy',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        // Check for href containing /privacy
        return (bool) preg_match('/href=["\'][^"\']*\bpriv(?:acy)?\b[^"\']*["\']/i', $html);
    }

    private function detectTrackers(string $html): array
    {
        if (! $html) return [];

        $trackers = [
            'Google Analytics'   => ['google-analytics.com/analytics.js', 'google-analytics.com/ga.js', 'GoogleAnalyticsObject', "gtag('config"],
            'Google Tag Manager' => ['googletagmanager.com/gtm.js', 'GTM-'],
            'Facebook Pixel'     => ['connect.facebook.net/en_US/fbevents.js', "fbq('init", "fbq('track"],
            'Hotjar'             => ['static.hotjar.com', 'hjid:', '_hjSettings'],
            'Intercom'           => ['widget.intercom.io', 'intercomSettings'],
            'Mixpanel'           => ['cdn.mxpnl.com', 'mixpanel.init'],
            'LinkedIn Insight'   => ['snap.licdn.com/li.lms-analytics', '_linkedin_partner_id'],
            'Twitter/X Pixel'    => ['static.ads-twitter.com/uwt.js', "twq('init"],
            'TikTok Pixel'       => ['analytics.tiktok.com/i18n/pixel', "ttq.load"],
            'Crisp Chat'         => ['client.crisp.chat', 'CRISP_WEBSITE_ID'],
            'Heap Analytics'     => ['cdn.heapanalytics.com', 'heap.load'],
            'Segment'            => ['cdn.segment.com/analytics.js', 'analytics.identify'],
        ];

        $found = [];
        foreach ($trackers as $name => $signatures) {
            foreach ($signatures as $sig) {
                if (stripos($html, $sig) !== false) {
                    $found[] = $name;
                    break;
                }
            }
        }

        return $found;
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
