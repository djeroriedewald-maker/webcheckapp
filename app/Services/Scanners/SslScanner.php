<?php

namespace App\Services\Scanners;

class SslScanner
{
    use HasSafeCall;
    private const TIMEOUT = 4;

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        $certInfo = $this->safe(fn() => $this->getCertificateInfo($host), [
            'reachable'    => false,
            'valid'        => false,
            'expires_soon' => false,
            'expires'      => null,
            'days_left'    => null,
            'error'        => 'Could not connect.',
            'hsts'         => ['present' => false, 'max_age' => 0, 'includes_subdomains' => false],
        ]);

        // --- Check 1: HTTPS available ---
        $maxScore += 30;
        if ($certInfo['reachable']) {
            $score += 30;
            $checks[] = [
                'id'          => 'ssl_available',
                'label'       => 'HTTPS / SSL enabled',
                'status'      => 'pass',
                'description' => 'The website is accessible over HTTPS.',
            ];
        } else {
            $checks[] = [
                'id'             => 'ssl_available',
                'label'          => 'HTTPS / SSL enabled',
                'status'         => 'fail',
                'description'    => 'The website does not appear to support HTTPS.',
                'recommendation' => 'Install an SSL certificate and redirect all traffic to HTTPS.',
            ];
        }

        // --- Check 2: Certificate validity ---
        $maxScore += 30;
        if ($certInfo['expires_soon']) {
            $score += 10;
            $checks[] = [
                'id'             => 'ssl_valid',
                'label'          => 'SSL certificate valid',
                'status'         => 'warn',
                'description'    => "Certificate expires soon: {$certInfo['expires']} ({$certInfo['days_left']} days left).",
                'recommendation' => 'Renew your SSL certificate before it expires.',
            ];
        } elseif ($certInfo['valid']) {
            $score += 30;
            $description = ($certInfo['expires'] && $certInfo['days_left'] !== null)
                ? "Certificate is valid and expires on {$certInfo['expires']} ({$certInfo['days_left']} days left)."
                : 'Certificate is valid.';
            $checks[] = [
                'id'          => 'ssl_valid',
                'label'       => 'SSL certificate valid',
                'status'      => 'pass',
                'description' => $description,
            ];
        } else {
            $checks[] = [
                'id'             => 'ssl_valid',
                'label'          => 'SSL certificate valid',
                'status'         => 'fail',
                'description'    => $certInfo['error'] ?? 'SSL certificate is invalid or expired.',
                'recommendation' => 'Install a valid SSL certificate from a trusted Certificate Authority.',
            ];
        }

        // --- Check 3: HTTP → HTTPS redirect ---
        $maxScore += 20;
        $redirect = $this->safe(fn() => $this->checkHttpsRedirect($host), ['redirects' => false, 'permanent' => false]);
        if ($redirect['redirects'] && $redirect['permanent']) {
            $score += 20;
            $checks[] = [
                'id'          => 'ssl_redirect',
                'label'       => 'HTTP redirects to HTTPS',
                'status'      => 'pass',
                'description' => 'HTTP traffic is permanently (301) redirected to HTTPS.',
            ];
        } elseif ($redirect['redirects']) {
            $score += 12;
            $checks[] = [
                'id'             => 'ssl_redirect',
                'label'          => 'HTTP redirects to HTTPS',
                'status'         => 'warn',
                'description'    => 'HTTP redirects to HTTPS, but not via a fully permanent redirect chain.',
                'recommendation' => 'Use 301 permanent redirects at every step from HTTP to HTTPS for better SEO and caching.',
            ];
        } else {
            $checks[] = [
                'id'             => 'ssl_redirect',
                'label'          => 'HTTP redirects to HTTPS',
                'status'         => 'fail',
                'description'    => 'HTTP requests are not being redirected to HTTPS.',
                'recommendation' => 'Configure a permanent (301) redirect from HTTP to HTTPS.',
            ];
        }

        // --- Check 4: HSTS ---
        $maxScore += 20;
        $hsts = $certInfo['hsts'];
        if ($hsts['present'] && $hsts['max_age'] >= 31536000) {
            $score += 20;
            $subdomain = $hsts['includes_subdomains'] ? ' includeSubDomains is set.' : '';
            $checks[] = [
                'id'          => 'ssl_hsts',
                'label'       => 'HSTS header configured',
                'status'      => 'pass',
                'description' => "Strict-Transport-Security header found with max-age={$hsts['max_age']}.{$subdomain}",
            ];
        } elseif ($hsts['present']) {
            $score += 8;
            $checks[] = [
                'id'             => 'ssl_hsts',
                'label'          => 'HSTS header configured',
                'status'         => 'warn',
                'description'    => "HSTS header present but max-age is only {$hsts['max_age']} seconds (minimum recommended: 31536000).",
                'recommendation' => 'Set Strict-Transport-Security: max-age=31536000; includeSubDomains',
            ];
        } else {
            $checks[] = [
                'id'             => 'ssl_hsts',
                'label'          => 'HSTS header configured',
                'status'         => 'fail',
                'description'    => 'No Strict-Transport-Security (HSTS) header found.',
                'recommendation' => 'Add: Strict-Transport-Security: max-age=31536000; includeSubDomains',
            ];
        }

