<?php

namespace App\Services\Scanners;

class BrandingScanner
{
    use HasSafeCall;
    private const TIMEOUT = 6;

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        $html = $this->safe(fn() => $this->fetchPage($host), '');

        // 1. Favicon
        $maxScore += 15;
        $hasFavicon = $this->safe(fn() => $this->checkFavicon($host, $html), false);
        if ($hasFavicon) {
            $score += 15;
            $checks[] = [
                'id'          => 'brand_favicon',
                'label'       => 'Favicon',
                'status'      => 'pass',
                'description' => 'A favicon is present — shown in browser tabs and bookmarks.',
            ];
        } else {
            $checks[] = [
                'id'             => 'brand_favicon',
                'label'          => 'Favicon',
                'status'         => 'warn',
                'description'    => 'No favicon detected.',
                'recommendation' => 'Add a favicon.ico or <link rel="icon" href="/favicon.ico">. Use a 32×32px PNG or ICO file.',
            ];
        }

        // 2. Apple Touch Icon
        $maxScore += 10;
        if ($html && preg_match('/<link[^>]+rel=["\'][^"\']*apple-touch-icon[^"\']*["\']/i', $html)) {
            $score += 10;
            $checks[] = [
                'id'          => 'brand_apple_icon',
                'label'       => 'Apple Touch Icon',
                'status'      => 'pass',
                'description' => 'Apple Touch Icon found — used when adding the site to iOS home screens.',
            ];
        } else {
            $checks[] = [
                'id'             => 'brand_apple_icon',
                'label'          => 'Apple Touch Icon',
                'status'         => 'warn',
                'description'    => 'No Apple Touch Icon found.',
                'recommendation' => 'Add <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png"> for iOS home screen support.',
            ];
        }

        // 3. Web App Manifest
        $maxScore += 10;
        $hasManifest = $html && preg_match('/<link[^>]+rel=["\'][^"\']*manifest[^"\']*["\']/i', $html);
        if (! $hasManifest) {
            $hasManifest = $this->safe(
                fn() => $this->urlExists($host, '/manifest.json') || $this->urlExists($host, '/site.webmanifest'),
                false
            );
        }
        if ($hasManifest) {
            $score += 10;
            $checks[] = [
                'id'          => 'brand_manifest',
                'label'       => 'Web App Manifest',
                'status'      => 'pass',
                'description' => 'A Web App Manifest is present — enables "Add to Home Screen" on mobile.',
            ];
        } else {
            $checks[] = [
                'id'             => 'brand_manifest',
                'label'          => 'Web App Manifest',
                'status'         => 'warn',
                'description'    => 'No Web App Manifest found.',
                'recommendation' => 'Add a manifest.json (or site.webmanifest) with name, icons, and theme_color for PWA support.',
            ];
        }

        // 4. Open Graph title
        $maxScore += 15;
        if ($html && preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $score += 15;
            $checks[] = [
                'id'          => 'brand_og_title',
                'label'       => 'Open Graph title',
                'status'      => 'pass',
                'description' => 'og:title is set: "' . htmlspecialchars(substr($m[1], 0, 80)) . '"',
            ];
        } else {
            $checks[] = [
                'id'             => 'brand_og_title',
                'label'          => 'Open Graph title',
                'status'         => 'warn',
                'description'    => 'No og:title meta tag found.',
                'recommendation' => 'Add <meta property="og:title" content="..."> for proper social media previews on Facebook, LinkedIn, and WhatsApp.',
            ];
        }

        // 5. Open Graph description
        $maxScore += 10;
        if ($html && preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/i', $html)) {
            $score += 10;
            $checks[] = [
                'id'          => 'brand_og_desc',
                'label'       => 'Open Graph description',
                'status'      => 'pass',
                'description' => 'og:description is set.',
            ];
        } else {
            $checks[] = [
                'id'             => 'brand_og_desc',
                'label'          => 'Open Graph description',
                'status'         => 'warn',
                'description'    => 'No og:description meta tag found.',
                'recommendation' => 'Add <meta property="og:description" content="..."> for social media link previews.',
            ];
        }

        // 6. Open Graph image
        $maxScore += 15;
        if ($html && preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html)) {
            $score += 15;
            $checks[] = [
                'id'          => 'brand_og_image',
                'label'       => 'Open Graph image',
                'status'      => 'pass',
                'description' => 'og:image is set — links shared on social media will show a preview image.',
            ];
        } else {
            $checks[] = [
                'id'             => 'brand_og_image',
                'label'          => 'Open Graph image',
                'status'         => 'warn',
                'description'    => 'No og:image meta tag found.',
                'recommendation' => 'Add <meta property="og:image" content="..."> with a 1200×630px image for social sharing previews.',
            ];
        }

        // 7. Twitter/X Card
        $maxScore += 15;
        if ($html && preg_match('/<meta[^>]+name=["\']twitter:card["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $score += 15;
            $checks[] = [
                'id'          => 'brand_twitter_card',
                'label'       => 'Twitter/X Card',
                'status'      => 'pass',
                'description' => 'twitter:card is set to "' . htmlspecialchars($m[1]) . '" — links will show rich previews on X/Twitter.',
            ];
        } else {
            $checks[] = [
                'id'             => 'brand_twitter_card',
                'label'          => 'Twitter/X Card',
                'status'         => 'warn',
                'description'    => 'No twitter:card meta tag found.',
                'recommendation' => 'Add <meta name="twitter:card" content="summary_large_image"> for rich link previews on X/Twitter.',
            ];
        }

        // 8. Theme color
        $maxScore += 10;
        if ($html && preg_match('/<meta[^>]+name=["\']theme-color["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $score += 10;
            $checks[] = [
                'id'          => 'brand_theme_color',
                'label'       => 'Theme color',
                'status'      => 'pass',
                'description' => 'theme-color meta tag is set (' . htmlspecialchars($m[1]) . ') — colors the browser address bar on Android Chrome.',
            ];
        } else {
            $checks[] = [
                'id'             => 'brand_theme_color',
                'label'          => 'Theme color',
                'status'         => 'warn',
                'description'    => 'No theme-color meta tag found.',
                'recommendation' => 'Add <meta name="theme-color" content="#yourcolor"> to brand the browser address bar on mobile.',
            ];
        }

        return [
            'category' => 'Branding & Social',
            'icon'     => 'photo',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function checkFavicon(string $host, string $html): bool
    {
        if ($html && preg_match('/<link[^>]+rel=["\'][^"\']*(?:shortcut\s+)?icon[^"\']*["\']/i', $html)) {
            return true;
        }
        return $this->urlExists($host, '/favicon.ico');
    }

    private function urlExists(string $host, string $path): bool
    {
        $ch = curl_init("https://{$host}{$path}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 400;
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
            CURLOPT_RANGE          => '0-131071',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return $body ?: '';
    }

}
