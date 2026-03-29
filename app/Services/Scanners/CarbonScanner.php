<?php

namespace App\Services\Scanners;

class CarbonScanner
{
    use HasSafeCall;
    private const TIMEOUT = 8;

    public function scan(string $host): array
    {
        $checks = [];

        // Strip www for apex domain lookup
        $apexDomain = preg_replace('/^www\./i', '', $host);

        // 1. Green Web Foundation check
        $greenData = $this->safe(fn() => $this->checkGreenHosting($apexDomain), null);
        if ($greenData === null) {
            $checks[] = [
                'id'          => 'carbon_green',
                'label'       => 'Green hosting',
                'status'      => 'warn',
                'description' => 'Could not verify green hosting status (Green Web Foundation API unavailable).',
            ];
        } elseif (! empty($greenData['green'])) {
            $hostedBy = $greenData['hostedby'] ?? 'Unknown provider';
            $checks[] = [
                'id'          => 'carbon_green',
                'label'       => 'Green hosting',
                'status'      => 'pass',
                'description' => "This website is hosted on green infrastructure ({$hostedBy}). The provider uses renewable energy or verified carbon offsets.",
            ];
        } else {
            $checks[] = [
                'id'             => 'carbon_green',
                'label'          => 'Green hosting',
                'status'         => 'warn',
                'description'    => 'This website is not confirmed to be hosted on green infrastructure.',
                'recommendation' => 'Consider migrating to a green hosting provider. Find certified providers at thegreenwebfoundation.org.',
            ];
        }

        // 2. HTTP/2 or HTTP/3 (reduces connection overhead = less energy per request)
        $httpVersion = $this->safe(fn() => $this->getHttpVersion($host), null);
        if ($httpVersion !== null) {
            if ($httpVersion >= 2) {
                $label = $httpVersion >= 3 ? 'HTTP/3' : 'HTTP/2';
                $checks[] = [
                    'id'          => 'carbon_http2',
                    'label'       => "Modern HTTP protocol ({$label})",
                    'status'      => 'pass',
                    'description' => "{$label} is in use — reduces connection overhead and improves energy efficiency.",
                ];
            } else {
                $checks[] = [
                    'id'             => 'carbon_http2',
                    'label'          => 'Modern HTTP protocol',
                    'status'         => 'warn',
                    'description'    => 'Only HTTP/1.1 detected. Upgrading to HTTP/2 reduces connection overhead and energy use.',
                    'recommendation' => 'Enable HTTP/2 on your web server (Nginx: http2 on; Apache: LoadModule http2_module).',
                ];
            }
        }

        // 3. Response compression (less data = less energy)
        $compression = $this->safe(fn() => $this->checkCompression($host), null);
        if ($compression === 'brotli') {
            $checks[] = [
                'id'          => 'carbon_compression',
                'label'       => 'Brotli compression enabled',
                'status'      => 'pass',
                'description' => 'Brotli compression is active — typically 15–25% smaller than Gzip, reducing bandwidth and energy consumption.',
            ];
        } elseif ($compression === 'gzip') {
            $checks[] = [
                'id'          => 'carbon_compression',
                'label'       => 'Gzip compression enabled',
                'status'      => 'pass',
                'description' => 'Gzip compression is active — reduces data transfer size and energy use.',
            ];
        } else {
            $checks[] = [
                'id'             => 'carbon_compression',
                'label'          => 'Response compression',
                'status'         => 'warn',
                'description'    => 'No response compression (Gzip/Brotli) detected.',
                'recommendation' => 'Enable Brotli or Gzip compression to reduce data transfer and energy consumption.',
            ];
        }

        return [
            'category' => 'Carbon & Sustainability',
            'icon'     => 'globe-alt',
            'score'    => null,
            'checks'   => $checks,
        ];
    }

    private function checkGreenHosting(string $domain): ?array
    {
        $ch = curl_init("https://api.thegreenwebfoundation.org/greencheck/{$domain}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'WebCheckApp/1.0 (security scanner)',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || ! $body) {
            return null;
        }

        $data = @json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    private function getHttpVersion(string $host): ?int
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        curl_exec($ch);
        // CURLINFO_HTTP_VERSION returns: 10 = HTTP/1.0, 11 = HTTP/1.1, 20 = HTTP/2, 30 = HTTP/3
        $version = curl_getinfo($ch, CURLINFO_HTTP_VERSION);
        curl_close($ch);

        if ($version === 0) {
            return null;
        }
        if ($version >= 30) {
            return 3;
        }
        if ($version >= 20) {
            return 2;
        }
        return 1;
    }

    private function checkCompression(string $host): ?string
    {
        $lastHeaders = [];
        $ch          = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => 'br, gzip, deflate',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$lastHeaders) {
            if (preg_match('/^HTTP\//i', $header)) {
                $lastHeaders = [];
            } elseif (str_contains($header, ':')) {
                [$k, $v] = explode(':', $header, 2);
                $lastHeaders[strtolower(trim($k))] = trim($v);
            }
            return strlen($header);
        });
        curl_exec($ch);
        curl_close($ch);

        $encoding = $lastHeaders['content-encoding'] ?? null;
        if (! $encoding) {
            return null;
        }
        if (str_contains($encoding, 'br')) {
            return 'brotli';
        }
        if (str_contains($encoding, 'gzip')) {
            return 'gzip';
        }
        return null;
    }

}