        // --- Check 5: Weak cipher suites ---
        if ($certInfo['reachable']) {
            $maxScore += 15;
            $weakCiphers = $this->safe(fn() => $this->checkWeakCiphers($host), []);
            if (empty($weakCiphers)) {
                $score += 15;
                $checks[] = [
                    'id'          => 'ssl_ciphers',
                    'label'       => 'No weak cipher suites',
                    'status'      => 'pass',
                    'description' => 'Server does not accept known weak cipher suites (RC4, 3DES, EXPORT, NULL).',
                ];
            } else {
                $checks[] = [
                    'id'             => 'ssl_ciphers',
                    'label'          => 'No weak cipher suites',
                    'status'         => 'fail',
                    'description'    => 'Server accepts weak cipher suite(s): ' . implode(', ', $weakCiphers) . '. These ciphers have known cryptographic weaknesses.',
                    'recommendation' => "Restrict your cipher list in your server config:\nNginx: ssl_ciphers ECDH+AESGCM:ECDH+AES256:ECDH+AES128:!aNULL:!MD5:!3DES:!RC4;\nApache: SSLCipherSuite HIGH:!aNULL:!MD5:!3DES:!RC4\nThen reload your server.",
                ];
            }
        }

        // --- Check 6: TLS 1.0 / 1.1 deprecated versions ---
        // Only meaningful if HTTPS is reachable at all.
        if ($certInfo['reachable']) {
            $maxScore += 15;
            $oldTls = $this->safe(fn() => $this->checkOldTls($host), []);
            if (empty($oldTls)) {
                $score += 15;
                $checks[] = [
                    'id'          => 'ssl_tls_version',
                    'label'       => 'TLS 1.0 and 1.1 disabled',
                    'status'      => 'pass',
                    'description' => 'Server only accepts TLS 1.2 or higher. Deprecated TLS versions are not supported.',
                ];
            } else {
                $checks[] = [
                    'id'             => 'ssl_tls_version',
                    'label'          => 'TLS 1.0 and 1.1 disabled',
                    'status'         => 'fail',
                    'description'    => 'Server still accepts ' . implode(' and ', $oldTls) . '. These protocol versions are deprecated, have known vulnerabilities (POODLE, BEAST), and are banned by PCI DSS since 2018.',
                    'recommendation' => "Disable TLS 1.0 and 1.1 in your server config:\nNginx: ssl_protocols TLSv1.2 TLSv1.3;\nApache: SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1",
                ];
            }
        }

