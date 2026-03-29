<?php

namespace App\Services\Scanners;

class HeadersScanner
{
    use HasSafeCall;
    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        // Use HEADERFUNCTION so we only parse headers from the FINAL response (not intermediate redirects)
        $headers        = $this->safe(fn() => $this->fetchHeaders($host), []);
        $csp            = $headers['content-security-policy'] ?? null;
        $cspReportOnly  = $headers['content-security-policy-report-only'] ?? null;

        // --- Check: Server version disclosure ---
        $maxScore += 10;
        $server = $headers['server'] ?? null;
        if (! $server || ! preg_match('/\d+\.\d+/', $server)) {
            $score += 10;
            $checks[] = [
                'id'          => 'header_server',
                'label'       => 'Server version not disclosed',
                'status'      => 'pass',
                'description' => 'The Server header does not expose version information.',
            ];
        } else {
            $score += 5; // partial credit — header present but leaks version number
            $checks[] = [
                'id'             => 'header_server',
                'label'          => 'Server version not disclosed',
                'status'         => 'warn',
                'description'    => "Server header reveals version: \"{$server}\".",
                'recommendation' => 'Configure your web server to suppress the version number from the Server header.',
            ];
        }

        // --- Check: Content-Security-Policy ---
        $maxScore += 20;
        if ($csp !== null) {
            // Quality check: unsafe-inline / unsafe-eval in script context defeats XSS protection.
            // Check script-src first; fall back to default-src if no explicit script-src exists.
            $weakDirectives = [];
            $hasScriptSrc   = (bool) preg_match('/\bscript-src\b/i', $csp);
            $srcContext     = $hasScriptSrc ? 'script-src' : 'default-src';

            if (preg_match('/\b' . $srcContext . '\b[^;]*\'unsafe-inline\'/i', $csp)) {
                $weakDirectives[] = "'unsafe-inline'";
            }
            if (preg_match('/\b' . $srcContext . '\b[^;]*\'unsafe-eval\'/i', $csp)) {
                $weakDirectives[] = "'unsafe-eval'";
            }

            if (! empty($weakDirectives)) {
                $score += 5;
                $checks[] = [
                    'id'             => 'header_csp',
                    'label'          => 'Content-Security-Policy',
                    'status'         => 'warn',
                    'description'    => 'CSP is set but weakened by ' . implode(' and ', $weakDirectives) . ' in ' . $srcContext . '. These directives allow inline scripts and effectively disable XSS injection protection.',
                    'recommendation' => "Remove 'unsafe-inline' and 'unsafe-eval' from your CSP. Replace inline scripts with external files or use nonces/hashes. Test your policy at https://csp-evaluator.withgoogle.com/",
                ];
            } else {
                $score += 20;
                $preview  = strlen($csp) <= 100 ? "\"{$csp}\"" : '(policy is set)';
                $checks[] = [
                    'id'          => 'header_csp',
                    'label'       => 'Content-Security-Policy',
                    'status'      => 'pass',
                    'description' => "CSP header enforced: {$preview}",
                ];
            }
        } elseif ($cspReportOnly !== null) {
            // Report-Only mode monitors but does NOT enforce — partial credit
            $score += 8;
            $checks[] = [
                'id'             => 'header_csp',
                'label'          => 'Content-Security-Policy',
                'status'         => 'warn',
                'description'    => 'Only a Content-Security-Policy-Report-Only header was found. This monitors violations but does NOT prevent attacks.',
                'recommendation' => 'Promote your CSP from Report-Only to enforced by renaming the header to Content-Security-Policy.',
            ];
        } else {
            $checks[] = [
                'id'             => 'header_csp',
                'label'          => 'Content-Security-Policy',
                'status'         => 'fail',
                'description'    => 'No Content-Security-Policy header found.',
                'recommendation' => 'Add a Content-Security-Policy header to restrict which resources the browser may load, preventing XSS attacks.',
            ];
        }

        // --- Check: X-Frame-Options ---
        // Note: CSP frame-ancestors supersedes X-Frame-Options in modern browsers.
        $maxScore += 15;
        $xframe = $headers['x-frame-options'] ?? null;
        $cspHasFrameAncestors = $csp && stripos($csp, 'frame-ancestors') !== false;

