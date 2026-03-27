<?php

namespace App\Services\Scanners;

class HeadersScanner
{
    private array $headers = [];

    public function scan(string $host): array
    {
        $this->headers = $this->fetchHeaders($host);
        $checks = [];
        $score = 0;
        $maxScore = 0;

        $headerChecks = [
            [
                'id'             => 'header_csp',
                'label'          => 'Content-Security-Policy',
                'header'         => 'content-security-policy',
                'weight'         => 20,
                'recommendation' => 'Add a Content-Security-Policy header to prevent XSS and data injection attacks.',
            ],
            [
                'id'             => 'header_xframe',
                'label'          => 'X-Frame-Options',
                'header'         => 'x-frame-options',
                'weight'         => 15,
                'recommendation' => 'Add X-Frame-Options: DENY or SAMEORIGIN to prevent clickjacking attacks.',
            ],
            [
                'id'             => 'header_xcontent',
                'label'          => 'X-Content-Type-Options',
                'header'         => 'x-content-type-options',
                'weight'         => 15,
                'expected'       => 'nosniff',
                'recommendation' => 'Add X-Content-Type-Options: nosniff to prevent MIME-type sniffing.',
            ],
            [
                'id'             => 'header_referrer',
                'label'          => 'Referrer-Policy',
                'header'         => 'referrer-policy',
                'weight'         => 15,
                'recommendation' => 'Add a Referrer-Policy header to control how much referrer information is shared.',
            ],
            [
                'id'             => 'header_permissions',
                'label'          => 'Permissions-Policy',
                'header'         => 'permissions-policy',
                'weight'         => 15,
                'recommendation' => 'Add a Permissions-Policy header to control which browser features can be used.',
            ],
            [
                'id'             => 'header_xss',
                'label'          => 'X-XSS-Protection',
                'header'         => 'x-xss-protection',
                'weight'         => 10,
                'recommendation' => 'Add X-XSS-Protection: 1; mode=block (legacy browsers). Modern apps should rely on CSP.',
            ],
        ];

        // Check for server info disclosure
        $maxScore += 10;
        $serverHeader = $this->headers['server'] ?? null;
        if (! $serverHeader || ! preg_match('/(\d+\.\d+)/', $serverHeader)) {
            $score += 10;
            $checks[] = [
                'id'          => 'header_server',
                'label'       => 'Server version not disclosed',
                'status'      => 'pass',
                'description' => 'Server header does not expose version information.',
            ];
        } else {
            $checks[] = [
                'id'          => 'header_server',
                'label'       => 'Server version not disclosed',
                'status'      => 'warn',
                'description' => "Server header exposes version: \"{$serverHeader}\".",
                'recommendation' => 'Configure your web server to hide its version number.',
            ];
        }

        foreach ($headerChecks as $check) {
            $maxScore += $check['weight'];
            $value = $this->headers[$check['header']] ?? null;

            if ($value !== null) {
                $score += $check['weight'];
                $checks[] = [
                    'id'          => $check['id'],
                    'label'       => $check['label'],
                    'status'      => 'pass',
                    'description' => "Header found: \"{$value}\".",
                ];
            } else {
                $checks[] = [
                    'id'             => $check['id'],
                    'label'          => $check['label'],
                    'status'         => 'fail',
                    'description'    => "The {$check['label']} header is missing.",
                    'recommendation' => $check['recommendation'],
                ];
            }
        }

        return [
            'category' => 'Security Headers',
            'icon'     => 'document-check',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function fetchHeaders(string $host): array
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => true,
            CURLOPT_NOBODY          => true,
            CURLOPT_TIMEOUT         => 5,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 3,
            CURLOPT_USERAGENT       => 'WebCheckApp/1.0 Security Scanner',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $headers = [];
        foreach (explode("\r\n", $response) as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }

        return $headers;
    }
}
