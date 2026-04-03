<?php

namespace App\Services\Scanners;

class InputReflectionScanner
{
    use HasSafeCall;

    private const TIMEOUT = 8;

    public function scan(string $host): array
    {
        $checks = [];
        $score  = 0;
        $max    = 30;

        // Test 1: Query parameter reflection in HTML
        $canary = 'wca' . bin2hex(random_bytes(4));
        $params = ['q', 'search', 'query', 'keyword', 'name', 'id'];
        $reflected = false;

        foreach ($params as $param) {
            $body = $this->fetchBody("https://{$host}/?{$param}={$canary}");
            if ($body && stripos($body, $canary) !== false) {
                // Check if it's reflected in a dangerous context (inside tag attributes, scripts)
                $inAttribute = preg_match('/["\']' . preg_quote($canary) . '/i', $body);
                $inScript = preg_match('/<script[^>]*>.*?' . preg_quote($canary) . '/si', $body);

                $checks[] = [
                    'id'             => 'reflection_param_' . $param,
                    'label'          => "Parameter '{$param}' reflected in response",
                    'status'         => ($inAttribute || $inScript) ? 'fail' : 'warn',
                    'description'    => $inScript
                        ? "The parameter '{$param}' is reflected inside a script block — high XSS risk."
                        : ($inAttribute
                            ? "The parameter '{$param}' is reflected inside an HTML attribute — potential XSS vector."
                            : "The parameter '{$param}' is reflected in the HTML body. This may indicate insufficient output encoding."),
                    'recommendation' => 'Encode all user input before rendering in HTML. Use context-aware output encoding (HTML entities, JavaScript escaping, URL encoding).',
                ];

                if ($inScript) {
                    // Critical
                } elseif ($inAttribute) {
                    $score += 5;
                } else {
                    $score += 7;
                }
                $reflected = true;
                break; // One finding is enough
            }
        }

        if (! $reflected) {
            $score += 10;
            $checks[] = [
                'id'          => 'reflection_none',
                'label'       => 'No input reflection detected',
                'status'      => 'pass',
                'description' => 'URL parameters are not reflected in the HTML response, reducing XSS risk.',
            ];
        }

        // Test 2: Error message reflection
        $errorCanary = 'wcaerr' . bin2hex(random_bytes(3));
        $errorBody = $this->fetchBody("https://{$host}/" . $errorCanary);
        $errorReflected = $errorBody && stripos($errorBody, $errorCanary) !== false;

        if ($errorReflected) {
            $checks[] = [
                'id'             => 'reflection_error_path',
                'label'          => 'URL path reflected in error page',
                'status'         => 'warn',
                'description'    => 'The requested URL path is reflected in the error page response. This could be exploited for XSS if output is not properly encoded.',
                'recommendation' => 'Ensure error pages use proper HTML encoding for all dynamic content including the requested URL.',
            ];
            $score += 5;
        } else {
            $score += 10;
            $checks[] = [
                'id'          => 'reflection_error_safe',
                'label'       => 'Error pages do not reflect URL',
                'status'      => 'pass',
                'description' => 'Error pages do not reflect the requested URL in the response body.',
            ];
        }

        // Test 3: Form action validation
        $formBody = $this->fetchBody("https://{$host}/");
        $formIssue = false;
        if ($formBody) {
            preg_match_all('/<form[^>]+action=["\']([^"\']*)["\'][^>]*>/i', $formBody, $formMatches);
            foreach ($formMatches[1] ?? [] as $action) {
                if (preg_match('/^https?:\/\//i', $action) && stripos($action, $host) === false) {
                    $formIssue = true;
                    break;
                }
            }
        }

        if ($formIssue) {
            $checks[] = [
                'id'             => 'reflection_form_external',
                'label'          => 'Form submits to external domain',
                'status'         => 'warn',
                'description'    => 'A form on the page submits data to an external domain. This could indicate a phishing form or data exfiltration.',
                'recommendation' => 'Verify that all form actions point to trusted domains. Avoid submitting sensitive data to third-party URLs.',
            ];
            $score += 5;
        } else {
            $score += 10;
            $checks[] = [
                'id'          => 'reflection_forms_safe',
                'label'       => 'Forms submit to same origin',
                'status'      => 'pass',
                'description' => 'All detected forms submit to the same domain or use relative URLs.',
            ];
        }

        return [
            'category' => 'Input Reflection',
            'icon'     => 'cursor-arrow-rays',
            'score'    => $max > 0 ? (int) round(($score / $max) * 100) : 100,
            'checks'   => $checks,
        ];
    }

    private function fetchBody(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_RANGE          => '0-32767',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($body && $code >= 200 && $code < 500) ? $body : null;
    }
}
