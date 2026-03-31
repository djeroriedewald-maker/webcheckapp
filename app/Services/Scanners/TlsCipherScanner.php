<?php

namespace App\Services\Scanners;

class TlsCipherScanner
{
    use HasSafeCall;
    private const TIMEOUT = 3;

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        // Curl-based version constants (PHP 7.3+ / libcurl 7.54+).
        // Combining min + max forces an exact TLS version negotiation.
        // Falls back to null (skip check) if constants are unavailable.
        $tls13Version = defined('CURL_SSLVERSION_TLSv1_3') && defined('CURL_SSLVERSION_MAX_TLSv1_3')
            ? (CURL_SSLVERSION_TLSv1_3 | CURL_SSLVERSION_MAX_TLSv1_3) : null;
        $tls12Version = defined('CURL_SSLVERSION_TLSv1_2') && defined('CURL_SSLVERSION_MAX_TLSv1_2')
            ? (CURL_SSLVERSION_TLSv1_2 | CURL_SSLVERSION_MAX_TLSv1_2) : null;
        $tls11Version = defined('CURL_SSLVERSION_TLSv1_1') && defined('CURL_SSLVERSION_MAX_TLSv1_1')
            ? (CURL_SSLVERSION_TLSv1_1 | CURL_SSLVERSION_MAX_TLSv1_1) : null;
        $tls10Version = defined('CURL_SSLVERSION_TLSv1_0') && defined('CURL_SSLVERSION_MAX_TLSv1_0')
            ? (CURL_SSLVERSION_TLSv1_0 | CURL_SSLVERSION_MAX_TLSv1_0) : null;

        // 1. TLS 1.3 support
        if ($tls13Version !== null) {
            $tls13 = $this->safe(fn() => $this->testTlsVersion($host, $tls13Version), null);
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
        }

        // 2. TLS 1.2 support (required minimum)
        if ($tls12Version !== null) {
            $maxScore += 30;
            $tls12 = $this->safe(fn() => $this->testTlsVersion($host, $tls12Version), null);
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
        }

        // 3. TLS 1.1 should be disabled
        if ($tls11Version !== null) {
            $maxScore += 25;
            $tls11 = $this->safe(fn() => $this->testTlsVersion($host, $tls11Version), null);
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
        }

        // 4. TLS 1.0 should be disabled
        if ($tls10Version !== null) {
            $maxScore += 25;
            $tls10 = $this->safe(fn() => $this->testTlsVersion($host, $tls10Version), null);
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
        }

        // 5. Perfect Forward Secrecy (ECDHE cipher suites)
        $maxScore += 25;
        try {
            $pfs = $this->testPerfectForwardSecrecy($host);
        } catch (\Throwable) {
            $pfs = null;
        }
        if ($pfs === true) {
            $score += 25;
            $checks[] = [
                'id'          => 'tls_pfs',
                'label'       => 'Perfect Forward Secrecy (PFS)',
                'status'      => 'pass',
                'description' => 'Server supports ECDHE cipher suites — session keys are ephemeral and past sessions cannot be decrypted if the private key is ever compromised.',
            ];
        } elseif ($pfs === false) {
            $checks[] = [
                'id'             => 'tls_pfs',
                'label'          => 'Perfect Forward Secrecy (PFS)',
                'status'         => 'warn',
                'description'    => 'Server does not appear to support ECDHE cipher suites for Perfect Forward Secrecy. Recorded traffic could be decrypted if the server private key is compromised.',
                'recommendation' => "Enable ECDHE cipher suites in your server config:\nNginx: ssl_ciphers ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;\nApache: SSLCipherSuite ECDHE+AESGCM:ECDHE+AES",
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
     * Test whether the server accepts a specific TLS version using curl.
     * curl respects CURLOPT_TIMEOUT reliably; stream_socket_client does not for SSL.
     *
     * @param  int  $curlSslVersion  Combined min+max curl SSL version constant.
     * @return bool|null  true = accepted, false = rejected, null = inconclusive
     */
    private function testTlsVersion(string $host, int $curlSslVersion): ?bool
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSLVERSION     => $curlSslVersion,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        curl_exec($ch);
        $errno = curl_errno($ch);
        $error = strtolower(curl_error($ch));
        curl_close($ch);

        if ($errno === 0) {
            return true;
        }

        // SSL/TLS handshake failure = version rejected by the server
        if (str_contains($error, 'ssl') || str_contains($error, 'tls')
            || str_contains($error, 'handshake') || $errno === 35) {
            return false;
        }

        return null; // network error — inconclusive
    }

    /**
     * Test whether the server accepts ECDHE cipher suites (Perfect Forward Secrecy).
     * Returns true if PFS is supported, false if not, null if inconclusive.
     */
    private function testPerfectForwardSecrecy(string $host): ?bool
    {
        $ecdheCiphers = 'ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256';

        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_NOBODY          => true,
            CURLOPT_TIMEOUT         => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT  => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => 0,
            CURLOPT_SSL_CIPHER_LIST => $ecdheCiphers,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno === 0) {
            return true;
        }

        // SSL/handshake error means ECDHE ciphers rejected
        $errMsg = strtolower(curl_strerror($errno));
        if (str_contains($errMsg, 'ssl') || str_contains($errMsg, 'handshake') || $errno === 35) {
            return false;
        }

        return null; // network error — inconclusive
    }
}
