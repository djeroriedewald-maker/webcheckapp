<?php

namespace App\Services\Scanners;

class TechnologyScanner
{
    private const TIMEOUT = 8;

    public function scan(string $host): array
    {
        $response = $this->safe(fn() => $this->fetchWithInfo($host), null);

        $html        = $response['html'] ?? '';
        $headers     = $response['headers'] ?? [];
        $httpVersion = $response['http_version'] ?? null;

        // Detect technologies from headers + HTML, then deduplicate
        $raw = array_merge(
            $this->detectFromHeaders($headers),
            $this->detectFromHtml($html)
        );

        $seen = [];
        foreach ($raw as $tech) {
            $key = $tech['type'] . ':' . $tech['name'];
            $seen[$key] = $tech;
        }

        $typeOrder = ['CMS', 'E-commerce', 'Web Server', 'CDN / Security', 'Backend', 'JavaScript', 'Analytics'];
        $technologies = array_values($seen);
        usort($technologies, fn($a, $b) =>
            (array_search($a['type'], $typeOrder, true) ?: 99) <=> (array_search($b['type'], $typeOrder, true) ?: 99)
        );

        // HTTP/2 check
        // CURLINFO_HTTP_VERSION returns: 10=HTTP/1.0, 11=HTTP/1.1, 20=HTTP/2, 30=HTTP/3
        $checks = [];

        if ($httpVersion === null) {
            $checks[] = [
                'id'          => 'tech_http2',
                'label'       => 'HTTP/2 support',
                'status'      => 'info',
                'description' => 'Could not determine the HTTP protocol version in use.',
            ];
        } elseif ($httpVersion >= 20) {
            $label = $httpVersion >= 30 ? 'HTTP/3 supported' : 'HTTP/2 supported';
            $desc  = $httpVersion >= 30
                ? 'The server supports HTTP/3 (QUIC), the latest HTTP protocol for fast and reliable connections.'
                : 'The server supports HTTP/2, enabling faster loading via multiplexing and header compression.';
            $checks[] = [
                'id'          => 'tech_http2',
                'label'       => $label,
                'status'      => 'pass',
                'description' => $desc,
            ];
        } else {
            $versionLabel = $httpVersion === 10 ? 'HTTP/1.0' : 'HTTP/1.1';
            $checks[] = [
                'id'             => 'tech_http2',
                'label'          => 'HTTP/2 not enabled',
                'status'         => 'warn',
                'description'    => "The server is using {$versionLabel}. Enabling HTTP/2 can noticeably improve page load speed.",
                'recommendation' => 'Enable HTTP/2 on your server (Nginx: add "http2" to the listen directive; Apache: enable mod_http2).',
            ];
        }

        return [
            'category'     => 'Technology',
            'icon'         => 'chip',
            'score'        => null, // informational — not included in overall score
            'technologies' => $technologies,
            'checks'       => $checks,
        ];
    }

