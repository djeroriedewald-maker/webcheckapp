<?php

namespace App\Services\Scanners;

class SessionSecurityScanner
{
    use HasSafeCall;

    private const TIMEOUT = 6;

    public function scan(string $host): array
    {
        $checks = [];
        $score  = 0;
        $max    = 40;

        // Fetch response headers to analyze cookies
        $ch = curl_init("https://{$host}/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER         => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        preg_match_all('/^Set-Cookie:\s*(.+)$/mi', $response ?? '', $matches);
        $cookies = $matches[1] ?? [];

        if (empty($cookies)) {
            $score += $max;
            $checks[] = [
                'id'          => 'session_no_cookies',
                'label'       => 'No session cookies detected',
                'status'      => 'pass',
                'description' => 'The homepage does not set any cookies, which is good for privacy. Session cookies will be set upon authentication.',
            ];
            return [
                'category' => 'Session Security',
                'icon'     => 'finger-print',
                'score'    => 100,
                'checks'   => $checks,
            ];
        }

        $sessionCookies = [];
        foreach ($cookies as $cookie) {
            $name = strtok($cookie, '=');
            if (preg_match('/sess|token|auth|sid|laravel|PHPSESSID|JSESSIONID|ASP\.NET/i', $name)) {
                $sessionCookies[$name] = strtolower($cookie);
            }
        }

        // Check 1: Secure flag
        $allSecure = true;
        foreach ($sessionCookies as $name => $cookie) {
            if (strpos($cookie, 'secure') === false) {
                $allSecure = false;
                break;
            }
        }
        if ($allSecure) {
            $score += 10;
            $checks[] = [
                'id'          => 'session_secure',
                'label'       => 'Session cookies have Secure flag',
                'status'      => 'pass',
                'description' => 'All session cookies include the Secure flag, ensuring they are only sent over HTTPS.',
            ];
        } else {
            $checks[] = [
                'id'             => 'session_no_secure',
                'label'          => 'Session cookies missing Secure flag',
                'status'         => 'fail',
                'description'    => 'One or more session cookies do not have the Secure flag, allowing them to be sent over unencrypted HTTP.',
                'recommendation' => 'Set the Secure flag on all session cookies to prevent transmission over HTTP.',
            ];
        }

        // Check 2: HttpOnly flag
        $allHttpOnly = true;
        foreach ($sessionCookies as $name => $cookie) {
            if (strpos($cookie, 'httponly') === false) {
                $allHttpOnly = false;
                break;
            }
        }
        if ($allHttpOnly) {
            $score += 10;
            $checks[] = [
                'id'          => 'session_httponly',
                'label'       => 'Session cookies have HttpOnly flag',
                'status'      => 'pass',
                'description' => 'All session cookies include the HttpOnly flag, preventing JavaScript access.',
            ];
        } else {
            $checks[] = [
                'id'             => 'session_no_httponly',
                'label'          => 'Session cookies missing HttpOnly flag',
                'status'         => 'fail',
                'description'    => 'One or more session cookies do not have the HttpOnly flag, making them accessible via JavaScript (XSS risk).',
                'recommendation' => 'Set the HttpOnly flag on all session cookies to prevent access from client-side scripts.',
            ];
        }

        // Check 3: SameSite flag
        $allSameSite = true;
        foreach ($sessionCookies as $name => $cookie) {
            if (! preg_match('/samesite\s*=\s*(strict|lax|none)/i', $cookie)) {
                $allSameSite = false;
                break;
            }
        }
        if ($allSameSite) {
            $score += 10;
            $checks[] = [
                'id'          => 'session_samesite',
                'label'       => 'Session cookies have SameSite flag',
                'status'      => 'pass',
                'description' => 'All session cookies include a SameSite attribute, reducing CSRF risk.',
            ];
        } else {
            $checks[] = [
                'id'             => 'session_no_samesite',
                'label'          => 'Session cookies missing SameSite flag',
                'status'         => 'warn',
                'description'    => 'One or more session cookies do not have a SameSite attribute. Browsers default to Lax, but explicit setting is recommended.',
                'recommendation' => 'Set SameSite=Lax or SameSite=Strict on all session cookies.',
            ];
        }

        // Check 4: Cookie prefix (__Host- or __Secure-)
        $hasPrefix = false;
        foreach (array_keys($sessionCookies) as $name) {
            if (str_starts_with($name, '__Host-') || str_starts_with($name, '__Secure-')) {
                $hasPrefix = true;
                break;
            }
        }
        if ($hasPrefix) {
            $score += 10;
            $checks[] = [
                'id'          => 'session_prefix',
                'label'       => 'Cookie prefix used (__Host- or __Secure-)',
                'status'      => 'pass',
                'description' => 'Session cookies use the __Host- or __Secure- prefix for additional protection against cookie injection.',
            ];
        } else {
            $checks[] = [
                'id'             => 'session_no_prefix',
                'label'          => 'No cookie prefix used',
                'status'         => 'warn',
                'description'    => 'Session cookies do not use the __Host- or __Secure- prefix. These prefixes provide additional protection against cookie overwriting.',
                'recommendation' => 'Consider using __Host- prefix for session cookies (requires Secure flag, no Domain, Path=/).',
            ];
        }

        if (empty($sessionCookies)) {
            $score += $max;
            $checks[] = [
                'id'          => 'session_no_session_cookies',
                'label'       => 'No session cookies on homepage',
                'status'      => 'pass',
                'description' => 'The homepage does not set session cookies. Session management is likely handled on authenticated pages only.',
            ];
        }

        return [
            'category' => 'Session Security',
            'icon'     => 'finger-print',
            'score'    => $max > 0 ? (int) round(($score / $max) * 100) : 100,
            'checks'   => $checks,
        ];
    }
}
