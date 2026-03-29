<?php

namespace App\Services\Scanners;

class TlsCipherScanner
{
    private const TIMEOUT = 3;

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        // 1. TLS 1.3 support (PHP 7.4+, OpenSSL 1.1.1+)
        $tls13Const = defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : null;
        $tls13      = $tls13Const !== null ? $this->testTlsVersion($host, $tls13Const) : null;

        if ($tls13 !== null) {
            $maxScore += 20;
            if ($tls13) {
                $score += 20;
                $checks[] = [
                    'id'          => 'tls_v13',
                    'label'       => 'TLS 1.3 supported',
                    'status'      => 'pass',
                    'description' => 'TLS 1.3 is supported — the most secure and performant TLS version.',
                ];
            } else {
                $checks[] = [
                    'id'             => 'tls_v13',
                    'label'          => 'TLS 1.3 supported',
                    'status'         => 'warn',
                    'description'    => 'TLS 1.3 is not supported.',
                    'recommendation' => 'Enable TLS 1.3 on your web server for better security and performance.',
                ];
            }
        }

        // 2. TLS 1.2 support (required minimum)
        $maxScore += 30;
        $tls12 = $this->testTlsVersion($host, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
        if ($tls12 === true) {
            $score += 30;
            $checks[] = [
                'id'          => 'tls_v12',
                'label'       => 'TLS 1.2 supported',
                'status'      => 'pass',
                'description' => 'TLS 1.2 is supported — required as a minimum for modern compatibility.',
            ];
        } elseif ($tls12 === false) {
            $checks[] = [
                'id'             => 'tls_v12',
                'label'          => 'TLS 1.2 supported',
                'status'         => 'fail',
                'description'    => 'TLS 1.2 is not supported. This breaks compatibility with most modern clients.',
                'recommendation' => 'Enable TLS 1.2 — it is the current minimum acceptable version.',
            ];
        }

        // 3. TLS 1.1 should be disabled
        $maxScore += 25;
        $tls11 = $this->testTlsVersion($host, STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT);
        if ($tls11 === false) {
            $score += 25;
            $checks[] = [
                'id'          => 'tls_v11',
                'label'       => 'TLS 1.1 disabled',
                'status'      => 'pass',
                'description' => 'TLS 1.1 is disabled — this deprecated version is correctly rejected.',
            ];
        } elseif ($tls11 === true) {
            $checks[] = [
                'id'             => 'tls_v11',
                'label'          => 'TLS 1.1 disabled',
                'status'         => 'fail',
                'description'    => 'TLS 1.1 is still accepted. It was deprecated in RFC 8996 (2021) and has known weaknesses.',
                'recommendation' => 'Disable TLS 1.1 in your server config. Only TLS 1.2 and 1.3 should be accepted.',
            ];
        }

        // 4. TLS 1.0 should be disabled
        $maxScore += 25;
        $tls10 = $this->testTlsVersion($host, STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT);
        if ($tls10 === false) {
            $score += 25;
            $checks[] = [
                'id'          => 'tls_v10',
                'label'       => 'TLS 1.0 disabled',
                'status'      => 'pass',
                'description' => 'TLS 1.0 is disabled — this obsolete version is correctly rejected.',
            ];
        } elseif ($tls10 === true) {
            $checks[] = [
                'id'             => 'tls_v10',
                'label'          => 'TLS 1.0 disabled',
                'status'         => 'fail',
                'description'    => 'TLS 1.0 is still accepted. It is vulnerable to POODLE, BEAST, and other attacks.',
                'recommendation' => 'Disable TLS 1.0 immediately. PCI DSS compliance has required this since June 2018.',
            ];
        }

        return [
            'category' => 'TLS / Cipher',
            'icon'     => 'shield-check',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    /**
     * Returns true if the TLS version is accepted by the server,
     * false if explicitly rejected, null if result is inconclusive.
     */
    private function testTlsVersion(string $host, int $method): ?bool
    {
        $ctx = stream_context_create([
            'ssl' => [
                'crypto_method'    => $method,
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $sock = @stream_socket_client(
            "ssl://{$host}:443",
            $errno,
            $errstr,
            self::TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if ($sock) {
            fclose($sock);
            return true;
        }

        $errLower = strtolower((string) $errstr);

        // Only return false for SSL/TLS-specific failures
        if (str_contains($errLower, 'ssl') || str_contains($errLower, 'tls')
            || str_contains($errLower, 'handshake') || str_contains($errLower, 'protocol')) {
            return false;
        }

        // Network-level error (e.g. port closed) — inconclusive
        return null;
    }
}
