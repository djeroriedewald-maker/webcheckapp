<?php

namespace App\Services\Scanners;

class HttpMethodScanner
{
    use HasSafeCall;

    private const TIMEOUT = 5;

    private const DANGEROUS_METHODS = ['PUT', 'DELETE', 'TRACE', 'CONNECT', 'PATCH'];

    public function scan(string $host): array
    {
        $checks = [];
        $score  = 0;
        $max    = 30;

        // Check 1: OPTIONS request to discover allowed methods
        $allowedMethods = $this->safe(fn() => $this->getOptionsAllow($host), []);

        if (empty($allowedMethods)) {
            $score += 10;
            $checks[] = [
                'id'          => 'http_options_hidden',
                'label'       => 'OPTIONS method does not expose allowed methods',
                'status'      => 'pass',
                'description' => 'The server does not respond to OPTIONS requests with an Allow header, hiding available HTTP methods.',
            ];
        } else {
            $dangerous = array_intersect(self::DANGEROUS_METHODS, $allowedMethods);
            if (empty($dangerous)) {
                $score += 10;
                $checks[] = [
                    'id'          => 'http_options_safe',
                    'label'       => 'Only safe HTTP methods allowed',
                    'status'      => 'pass',
                    'description' => 'Allowed methods: ' . implode(', ', $allowedMethods) . '. No dangerous methods detected.',
                ];
            } else {
                $checks[] = [
                    'id'             => 'http_options_dangerous',
                    'label'          => 'Dangerous HTTP methods allowed',
                    'status'         => 'warn',
                    'description'    => 'The server advertises these methods: ' . implode(', ', $allowedMethods) . '. Dangerous methods detected: ' . implode(', ', $dangerous) . '.',
                    'recommendation' => 'Disable unnecessary HTTP methods (PUT, DELETE, TRACE) on the web server unless specifically needed by the application.',
                ];
            }
        }

        // Check 2: Test TRACE method (XST vulnerability)
        $traceEnabled = $this->safe(fn() => $this->testTrace($host), false);
        if (! $traceEnabled) {
            $score += 10;
            $checks[] = [
                'id'          => 'http_trace_disabled',
                'label'       => 'TRACE method is disabled',
                'status'      => 'pass',
                'description' => 'The TRACE method is disabled, preventing Cross-Site Tracing (XST) attacks.',
            ];
        } else {
            $checks[] = [
                'id'             => 'http_trace_enabled',
                'label'          => 'TRACE method is enabled',
                'status'         => 'fail',
                'description'    => 'The TRACE method is enabled. This can be exploited for Cross-Site Tracing (XST) attacks to steal credentials.',
                'recommendation' => 'Disable the TRACE method in your web server configuration. Apache: TraceEnable Off. Nginx: deny TRACE by default.',
            ];
        }

        // Check 3: Test PUT method (file upload)
        $putEnabled = $this->safe(fn() => $this->testPut($host), false);
        if (! $putEnabled) {
            $score += 10;
            $checks[] = [
                'id'          => 'http_put_disabled',
                'label'       => 'PUT method rejects arbitrary uploads',
                'status'      => 'pass',
                'description' => 'The PUT method does not accept arbitrary file uploads to the web root.',
            ];
        } else {
            $checks[] = [
                'id'             => 'http_put_enabled',
                'label'          => 'PUT method may accept file uploads',
                'status'         => 'fail',
                'description'    => 'The server responded with a success code to a PUT request, potentially allowing unauthorized file uploads.',
                'recommendation' => 'Disable PUT method on the web server unless it is explicitly required by the application with proper authentication.',
            ];
        }

        return [
            'category' => 'HTTP Methods',
            'icon'     => 'arrows-right-left',
            'score'    => $max > 0 ? (int) round(($score / $max) * 100) : 100,
            'checks'   => $checks,
        ];
    }

    private function getOptionsAllow(string $host): array
    {
        $ch = curl_init("https://{$host}/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'OPTIONS',
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (! $response) return [];

        if (preg_match('/^Allow:\s*(.+)$/mi', $response, $match)) {
            return array_map('trim', explode(',', strtoupper($match[1])));
        }
        return [];
    }

    private function testTrace(string $host): bool
    {
        $ch = curl_init("https://{$host}/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'TRACE',
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // TRACE echoes back the request — check for message/http content type
        return $code === 200 && $body && stripos($body, 'TRACE / HTTP') !== false;
    }

    private function testPut(string $host): bool
    {
        $ch = curl_init("https://{$host}/___wca_put_test_" . time() . ".txt");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => 'test',
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return in_array($code, [200, 201, 204]);
    }
}