    private function fetchWithInfo(string $host): array
    {
        $headers = [];

        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
            CURLOPT_BUFFERSIZE     => 512000,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$headers) {
                $line = trim($header);
                if (str_starts_with($line, 'HTTP/')) {
                    $headers = [];
                    return strlen($header);
                }
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($name))] = trim($value);
                }
                return strlen($header);
            },
        ]);

        $html        = curl_exec($ch);
        $httpVersion = defined('CURLINFO_HTTP_VERSION')
            ? curl_getinfo($ch, CURLINFO_HTTP_VERSION)
            : null;
        curl_close($ch);

        return [
            'html'         => $html ? substr($html, 0, 512000) : '',
            'headers'      => $headers,
            'http_version' => $httpVersion,
        ];
    }

    private function detectFromHeaders(array $headers): array
    {
        $found   = [];
        $server  = $headers['server'] ?? '';
        $via     = $headers['via'] ?? '';
        $powered = $headers['x-powered-by'] ?? '';

        // Web server
        if (stripos($server, 'nginx') !== false)         $found[] = ['type' => 'Web Server', 'name' => 'Nginx'];
        elseif (stripos($server, 'apache') !== false)    $found[] = ['type' => 'Web Server', 'name' => 'Apache'];
        elseif (stripos($server, 'litespeed') !== false) $found[] = ['type' => 'Web Server', 'name' => 'LiteSpeed'];
        elseif (stripos($server, 'iis') !== false)       $found[] = ['type' => 'Web Server', 'name' => 'IIS'];
        elseif (stripos($server, 'caddy') !== false)     $found[] = ['type' => 'Web Server', 'name' => 'Caddy'];
        elseif (stripos($server, 'openresty') !== false) $found[] = ['type' => 'Web Server', 'name' => 'OpenResty'];

        // CDN / Security
        if (!empty($headers['cf-ray']) || stripos($server, 'cloudflare') !== false) {
            $found[] = ['type' => 'CDN / Security', 'name' => 'Cloudflare'];
        }
        if (!empty($headers['x-varnish']) || stripos($via, 'varnish') !== false) {
            $found[] = ['type' => 'CDN / Security', 'name' => 'Varnish'];
        }
        if (!empty($headers['x-sucuri-id']) || stripos($server, 'sucuri') !== false) {
            $found[] = ['type' => 'CDN / Security', 'name' => 'Sucuri'];
        }
        if (!empty($headers['x-iinfo']) || stripos($server, 'incapsula') !== false) {
            $found[] = ['type' => 'CDN / Security', 'name' => 'Imperva'];
        }
        if (!empty($headers['fastly-restarts']) || stripos($via, 'fastly') !== false) {
            $found[] = ['type' => 'CDN / Security', 'name' => 'Fastly'];
        }
        if (!empty($headers['x-akamai-request-id'])) {
            $found[] = ['type' => 'CDN / Security', 'name' => 'Akamai'];
        }

        // Backend language/platform
        if (stripos($powered, 'php') !== false)     $found[] = ['type' => 'Backend', 'name' => 'PHP'];
        if (stripos($powered, 'asp.net') !== false) $found[] = ['type' => 'Backend', 'name' => 'ASP.NET'];
        if (stripos($powered, 'node') !== false)    $found[] = ['type' => 'Backend', 'name' => 'Node.js'];
        if (stripos($powered, 'ruby') !== false)    $found[] = ['type' => 'Backend', 'name' => 'Ruby on Rails'];

        return $found;
    }

    private function detectFromHtml(string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $found = [];

        // ---- CMS ----
        if (str_contains($html, '/wp-content/') || str_contains($html, '/wp-includes/')) {
            $found[] = ['type' => 'CMS', 'name' => 'WordPress'];
        }
        if (str_contains($html, '/sites/default/') || str_contains($html, 'Drupal.settings') || stripos($html, 'drupal.js') !== false) {
            $found[] = ['type' => 'CMS', 'name' => 'Drupal'];
        }
        if (str_contains($html, '/media/jui/') || str_contains($html, '/components/com_') || stripos($html, 'content="Joomla') !== false) {
            $found[] = ['type' => 'CMS', 'name' => 'Joomla'];
        }
        if (str_contains($html, 'wixstatic.com') || preg_match('/content=["\']Wix/i', $html)) {
            $found[] = ['type' => 'CMS', 'name' => 'Wix'];
        }
        if (str_contains($html, 'squarespace.com') || preg_match('/generator.*squarespace/i', $html)) {
            $found[] = ['type' => 'CMS', 'name' => 'Squarespace'];
        }
        if (str_contains($html, 'data-wf-') || str_contains($html, 'webflow.com')) {
            $found[] = ['type' => 'CMS', 'name' => 'Webflow'];
        }
        // Ghost: only match generator meta tag or the actual Ghost script — not plain text mentions
        if (preg_match('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']Ghost/i', $html) || str_contains($html, 'ghost.min.js')) {
            $found[] = ['type' => 'CMS', 'name' => 'Ghost'];
        }
        // TYPO3: only match URL paths or generator tag — not plain text mentions
        if (str_contains($html, '/typo3/') || str_contains($html, 'typo3conf') || preg_match('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']TYPO3/i', $html)) {
            $found[] = ['type' => 'CMS', 'name' => 'TYPO3'];
        }

        // ---- E-commerce ----
        if (str_contains($html, 'cdn.shopify.com') || str_contains($html, 'shopify-section') || str_contains($html, 'Shopify.theme')) {
            $found[] = ['type' => 'E-commerce', 'name' => 'Shopify'];
        }
        if (str_contains($html, 'Mage.Cookies') || preg_match('/generator.*magento/i', $html) || str_contains($html, '/skin/frontend/')) {
            $found[] = ['type' => 'E-commerce', 'name' => 'Magento'];
        }
        if (str_contains($html, '/modules/ps_') || preg_match('/generator.*prestashop/i', $html)) {
            $found[] = ['type' => 'E-commerce', 'name' => 'PrestaShop'];
        }
        // WooCommerce: require a URL path or specific JS hook — not a plain text mention
        if (str_contains($html, '/woocommerce/') || str_contains($html, 'wc-ajax') || str_contains($html, '"woocommerce"')) {
            $found[] = ['type' => 'E-commerce', 'name' => 'WooCommerce'];
        }

        // ---- JavaScript frameworks ----
        if (str_contains($html, '__NEXT_DATA__') || str_contains($html, '_next/static')) {
            $found[] = ['type' => 'JavaScript', 'name' => 'Next.js'];
        }
        if (str_contains($html, '__nuxt') || str_contains($html, '_nuxt/')) {
            $found[] = ['type' => 'JavaScript', 'name' => 'Nuxt.js'];
        }
        if (str_contains($html, '__sveltekit') || str_contains($html, 'sveltekit')) {
            $found[] = ['type' => 'JavaScript', 'name' => 'SvelteKit'];
        }
        // React: only match reliable in-HTML markers; skip filename patterns (modern bundlers rename everything)
        if (str_contains($html, 'data-reactroot') || str_contains($html, '__reactFiber') || str_contains($html, 'react-dom@') || str_contains($html, '/react-dom.production.min.js')) {
            $found[] = ['type' => 'JavaScript', 'name' => 'React'];
        }
        if (str_contains($html, 'data-v-') || str_contains($html, '__vue') || preg_match('/vue(?:\.min)?\.js/i', $html)) {
            $found[] = ['type' => 'JavaScript', 'name' => 'Vue.js'];
        }
        if (str_contains($html, 'ng-version') || str_contains($html, '_nghost') || preg_match('/angular(?:\.min)?\.js/i', $html)) {
            $found[] = ['type' => 'JavaScript', 'name' => 'Angular'];
        }
        if (preg_match('/jquery[.\-][\d.]+(?:\.min)?\.js/i', $html) || str_contains($html, 'jQuery.fn.jquery')) {
            $found[] = ['type' => 'JavaScript', 'name' => 'jQuery'];
        }

        // ---- Analytics ----
        // Google Analytics: detect both Universal Analytics (UA-) and GA4 (G-) measurement IDs
        if (
            str_contains($html, 'google-analytics.com/analytics.js') ||
            str_contains($html, "ga('create") ||
            preg_match('/gtag\s*\(\s*["\']config["\'].*["\'](?:UA-|G-)[A-Z0-9]/i', $html) ||
            preg_match('/["\'](?:UA-|G-)[A-Z0-9]{6,}["\']/i', $html)
        ) {
            $found[] = ['type' => 'Analytics', 'name' => 'Google Analytics'];
        }
        if (str_contains($html, 'googletagmanager.com/gtm.js') || str_contains($html, 'googletagmanager.com/ns.html')) {
            $found[] = ['type' => 'Analytics', 'name' => 'Google Tag Manager'];
        }
        if (str_contains($html, 'matomo.js') || str_contains($html, 'piwik.js')) {
            $found[] = ['type' => 'Analytics', 'name' => 'Matomo'];
        }
        if (str_contains($html, 'plausible.io')) {
            $found[] = ['type' => 'Analytics', 'name' => 'Plausible'];
        }
        if (str_contains($html, 'usefathom.com')) {
            $found[] = ['type' => 'Analytics', 'name' => 'Fathom'];
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
