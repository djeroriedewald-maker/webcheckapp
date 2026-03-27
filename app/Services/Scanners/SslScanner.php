<?php

namespace App\Services\Scanners;

class SslScanner
{
    public function scan(string $host): array
    {
        $checks = [];
        $score = 0;
        $maxScore = 0;

        // Check if HTTPS is available
        $maxScore += 30;
        $httpsAvailable = $this->checkHttps($host);
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

        // Check SSL certificate validity
        $maxScore += 30;
        $certInfo = $this->getCertificateInfo($host);
        if ($certInfo['valid']) {
            $score += 30;
            $checks[] = [
                'id'          => 'ssl_valid',
                'label'       => 'SSL certificate valid',
                'status'      => 'pass',
                'description' => "Certificate is valid and expires on {$certInfo['expires']}.",
                'detail'      => $certInfo,
            ];
        } elseif ($certInfo['expires_soon']) {
            $score += 15;
            $checks[] = [
                'id'          => 'ssl_valid',
                'label'       => 'SSL certificate valid',
                'status'      => 'warn',
                'description' => "Certificate expires soon: {$certInfo['expires']}.",
                'recommendation' => 'Renew your SSL certificate before it expires.',
                'detail'      => $certInfo,
            ];
        } else {
            $checks[] = [
                'id'          => 'ssl_valid',
                'label'       => 'SSL certificate valid',
                'status'      => 'fail',
                'description' => $certInfo['error'] ?? 'Could not validate SSL certificate.',
                'recommendation' => 'Install a valid SSL certificate from a trusted Certificate Authority.',
                'detail'      => $certInfo,
            ];
        }

        // Check HTTP → HTTPS redirect
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

        // Check HSTS header
        $maxScore += 20;
        $hsts = $this->checkHsts($host);
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
            'category'  => 'SSL & HTTPS',
            'icon'      => 'shield-check',
            'score'     => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'    => $checks,
        ];
    }

    private function checkHttps(string $host): bool
    {
        $context = stream_context_create([
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            'http' => ['timeout' => 10, 'ignore_errors' => true],
        ]);

        $handle = @fopen("https://{$host}", 'r', false, $context);
        if ($handle) {
            fclose($handle);
            return true;
        }

        return false;
    }

    private function getCertificateInfo(string $host): array
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer'       => true,
                'verify_peer_name'  => true,
            ],
        ]);

        $handle = @stream_socket_client(
            "ssl://{$host}:443",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (! $handle) {
            return ['valid' => false, 'expires_soon' => false, 'error' => $errstr];
        }

        $params = stream_context_get_params($handle);
        fclose($handle);

        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        if (! $cert) {
            return ['valid' => false, 'expires_soon' => false, 'error' => 'Could not parse certificate.'];
        }

        $expiresAt = $cert['validTo_time_t'];
        $now = time();
        $daysLeft = (int) round(($expiresAt - $now) / 86400);
        $expiresDate = date('Y-m-d', $expiresAt);

        return [
            'valid'       => $expiresAt > $now,
            'expires_soon' => $daysLeft <= 30 && $expiresAt > $now,
            'expires'     => $expiresDate,
            'days_left'   => $daysLeft,
            'issuer'      => $cert['issuer']['O'] ?? 'Unknown',
            'subject'     => $cert['subject']['CN'] ?? $host,
        ];
    }

    private function checkHttpsRedirect(string $host): bool
    {
        $ch = curl_init("http://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        return in_array($httpCode, [301, 302, 307, 308]) && str_starts_with($location, 'https://');
    }

    private function checkHsts(string $host): array
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (preg_match('/strict-transport-security:\s*(.+)/i', $response, $matches)) {
            $value = trim($matches[1]);
            preg_match('/max-age=(\d+)/i', $value, $ageMatches);
            return [
                'present'  => true,
                'value'    => $value,
                'max_age'  => isset($ageMatches[1]) ? (int) $ageMatches[1] : 0,
            ];
        }

        return ['present' => false, 'value' => null, 'max_age' => 0];
    }
}
