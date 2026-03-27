<?php

namespace App\Services\Scanners;

class SslScanner
{
    private const TIMEOUT = 6;

    public function scan(string $host): array
    {
        $checks = [];
        $score  = 0;
        $maxScore = 0;

        $defaultCert = [
            'reachable'    => false,
            'valid'        => false,
            'expires_soon' => false,
            'expires'      => null,
            'days_left'    => null,
            'error'        => 'Could not connect.',
            'hsts'         => ['present' => false, 'max_age' => 0, 'includes_subdomains' => false],
        ];

        $certInfo = $this->safe(fn() => $this->getCertificateInfo($host), $defaultCert);

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
                'description'    => 'HTTP redirects to HTTPS, but not via a permanent (301) redirect.',
                'recommendation' => 'Use a 301 permanent redirect from HTTP to HTTPS for better SEO and caching.',
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

        return [
            'category' => 'SSL & HTTPS',
            'icon'     => 'shield-check',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function getCertificateInfo(string $host): array
    {
        $default = [
            'reachable'    => false,
            'valid'        => false,
            'expires_soon' => false,
            'expires'      => null,
            'days_left'    => null,
            'error'        => 'Could not connect.',
            'hsts'         => ['present' => false, 'max_age' => 0, 'includes_subdomains' => false],
        ];

        // Capture only the headers of the final (non-redirect) response
        $responseHeaders = '';
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false, // Do NOT follow — check the host the user gave us
            CURLOPT_CERTINFO       => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        // Use HEADERFUNCTION so we always have the headers of the actual response
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
            $responseHeaders .= $header;
            return strlen($header);
        });

        curl_exec($ch);
        $errno    = curl_errno($ch);
        $certInfo = curl_getinfo($ch, CURLINFO_CERTINFO);
        curl_close($ch);

        if ($errno) {
            return array_merge($default, ['error' => 'SSL connection failed or certificate is invalid.']);
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

        // Parse certificate expiry date
        $expireRaw = $certInfo[0]['Expire date'] ?? $certInfo[0]['expire date'] ?? null;
        if ($expireRaw && ($expiresAt = strtotime($expireRaw)) && $expiresAt > 0) {
            $daysLeft             = (int) round(($expiresAt - time()) / 86400);
            $result['expires']    = date('Y-m-d', $expiresAt);
            $result['days_left']  = $daysLeft;
            $result['valid']      = $daysLeft > 30;
            $result['expires_soon'] = $daysLeft > 0 && $daysLeft <= 30;
        } else {
            // Reachable over HTTPS with valid SSL = cert is at minimum trusted
            $result['valid'] = true;
        }

        // Parse HSTS from the response headers (captured via HEADERFUNCTION — correct response only)
        if (preg_match('/^strict-transport-security:\s*([^\r\n]+)/im', $responseHeaders, $m)) {
            $value   = trim($m[1]);
            $maxAge  = 0;
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

    private function checkHttpsRedirect(string $host): array
    {
        $ch = curl_init("http://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_NOBODY         => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        $isRedirect  = in_array($httpCode, [301, 302, 307, 308]);
        $toHttps     = str_starts_with((string) $location, 'https://');
        $isPermanent = in_array($httpCode, [301, 308]);

        return [
            'redirects' => $isRedirect && $toHttps,
            'permanent' => $isRedirect && $toHttps && $isPermanent,
        ];
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