        if ($xframe !== null) {
            $validValues = ['DENY', 'SAMEORIGIN'];
            $upperVal    = strtoupper(trim($xframe));
            if (in_array($upperVal, $validValues)) {
                $score += 15;
                $checks[] = [
                    'id'          => 'header_xframe',
                    'label'       => 'X-Frame-Options',
                    'status'      => 'pass',
                    'description' => "X-Frame-Options: {$xframe} — protects against clickjacking.",
                ];
            } else {
                $score += 5;
                $checks[] = [
                    'id'             => 'header_xframe',
                    'label'          => 'X-Frame-Options',
                    'status'         => 'warn',
                    'description'    => "X-Frame-Options is set to \"{$xframe}\" which is not a recommended value.",
                    'recommendation' => 'Use X-Frame-Options: DENY or SAMEORIGIN.',
                ];
            }
        } elseif ($cspHasFrameAncestors) {
            $score += 15;
            $checks[] = [
                'id'          => 'header_xframe',
                'label'       => 'X-Frame-Options',
                'status'      => 'pass',
                'description' => 'X-Frame-Options not set, but CSP frame-ancestors directive provides equivalent clickjacking protection.',
            ];
        } else {
            $checks[] = [
                'id'             => 'header_xframe',
                'label'          => 'X-Frame-Options',
                'status'         => 'fail',
                'description'    => 'No X-Frame-Options header found. The site may be vulnerable to clickjacking.',
                'recommendation' => 'Add X-Frame-Options: DENY or SAMEORIGIN, or use CSP frame-ancestors.',
            ];
        }

        // --- Check: X-Content-Type-Options ---
        $maxScore += 15;
        $xcto = $headers['x-content-type-options'] ?? null;
        if ($xcto !== null && stripos($xcto, 'nosniff') !== false) {
            $score += 15;
            $checks[] = [
                'id'          => 'header_xcontent',
                'label'       => 'X-Content-Type-Options',
                'status'      => 'pass',
                'description' => 'X-Content-Type-Options: nosniff is set — prevents MIME-type sniffing.',
            ];
        } elseif ($xcto !== null) {
            $score += 5;
            $checks[] = [
                'id'             => 'header_xcontent',
                'label'          => 'X-Content-Type-Options',
                'status'         => 'warn',
                'description'    => "X-Content-Type-Options is set to \"{$xcto}\" — the only valid value is \"nosniff\".",
                'recommendation' => 'Set X-Content-Type-Options: nosniff',
            ];
        } else {
            $checks[] = [
                'id'             => 'header_xcontent',
                'label'          => 'X-Content-Type-Options',
                'status'         => 'fail',
                'description'    => 'X-Content-Type-Options header is missing.',
                'recommendation' => 'Add X-Content-Type-Options: nosniff to prevent browsers from MIME-sniffing responses.',
            ];
        }

        // --- Check: Referrer-Policy ---
        $maxScore += 15;
        $referrer        = $headers['referrer-policy'] ?? null;
        // Only flag values that expose full URL paths cross-origin.
        // 'origin' and 'origin-when-cross-origin' are acceptable — they only leak the domain, not the path.
        $insecureReferrer = ['unsafe-url', 'no-referrer-when-downgrade'];
        if ($referrer !== null && ! in_array(strtolower(trim($referrer)), $insecureReferrer)) {
            $score += 15;
            $checks[] = [
                'id'          => 'header_referrer',
                'label'       => 'Referrer-Policy',
                'status'      => 'pass',
                'description' => "Referrer-Policy: {$referrer}",
            ];
        } elseif ($referrer !== null) {
            $checks[] = [
                'id'             => 'header_referrer',
                'label'          => 'Referrer-Policy',
                'status'         => 'warn',
                'description'    => "Referrer-Policy is set to \"{$referrer}\" which may leak sensitive URL data.",
                'recommendation' => 'Use a stricter value such as: strict-origin-when-cross-origin or no-referrer.',
            ];
        } else {
            $checks[] = [
                'id'             => 'header_referrer',
                'label'          => 'Referrer-Policy',
                'status'         => 'fail',
                'description'    => 'No Referrer-Policy header found.',
                'recommendation' => 'Add Referrer-Policy: strict-origin-when-cross-origin to control how much referrer info is sent.',
            ];
        }

