<?php

namespace App\Services\Scanners;

class SslScanner
{
    private const TIMEOUT = 5;

    public function scan(string $host): array
    {
        $checks = [];
        $score = 0;
        $maxScore = 0;

        // Check if HTTPS is available + get cert info in one curl call
        $maxScore += 30;
        $certInfo = $this->getCertificateInfo($host);
        $httpsAvailable = $certInfo['reachable'];

        if ($httpsAvailable) {
            $score += 30;
            $checks[] = [
                'id'          => 'ssl_available',
                'label'       => 'HTTPS / SSL enabled',
                'status'      => 'pass',
                'description' => 'The website is accessible over HTTPS.',
            ];
        } else {
            $checks[] = [
                'id'          => 'ssl_available',
                'label'       => 'HTTPS / SSL enabled',
                'status'      => 'fail',
                'description' => 'The website does not appear to support HTTPS.',
                'recommendation' => 'Install an SSL certificate and redirect all traffic to HTTPS.',
            ];
        }

        // SSL certificate validity
        $maxScore += 30;
        if ($certInfo['valid']) {
            $score += 30;
            $checks[] = [
                'id'          => 'ssl_valid',
                'label'       => 'SSL certificate valid',
                'status'      => 'pass',
                'description' => "Certificate is valid and expires on {$certInfo['expires']} ({$certInfo['days_left']} days left).",
            ];
        } elseif ($certInfo['expires_soon']) {
            $score += 15;
            $checks[] = [
                'id'          => 'ssl_valid',
                'label'       => 'SSL certificate valid',
                'status'      => 'warn',
                'description' => "Certificate expires soon: {$certInfo['expires']} ({$certInfo['days_left']} days left).",
                'recommendation' => 'Renew your SSL certificate before it expires.',
            ];
        } else {
            $checks[] = [
                'id'          => 'ssl_valid',
                'label'       => 'SSL certificate valid',
                'status'      => 'fail',
                'description' => $certInfo['error'] ?? 'Could not validate SSL certificate.',
                'recommendation' => 'Install a valid SSL certificate from a trusted Certificate Authority.',
            ];
        }

        // HTTP → HTTPS redirect
        $maxScore += 20;
        $redirects = $this->checkHttpsRedirect($host);
        if ($redirects) {
            $score += 20;
            $checks[] = [
                'id'          => 'ssl_redirect',
                'label'       => 'HTTP redirects to HTTPS',
                'status'      => 'pass',
                'description' => 'HTTP traffic is automatically redirected to HTTPS.',
            ];
        } else {
            $checks[] = [
                'id'          => 'ssl_redirect',
                'label'       => 'HTTP redirects to HTTPS',
                'status'      => 'fail',
                'description' => 'HTTP requests are not being redirected to HTTPS.',
                'recommendation' => 'Configure a permanent (301) redirect from HTTP to HTTPS.',
            ];
        }

        // HSTS (reuse headers from cert check)
        $maxScore += 20;
        $hsts = $certInfo['hsts'];
        if ($hsts['present'] && $hsts['max_age'] >= 31536000) {
            $score += 20;
            $checks[] = [
                'id'          => 'ssl_hsts',
                'label'       => 'HSTS header configured',
                'status'      => 'pass',
                'description' => "Strict-Transport-Security header found with max-age={$hsts['max_age']}.",
            ];
        } elseif ($hsts['present']) {
            $score += 10;
            $checks[] = [
                'id'          => 'ssl_hsts',
                'label'       => 'HSTS header configured',
                'status'      => 'warn',
                'description' => "HSTS header found but max-age is too short ({$hsts['max_age']} seconds).",
                'recommendation' => 'Set max-age to at least 31536000 (1 year) and add includeSubDomains.',
            ];
        } else {
            $checks[] = [
                'id'          => 'ssl_hsts',
                'label'       => 'HSTS header configured',
                'status'      => 'fail',
                'description' => 'No Strict-Transport-Security (HSTS) header found.',
                'recommendation' => 'Add the header: Strict-Transport-Security: max-age=31536000; includeSubDomains',
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
            'reachable'   => false,
            'valid'       => false,
            'expires_soon' => false,
            'expires'     => null,
            'days_left'   => null,
            'error'       => 'Could not connect.',
            'hsts'        => ['present' => false, 'max_age' => 0],
        ];

        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => true,
            CURLOPT_NOBODY          => true,
            CURLOPT_TIMEOUT         => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT  => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 3,
            CURLOPT_CERTINFO        => true,
            CURLOPT_USERAGENT       => 'WebCheckApp/1.0',
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $certInfo = curl_getinfo($ch, CURLINFO_CERTINFO);
        curl_close($ch);

        if ($errno || $response === false) {
            return $default;
        }

        $result = ['reachable' => true, 'valid' => false, 'expires_soon' => false, 'expires' => null, 'days_left' => null, 'error' => null];

        // Parse cert expiry from CURLINFO_CERTINFO
        if (! empty($certInfo[0]['Expire date'])) {
            $expiresAt = strtotime($certInfo[0]['Expire date']);
            $daysLeft = (int) round(($expiresAt - time()) / 86400);
            $result['expires'] = date('Y-m-d', $expiresAt);
            $result['days_left'] = $daysLeft;
            $result['valid'] = $daysLeft > 30;
            $result['expires_soon'] = $daysLeft > 0 && $daysLeft <= 30;
        } else {
            $result['valid'] = true; // reachable with SSL = cert is valid enough
            $result['expires'] = 'Unknown';
            $result['days_left'] = null;
        }

        // Parse HSTS from response headers
        $hsts = ['present' => false, 'max_age' => 0];
        if (preg_match('/strict-transport-security:\s*([^\r\n]+)/i', $response, $m)) {
            $value = trim($m[1]);
            preg_match('/max-age=(\d+)/i', $value, $ageM);
            $hsts = ['present' => true, 'max_age' => isset($ageM[1]) ? (int) $ageM[1] : 0];
        }
        $result['hsts'] = $hsts;

        return $result;
    }

    private function checkHttpsRedirect(string $host): bool
    {
        $ch = curl_init("http://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => false,
            CURLOPT_TIMEOUT         => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT  => self::TIMEOUT,
            CURLOPT_NOBODY          => true,
            CURLOPT_SSL_VERIFYPEER  => false,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        return in_array($httpCode, [301, 302, 307, 308]) && str_starts_with($location, 'https://');
    }
}