        return [
            'category' => 'SSL & HTTPS',
            'icon'     => 'shield-check',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    /**
     * Try the given host, then www.{host} as fallback.
     * Many sites have SSL only on www and port 443 is not open on the apex domain.
     */
    private function getCertificateInfo(string $host): array
    {
        $info = $this->fetchSslInfo($host);
        if ($info['reachable']) {
            return $info;
        }

        // Fallback: try www prefix if not already present
        if (! str_starts_with($host, 'www.')) {
            $www = $this->fetchSslInfo("www.{$host}");
            if ($www['reachable']) {
                return $www;
            }
        }

        return $info; // return last failed result for error reporting
    }

    private function fetchSslInfo(string $host): array
    {
        $responseHeaders = '';

        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,   // follow HTTPS→HTTPS redirects (e.g. apex → www)
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CERTINFO       => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
            CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$responseHeaders) {
                if (str_starts_with(trim($header), 'HTTP/')) {
                    $responseHeaders = ''; // reset — keep only the final response headers
                }
                $responseHeaders .= $header;
                return strlen($header);
            },
        ]);

        curl_exec($ch);
        $errno       = curl_errno($ch);
        $certInfoRaw = curl_getinfo($ch, CURLINFO_CERTINFO);
        curl_close($ch);

        if ($errno) {
            return [
                'reachable'    => false,
                'valid'        => false,
                'expires_soon' => false,
                'expires'      => null,
                'days_left'    => null,
                'error'        => 'SSL connection failed or certificate is invalid.',
                'hsts'         => ['present' => false, 'max_age' => 0, 'includes_subdomains' => false],
            ];
        }

        $result = [
            'reachable'    => true,
            'valid'        => false,
            'expires_soon' => false,
            'expires'      => null,
            'days_left'    => null,
            'error'        => null,
            'hsts'         => ['present' => false, 'max_age' => 0, 'includes_subdomains' => false],
        ];

        // Parse certificate expiry
        $expireRaw = $certInfoRaw[0]['Expire date'] ?? $certInfoRaw[0]['expire date'] ?? null;
        if ($expireRaw && ($expiresAt = strtotime($expireRaw)) && $expiresAt > 0) {
            $daysLeft               = (int) round(($expiresAt - time()) / 86400);
            $result['expires']      = date('Y-m-d', $expiresAt);
            $result['days_left']    = $daysLeft;
            $result['valid']        = $daysLeft > 30;
            $result['expires_soon'] = $daysLeft > 0 && $daysLeft <= 30;
        } else {
            // Reachable over HTTPS with no parse error = cert is at minimum trusted by curl
            $result['valid'] = true;
        }

        // Parse HSTS from final response headers
        if (preg_match('/^strict-transport-security:\s*([^\r\n]+)/im', $responseHeaders, $m)) {
            $value  = trim($m[1]);
            $maxAge = 0;
            $subdoms = false;
            if (preg_match('/max-age\s*=\s*(\d+)/i', $value, $ageM)) {
                $maxAge = (int) $ageM[1];
            }
            if (stripos($value, 'includesubdomains') !== false) {
                $subdoms = true;
            }
            $result['hsts'] = ['present' => true, 'max_age' => $maxAge, 'includes_subdomains' => $subdoms];
        }

        return $result;
    }

    /**
     * Follow the full redirect chain from http:// and check whether it ends at https://.
     * This correctly handles multi-hop chains like:
     *   http://domain.com → http://www.domain.com → https://www.domain.com
     */
    private function checkHttpsRedirect(string $host): array
    {
        $hasTemporaryRedirect = false;

        $ch = curl_init("http://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_NOBODY         => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$hasTemporaryRedirect) {
                // Track any temporary (302/307) redirect in the chain
                if (preg_match('/^HTTP\/\S+\s+(302|307)\b/i', $header)) {
                    $hasTemporaryRedirect = true;
                }
                return strlen($header);
            },
        ]);

        curl_exec($ch);
        $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        $endsOnHttps  = str_starts_with($finalUrl, 'https://');
        $wasRedirected = rtrim($finalUrl, '/') !== "http://{$host}";

        return [
            'redirects' => $endsOnHttps && $wasRedirected,
            'permanent' => $endsOnHttps && $wasRedirected && ! $hasTemporaryRedirect,
        ];
    }

    /**
     * Test whether the server accepts a connection with TLS 1.0 or TLS 1.1.
     * Returns an array of deprecated version names that are still accepted.
     * A false negative is possible if the scanner's own OpenSSL has these versions compiled out.
     */
    private function checkWeakCiphers(string $host): array
    {
        $weakGroups = [
            'RC4'    => 'RC4-SHA:RC4-MD5',
            '3DES'   => 'DES-CBC3-SHA',
            'EXPORT' => 'EXP-RC4-MD5:EXP-DES-CBC-SHA',
            'NULL'   => 'NULL-SHA:NULL-MD5',
        ];

        // Run all cipher checks in parallel via curl_multi
        $multi   = curl_multi_init();
        $handles = [];

        foreach ($weakGroups as $name => $cipherList) {
            $ch = curl_init("https://{$host}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_NOBODY          => true,
                CURLOPT_TIMEOUT         => (float) self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT  => (float) self::TIMEOUT,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => 0,
                CURLOPT_SSL_CIPHER_LIST => $cipherList,
                CURLOPT_USERAGENT       => 'Mozilla/5.0 WebCheckApp/1.0',
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$name] = $ch;
        }

        do {
            curl_multi_exec($multi, $running);
            if ($running) curl_multi_select($multi);
        } while ($running);

        $accepted = [];
        foreach ($handles as $name => $ch) {
            if (curl_errno($ch) === 0) {
                $accepted[] = $name;
            }
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);
        return $accepted;
    }

    /**
     * Check whether the server accepts TLS 1.0 or TLS 1.1 using curl.
     * curl respects CURLOPT_TIMEOUT reliably for SSL; stream_socket_client does not.
     */
    private function checkOldTls(string $host): array
    {
        $supported = [];

        // CURL_SSLVERSION_MAX_TLSv1_x forces the negotiation ceiling so we can
        // test whether the server accepts that specific version.
        // These constants require libcurl ≥ 7.54 (available since PHP 7.3).
        $versions = [];
        if (defined('CURL_SSLVERSION_MAX_TLSv1_0')) {
            $versions['TLS 1.0'] = CURL_SSLVERSION_TLSv1_0 | CURL_SSLVERSION_MAX_TLSv1_0;
        }
        if (defined('CURL_SSLVERSION_MAX_TLSv1_1')) {
            $versions['TLS 1.1'] = CURL_SSLVERSION_TLSv1_1 | CURL_SSLVERSION_MAX_TLSv1_1;
        }

        if (empty($versions)) {
            return [];
        }

        foreach ($versions as $name => $sslVersion) {
            $ch = curl_init("https://{$host}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY         => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSLVERSION     => $sslVersion,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
            ]);
            curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            if ($errno === 0) {
                $supported[] = $name;
            }
        }

        return $supported;
    }

}