        // --- Check: Permissions-Policy ---
        $maxScore += 15;
        $permissions = $headers['permissions-policy'] ?? null;
        if ($permissions !== null) {
            $score += 15;
            $checks[] = [
                'id'          => 'header_permissions',
                'label'       => 'Permissions-Policy',
                'status'      => 'pass',
                'description' => "Permissions-Policy header found — browser feature access is restricted.",
            ];
        } else {
            $checks[] = [
                'id'             => 'header_permissions',
                'label'          => 'Permissions-Policy',
                'status'         => 'warn',
                'description'    => 'No Permissions-Policy header found.',
                'recommendation' => 'Add a Permissions-Policy header to restrict browser features like camera, microphone, and geolocation.',
            ];
        }

        // --- Check: Cookie Security (HttpOnly, Secure, SameSite) ---
        $cookies = $headers['set-cookie'] ?? [];
        if (! empty($cookies)) {
            $maxScore += 10;
            $insecure = [];
            foreach ($cookies as $cookie) {
                $lower   = strtolower($cookie);
                $name    = trim(explode('=', $cookie)[0]);
                $missing = [];
                if (stripos($lower, 'httponly') === false)  $missing[] = 'HttpOnly';
                if (stripos($lower, '; secure') === false)  $missing[] = 'Secure';
                if (stripos($lower, 'samesite') === false)  $missing[] = 'SameSite';
                if (! empty($missing)) {
                    $insecure[] = $name . ' (missing: ' . implode(', ', $missing) . ')';
                }
            }

            if (empty($insecure)) {
                $score += 10;
                $checks[] = [
                    'id'          => 'header_cookies',
                    'label'       => 'Cookie security flags',
                    'status'      => 'pass',
                    'description' => 'All cookies are set with HttpOnly, Secure, and SameSite flags.',
                ];
            } else {
                $checks[] = [
                    'id'             => 'header_cookies',
                    'label'          => 'Cookie security flags',
                    'status'         => 'fail',
                    'description'    => 'One or more cookies are missing security flags: ' . implode('; ', $insecure) . '.',
                    'recommendation' => 'Set HttpOnly (prevents JS access), Secure (HTTPS only), and SameSite=Lax or Strict on all cookies.',
                ];
            }
        }

        // --- Informational: X-XSS-Protection (deprecated, not scored) ---
        $xss = $headers['x-xss-protection'] ?? null;
        if ($xss !== null) {
            $checks[] = [
                'id'          => 'header_xss',
                'label'       => 'X-XSS-Protection (deprecated)',
                'status'      => 'pass',
                'description' => "X-XSS-Protection: {$xss} — Note: this header is deprecated and ignored by modern browsers. Rely on CSP instead.",
            ];
        }

        // --- Informational: CORS wildcard (not scored — only warn when misconfigured) ---
        $cors = $headers['access-control-allow-origin'] ?? null;
        if ($cors === '*') {
            $checks[] = [
                'id'             => 'header_cors',
                'label'          => 'CORS policy',
                'status'         => 'warn',
                'description'    => 'Access-Control-Allow-Origin: * is set. Any website can make cross-origin requests to this server and read the response.',
                'recommendation' => "Replace the wildcard with specific trusted origins:\nAccess-Control-Allow-Origin: https://yourtrustedapp.com\nOnly use * for fully public APIs that serve no authenticated or user-specific data.",
            ];
        } elseif ($cors !== null) {
            $checks[] = [
                'id'          => 'header_cors',
                'label'       => 'CORS policy',
                'status'       => 'pass',
                'description'  => "Cross-origin access is restricted to: {$cors}",
            ];
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
        // Collect only headers from the LAST response in a redirect chain
        $lastHeaders = [];

        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
            // Include Origin header so we can capture the CORS response header in one request
            CURLOPT_HTTPHEADER     => ['Origin: https://cors-probe.webcheckapp.com'],
        ]);

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$lastHeaders) {
            // A new HTTP status line signals a new response — reset collected headers
            if (preg_match('/^HTTP\//i', $header)) {
                $lastHeaders = [];
            } elseif (str_contains($header, ':')) {
                [$key, $value] = explode(':', $header, 2);
                $keyLower      = strtolower(trim($key));
                // Set-Cookie can appear multiple times — collect as array
                if ($keyLower === 'set-cookie') {
                    $lastHeaders['set-cookie'][] = trim($value);
                } else {
                    $lastHeaders[$keyLower] = trim($value);
                }
            }
            return strlen($header);
        });

        curl_exec($ch);
        curl_close($ch);

        return $lastHeaders;
    }

}
