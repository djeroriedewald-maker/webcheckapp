<?php

namespace App\Services\Scanners;

class ErrorDisclosureScanner
{
    use HasSafeCall;

    private const TIMEOUT = 6;

    private const DEBUG_FINGERPRINTS = [
        'Whoops! There was an error'      => 'Laravel/Whoops debug page',
        'vendor/laravel/framework'        => 'Laravel stack trace',
        'vendor/symfony'                  => 'Symfony stack trace',
        'Traceback (most recent call last)' => 'Python stack trace',
        'Exception in thread'             => 'Java stack trace',
        'at org.apache.'                  => 'Apache/Tomcat stack trace',
        'Microsoft .NET Framework'        => '.NET error page',
        'Server Error in'                 => 'ASP.NET error page',
        'SQLSTATE['                       => 'Database error exposed',
        'mysql_'                          => 'MySQL error exposed',
        'pg_query'                        => 'PostgreSQL error exposed',
        'ORA-'                            => 'Oracle error exposed',
        'DOCUMENT_ROOT'                   => 'Server path disclosure',
        'phpinfo()'                       => 'PHP info page',
        'Xdebug'                          => 'Xdebug active in production',
        'APP_DEBUG'                       => 'Debug environment variable exposed',
    ];

    public function scan(string $host): array
    {
        $checks = [];
        $score  = 0;
        $max    = 40;

        // Test 1: 404 error page info disclosure
        $notFoundBody = $this->fetchBody("https://{$host}/___wca_error_test_" . time());
        $notFoundIssues = $this->checkForDisclosure($notFoundBody);

        if (empty($notFoundIssues)) {
            $score += 15;
            $checks[] = [
                'id'          => 'error_404_safe',
                'label'       => '404 error page is clean',
                'status'      => 'pass',
                'description' => 'The 404 error page does not expose internal server details or stack traces.',
            ];
        } else {
            $checks[] = [
                'id'             => 'error_404_leak',
                'label'          => '404 page exposes internal information',
                'status'         => 'fail',
                'description'    => 'The 404 error page reveals: ' . implode(', ', $notFoundIssues) . '.',
                'recommendation' => 'Configure custom error pages that do not expose stack traces, file paths, or framework details.',
            ];
        }

        // Test 2: trigger 500 via malformed input
        $serverErrorBody = $this->fetchBody("https://{$host}/api/%00/../../etc/passwd");
        $serverErrorIssues = $this->checkForDisclosure($serverErrorBody);

        if (empty($serverErrorIssues)) {
            $score += 15;
            $checks[] = [
                'id'          => 'error_500_safe',
                'label'       => 'Server error pages are clean',
                'status'      => 'pass',
                'description' => 'Server error responses do not expose internal details.',
            ];
        } else {
            $checks[] = [
                'id'             => 'error_500_leak',
                'label'          => 'Server errors expose internal information',
                'status'         => 'fail',
                'description'    => 'Server error responses reveal: ' . implode(', ', $serverErrorIssues) . '.',
                'recommendation' => 'Disable debug mode in production (APP_DEBUG=false). Configure generic error pages.',
            ];
        }

        // Test 3: PHP/framework version in headers
        $versionLeak = $this->safe(fn() => $this->checkVersionHeaders($host), false);
        if (! $versionLeak) {
            $score += 10;
            $checks[] = [
                'id'          => 'error_version_hidden',
                'label'       => 'No version information in error responses',
                'status'      => 'pass',
                'description' => 'Error responses do not reveal PHP version or framework version in headers.',
            ];
        } else {
            $checks[] = [
                'id'             => 'error_version_leak',
                'label'          => 'Version information in error headers',
                'status'         => 'warn',
                'description'    => 'Error response headers expose the X-Powered-By or PHP version, helping attackers identify vulnerable versions.',
                'recommendation' => 'Remove X-Powered-By header. Set expose_php = Off in php.ini.',
            ];
        }

        return [
            'category' => 'Error Disclosure',
            'icon'     => 'exclamation-triangle',
            'score'    => $max > 0 ? (int) round(($score / $max) * 100) : 100,
            'checks'   => $checks,
        ];
    }

    private function checkForDisclosure(?string $body): array
    {
        if (! $body) return [];

        $found = [];
        foreach (self::DEBUG_FINGERPRINTS as $pattern => $label) {
            if (stripos($body, $pattern) !== false) {
                $found[] = $label;
            }
        }
        return array_unique($found);
    }

    private function checkVersionHeaders(string $host): bool
    {
        $ch = curl_init("https://{$host}/___nonexistent_" . time());
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (! $response) return false;

        return (bool) preg_match('/^X-Powered-By:/mi', $response);
    }

    private function fetchBody(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RANGE          => '0-16383',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        return $body ?: null;
    }
}
