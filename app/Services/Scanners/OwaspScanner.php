<?php

namespace App\Services\Scanners;

class OwaspScanner
{
    use HasSafeCall;

    private const TIMEOUT = 8;

    /**
     * OWASP Top 10 (2021) analysis.
     * Maps results from other scanners + performs its own checks.
     */
    public function scan(string $host, array $previousResults = []): array
    {
        $checks = [];
        $score  = 0;
        $max    = 0;

        // A01:2021 – Broken Access Control
        $a01 = $this->checkA01($host, $previousResults);
        $checks[] = $a01;
        $score += $a01['points_earned'];
        $max += $a01['points_max'];

        // A02:2021 – Cryptographic Failures
        $a02 = $this->checkA02($host, $previousResults);
        $checks[] = $a02;
        $score += $a02['points_earned'];
        $max += $a02['points_max'];

        // A03:2021 – Injection
        $a03 = $this->checkA03($host, $previousResults);
        $checks[] = $a03;
        $score += $a03['points_earned'];
        $max += $a03['points_max'];

        // A04:2021 – Insecure Design
        $a04 = $this->checkA04($host, $previousResults);
        $checks[] = $a04;
        $score += $a04['points_earned'];
        $max += $a04['points_max'];

        // A05:2021 – Security Misconfiguration
        $a05 = $this->checkA05($host, $previousResults);
        $checks[] = $a05;
        $score += $a05['points_earned'];
        $max += $a05['points_max'];

        // A06:2021 – Vulnerable and Outdated Components
        $a06 = $this->checkA06($host, $previousResults);
        $checks[] = $a06;
        $score += $a06['points_earned'];
        $max += $a06['points_max'];

        // A07:2021 – Identification and Authentication Failures
        $a07 = $this->checkA07($host, $previousResults);
        $checks[] = $a07;
        $score += $a07['points_earned'];
        $max += $a07['points_max'];

        // A08:2021 – Software and Data Integrity Failures
        $a08 = $this->checkA08($host, $previousResults);
        $checks[] = $a08;
        $score += $a08['points_earned'];
        $max += $a08['points_max'];

        // A09:2021 – Security Logging and Monitoring Failures
        $a09 = $this->checkA09($host, $previousResults);
        $checks[] = $a09;
        $score += $a09['points_earned'];
        $max += $a09['points_max'];

        // A10:2021 – Server-Side Request Forgery (SSRF)
        $a10 = $this->checkA10($host, $previousResults);
        $checks[] = $a10;
        $score += $a10['points_earned'];
        $max += $a10['points_max'];

        // Strip internal scoring fields from output
        $outputChecks = array_map(fn($c) => array_diff_key($c, ['points_earned' => 0, 'points_max' => 0]), $checks);

        return [
            'category' => 'OWASP Top 10',
            'icon'     => 'shield-exclamation',
            'score'    => $max > 0 ? (int) round(($score / $max) * 100) : 100,
            'checks'   => $outputChecks,
        ];
    }

    // ── A01: Broken Access Control ──────────────────────────────────
    private function checkA01(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Map: ExposedFiles failures
        if ($this->hasScannerFailures($results, 'exposed_files')) {
            $issues[] = 'Sensitive files are publicly accessible';
            $earned -= 3;
        }

        // Map: API endpoints exposed without auth
        if ($this->hasScannerFailures($results, 'api_security')) {
            $issues[] = 'API endpoints are exposed without authentication';
            $earned -= 2;
        }

        // Map: CORS wildcard
        $corsIssue = $this->checkHasStatus($results, 'headers', 'header_cors', 'warn');
        if ($corsIssue) {
            $issues[] = 'CORS policy allows wildcard origins';
            $earned -= 2;
        }

        // Own check: directory listing
        $dirListing = $this->safe(fn() => $this->checkDirectoryListing($host), false);
        if ($dirListing) {
            $issues[] = 'Directory listing is enabled on the server';
            $earned -= 3;
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'High');

        return [
            'id'             => 'owasp_a01',
            'label'          => 'A01:2021 – Broken Access Control',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'No broken access control issues detected. Access restrictions appear properly configured.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Restrict access to sensitive files and directories. Implement proper authentication on all API endpoints. Disable directory listing. Configure CORS with specific allowed origins.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── A02: Cryptographic Failures ─────────────────────────────────
    private function checkA02(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Map: SSL issues
        if ($this->hasScannerFailures($results, 'ssl')) {
            $issues[] = 'SSL/TLS configuration has weaknesses';
            $earned -= 3;
        }

        // Map: TLS cipher issues
        if ($this->hasScannerFailures($results, 'tls')) {
            $issues[] = 'Weak TLS protocol versions or cipher suites detected';
            $earned -= 3;
        }

        // Map: HSTS missing (check SSL scanner)
        $hstsIssue = $this->checkHasStatus($results, 'ssl', 'hsts_header', 'fail') ||
                     $this->checkHasStatus($results, 'ssl', 'hsts_header', 'warn');
        if ($hstsIssue) {
            $issues[] = 'HSTS header missing or misconfigured';
            $earned -= 2;
        }

        // Map: Mixed content from content scanner
        $mixedContent = $this->checkHasStatus($results, 'content', 'mixed_content', 'fail') ||
                        $this->checkHasStatus($results, 'content', 'mixed_content', 'warn');
        if ($mixedContent) {
            $issues[] = 'Mixed content detected (HTTP resources on HTTPS page)';
            $earned -= 2;
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'High');

        return [
            'id'             => 'owasp_a02',
            'label'          => 'A02:2021 – Cryptographic Failures',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'Cryptographic configuration is strong. SSL/TLS and HSTS are properly configured.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Enable HTTPS with strong TLS 1.2+ configuration. Set HSTS header with a long max-age. Remove all mixed content. Use secure cipher suites only.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── A03: Injection ──────────────────────────────────────────────
    private function checkA03(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Map: CSP header missing or weak
        $cspIssue = $this->checkHasStatus($results, 'headers', 'header_csp', 'fail') ||
                    $this->checkHasStatus($results, 'headers', 'header_csp', 'warn');
        if ($cspIssue) {
            $issues[] = 'Content-Security-Policy is missing or weak (XSS protection reduced)';
            $earned -= 4;
        }

        // Map: X-Content-Type-Options missing
        $xctIssue = $this->checkHasStatus($results, 'headers', 'header_xcontent', 'fail');
        if ($xctIssue) {
            $issues[] = 'X-Content-Type-Options header missing (MIME sniffing risk)';
            $earned -= 3;
        }

        // Own check: reflected input in response
        $reflected = $this->safe(fn() => $this->checkReflectedInput($host), false);
        if ($reflected) {
            $issues[] = 'URL parameters are reflected in HTML response (potential XSS)';
            $earned -= 3;
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'Critical');

        return [
            'id'             => 'owasp_a03',
            'label'          => 'A03:2021 – Injection',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'Injection protections look adequate. CSP and content type headers are in place.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Implement a strict Content-Security-Policy. Set X-Content-Type-Options: nosniff. Sanitize and encode all user input. Use parameterized queries for database access.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── A04: Insecure Design ────────────────────────────────────────
    private function checkA04(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Map: X-Frame-Options / frame-ancestors missing (clickjacking)
        $xframeIssue = $this->checkHasStatus($results, 'headers', 'header_xframe', 'fail');
        if ($xframeIssue) {
            $issues[] = 'Clickjacking protection missing (no X-Frame-Options or frame-ancestors)';
            $earned -= 3;
        }

        // Map: GraphQL introspection enabled
        $graphqlIssue = $this->checkHasStatus($results, 'api_security', 'api_graphql_introspection', 'warn');
        if ($graphqlIssue) {
            $issues[] = 'GraphQL introspection is enabled in production';
            $earned -= 3;
        }

        // Own check: rate limiting (send rapid requests, check for 429)
        $noRateLimit = $this->safe(fn() => $this->checkRateLimiting($host), false);
        if ($noRateLimit) {
            $issues[] = 'No rate limiting detected on the application';
            $earned -= 4;
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'High');

        return [
            'id'             => 'owasp_a04',
            'label'          => 'A04:2021 – Insecure Design',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'Application design appears secure. Clickjacking protection and API security are in place.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Add X-Frame-Options or CSP frame-ancestors to prevent clickjacking. Disable GraphQL introspection in production. Implement rate limiting on all endpoints.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── A05: Security Misconfiguration ──────────────────────────────
    private function checkA05(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Map: Server version disclosure
        $serverIssue = $this->checkHasStatus($results, 'headers', 'header_server', 'fail') ||
                       $this->checkHasStatus($results, 'headers', 'header_server', 'warn');
        if ($serverIssue) {
            $issues[] = 'Server version information is disclosed in headers';
            $earned -= 2;
        }

        // Map: Open ports
        if ($this->hasScannerFailures($results, 'ports')) {
            $issues[] = 'Dangerous ports are open and accessible from the internet';
            $earned -= 3;
        }

        // Map: Exposed files
        if ($this->hasScannerFailures($results, 'exposed_files')) {
            $issues[] = 'Configuration or backup files are publicly accessible';
            $earned -= 3;
        }

        // Own check: debug/stack trace detection
        $debugMode = $this->safe(fn() => $this->checkDebugMode($host), false);
        if ($debugMode) {
            $issues[] = 'Application debug mode or stack traces are exposed';
            $earned -= 2;
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'High');

        return [
            'id'             => 'owasp_a05',
            'label'          => 'A05:2021 – Security Misconfiguration',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'No security misconfigurations detected. Server headers and file access are properly restricted.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Remove server version headers. Close unnecessary ports. Remove all exposed configuration files. Disable debug mode in production.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── A06: Vulnerable and Outdated Components ─────────────────────
    private function checkA06(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Map from technology scanner — check for known vulnerable versions
        $techChecks = $this->getChecks($results, 'technology');
        $detectedTech = [];
        foreach ($techChecks as $check) {
            if (! empty($check['description'])) {
                $detectedTech[] = $check['description'];
            }
        }

        // Check for known vulnerable versions in tech detection
        $vulnerablePatterns = [
            '/jQuery\s+(1\.[0-9]|2\.[0-2]|3\.[0-4])\b/i' => 'jQuery version with known XSS vulnerabilities',
            '/WordPress\s+([1-5]\.\d)/i' => 'Outdated WordPress version with known vulnerabilities',
            '/PHP\/([5-7]\.[0-3])\b/' => 'Outdated PHP version no longer receiving security updates',
            '/Apache\/(2\.2|2\.0|1\.)/i' => 'Outdated Apache version with known vulnerabilities',
            '/nginx\/(0\.|1\.[0-9]\.|1\.1[0-7]\.)/i' => 'Outdated nginx version',
        ];

        $techString = implode(' ', $detectedTech);

        // Also check server header from headers scanner
        $rawHeaders = $results['headers']['raw_headers'] ?? [];
        foreach ($rawHeaders as $h) {
            $techString .= ' ' . $h;
        }

        foreach ($vulnerablePatterns as $pattern => $message) {
            if (preg_match($pattern, $techString)) {
                $issues[] = $message;
                $earned -= 3;
            }
        }

        // Map: composer.lock exposed (reveals exact dependency versions)
        $composerExposed = $this->checkHasStatus($results, 'exposed_files', 'exposed_composerlock', 'fail');
        if ($composerExposed) {
            $issues[] = 'composer.lock is publicly accessible, revealing exact dependency versions';
            $earned -= 2;
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'High');

        return [
            'id'             => 'owasp_a06',
            'label'          => 'A06:2021 – Vulnerable and Outdated Components',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'No known vulnerable components detected in the visible technology stack.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Update all components to their latest stable versions. Remove version information from public headers. Regularly audit dependencies for known CVEs.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── A07: Identification and Authentication Failures ─────────────
    private function checkA07(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Map: WordPress user enumeration
        $wpUsers = $this->checkHasStatus($results, 'api_security', 'api_wp_users', 'warn');
        if ($wpUsers) {
            $issues[] = 'WordPress user enumeration is possible via REST API';
            $earned -= 3;
        }

        // Map: .htpasswd exposed
        $htpasswd = $this->checkHasStatus($results, 'exposed_files', 'exposed_htpasswd', 'fail');
        if ($htpasswd) {
            $issues[] = '.htpasswd file with hashed credentials is publicly accessible';
            $earned -= 3;
        }

        // Own check: session cookie security flags
        $cookieIssues = $this->safe(fn() => $this->checkSessionCookies($host), []);
        foreach ($cookieIssues as $issue) {
            $issues[] = $issue;
            $earned -= 2;
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'High');

        return [
            'id'             => 'owasp_a07',
            'label'          => 'A07:2021 – Identification and Authentication Failures',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'Authentication-related configurations appear secure. No user enumeration or credential exposure detected.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Disable WordPress user enumeration. Remove exposed credential files. Set Secure, HttpOnly, and SameSite flags on all session cookies.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── A08: Software and Data Integrity Failures ───────────────────
    private function checkA08(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Map: COEP/COOP headers
        $coopIssue = $this->checkHasStatus($results, 'headers', 'header_coop', 'warn');
        $coepIssue = $this->checkHasStatus($results, 'headers', 'header_coep', 'warn');
        if ($coopIssue) {
            $issues[] = 'Cross-Origin-Opener-Policy (COOP) header not set';
            $earned -= 2;
        }
        if ($coepIssue) {
            $issues[] = 'Cross-Origin-Embedder-Policy (COEP) header not set';
            $earned -= 2;
        }

        // Own check: Subresource Integrity (SRI) on external scripts
        $sriResult = $this->safe(fn() => $this->checkSRI($host), null);
        if ($sriResult !== null) {
            if ($sriResult['missing'] > 0) {
                $issues[] = "{$sriResult['missing']} external script(s) loaded without Subresource Integrity (SRI)";
                $earned -= min(4, $sriResult['missing']);
            }
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'High');

        return [
            'id'             => 'owasp_a08',
            'label'          => 'A08:2021 – Software and Data Integrity Failures',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'Data integrity protections are in place. External resources are properly secured.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Add Subresource Integrity (SRI) hashes to all external scripts and stylesheets. Set COOP and COEP headers for cross-origin isolation.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── A09: Security Logging and Monitoring Failures ────────────────
    private function checkA09(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Own check: security.txt present
        $securityTxt = $this->safe(fn() => $this->checkSecurityTxt($host), false);
        if (! $securityTxt) {
            $issues[] = 'No security.txt file found (RFC 9116) — security researchers cannot easily report vulnerabilities';
            $earned -= 5;
        }

        // Own check: error pages leak information
        $errorLeak = $this->safe(fn() => $this->checkErrorInfoLeak($host), false);
        if ($errorLeak) {
            $issues[] = 'Error pages expose internal information (framework, paths, stack traces)';
            $earned -= 5;
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'Medium');

        return [
            'id'             => 'owasp_a09',
            'label'          => 'A09:2021 – Security Logging and Monitoring Failures',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'Security monitoring indicators are in place. A security.txt file is present for vulnerability reporting.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Add a /.well-known/security.txt file with contact information per RFC 9116. Configure custom error pages that do not expose internal details.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── A10: Server-Side Request Forgery (SSRF) ─────────────────────
    private function checkA10(string $host, array $results): array
    {
        $issues = [];
        $points = 10;
        $earned = $points;

        // Own check: open redirect detection
        $openRedirect = $this->safe(fn() => $this->checkOpenRedirect($host), false);
        if ($openRedirect) {
            $issues[] = 'Potential open redirect detected — could be chained with SSRF attacks';
            $earned -= 5;
        }

        // Map: exposed API endpoints that could be used for SSRF
        $actuatorExposed = false;
        $apiChecks = $this->getChecks($results, 'api_security');
        foreach ($apiChecks as $check) {
            if (($check['id'] ?? '') === 'api_docs_exposed' && ($check['status'] ?? '') === 'fail') {
                if (stripos($check['description'] ?? '', 'actuator') !== false) {
                    $actuatorExposed = true;
                }
            }
        }
        if ($actuatorExposed) {
            $issues[] = 'Spring Actuator endpoints exposed — potential SSRF attack vector';
            $earned -= 5;
        }

        $earned = max(0, $earned);
        $status = $earned === $points ? 'pass' : ($earned >= $points / 2 ? 'warn' : 'fail');
        $risk = $earned === $points ? 'Low' : ($earned >= $points / 2 ? 'Medium' : 'High');

        return [
            'id'             => 'owasp_a10',
            'label'          => 'A10:2021 – Server-Side Request Forgery (SSRF)',
            'status'         => $status,
            'risk'           => $risk,
            'description'    => empty($issues)
                ? 'No SSRF indicators detected. No open redirects or exposed internal endpoints found.'
                : 'Issues found: ' . implode('; ', $issues) . '.',
            'recommendation' => empty($issues)
                ? null
                : 'Validate and sanitize all URL inputs server-side. Block requests to internal/private IP ranges. Remove open redirect endpoints. Restrict access to actuator/monitoring endpoints.',
            'points_earned'  => $earned,
            'points_max'     => $points,
        ];
    }

    // ── Helper: check if a scanner category has any failures ────────
    private function hasScannerFailures(array $results, string $scannerKey): bool
    {
        $checks = $this->getChecks($results, $scannerKey);
        foreach ($checks as $check) {
            if (($check['status'] ?? '') === 'fail') {
                return true;
            }
        }
        return false;
    }

    private function getChecks(array $results, string $scannerKey): array
    {
        return $results[$scannerKey]['checks'] ?? [];
    }

    private function checkHasStatus(array $results, string $scanner, string $checkId, string $status): bool
    {
        foreach ($this->getChecks($results, $scanner) as $check) {
            if (($check['id'] ?? '') === $checkId && ($check['status'] ?? '') === $status) {
                return true;
            }
        }
        return false;
    }

    // ── Own checks ──────────────────────────────────────────────────

    private function checkDirectoryListing(string $host): bool
    {
        $paths = ['/icons/', '/images/', '/uploads/', '/assets/'];
        foreach ($paths as $path) {
            $body = $this->fetchBody("https://{$host}{$path}");
            if ($body && preg_match('/Index of \//i', $body)) {
                return true;
            }
        }
        return false;
    }

    private function checkReflectedInput(string $host): bool
    {
        $canary = 'wcatest' . bin2hex(random_bytes(4));
        $body = $this->fetchBody("https://{$host}/?q={$canary}&search={$canary}");
        return $body && stripos($body, $canary) !== false;
    }

    private function checkDebugMode(string $host): bool
    {
        // Request a path likely to trigger an error
        $body = $this->fetchBody("https://{$host}/___webcheckapp_debug_test_" . time());
        if (! $body) return false;

        $debugPatterns = [
            'Whoops!',
            'stack trace',
            'StackTrace',
            'SQLSTATE[',
            'vendor/laravel',
            'vendor/symfony',
            'Traceback (most recent call last)',
            'Exception in thread',
            'at org.apache.',
        ];

        foreach ($debugPatterns as $pattern) {
            if (stripos($body, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    private function checkSessionCookies(string $host): array
    {
        $issues = [];

        $ch = curl_init("https://{$host}/");
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

        if (! $response) return [];

        // Check Set-Cookie headers
        preg_match_all('/^Set-Cookie:\s*(.+)$/mi', $response, $matches);
        foreach ($matches[1] ?? [] as $cookie) {
            $cookieLower = strtolower($cookie);
            $name = strtok($cookie, '=');

            // Only check session-like cookies
            if (! preg_match('/sess|token|auth|sid/i', $name)) continue;

            if (stripos($cookieLower, 'secure') === false) {
                $issues[] = "Session cookie '{$name}' missing Secure flag";
                break;
            }
            if (stripos($cookieLower, 'httponly') === false) {
                $issues[] = "Session cookie '{$name}' missing HttpOnly flag";
                break;
            }
        }

        return $issues;
    }

    private function checkSRI(string $host): ?array
    {
        $body = $this->fetchBody("https://{$host}/");
        if (! $body) return null;

        // Find external scripts (different domain)
        preg_match_all('/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $body, $matches, PREG_SET_ORDER);

        $externalWithoutSri = 0;
        foreach ($matches as $match) {
            $src = $match[1];
            // Skip same-origin scripts
            if (str_starts_with($src, '/') && ! str_starts_with($src, '//')) continue;
            if (stripos($src, $host) !== false) continue;

            // Check for integrity attribute
            if (stripos($match[0], 'integrity=') === false) {
                $externalWithoutSri++;
            }
        }

        return ['missing' => $externalWithoutSri];
    }

    private function checkSecurityTxt(string $host): bool
    {
        $paths = ['/.well-known/security.txt', '/security.txt'];
        foreach ($paths as $path) {
            $body = $this->fetchBody("https://{$host}{$path}");
            if ($body && stripos($body, 'Contact:') !== false) {
                return true;
            }
        }
        return false;
    }

    private function checkErrorInfoLeak(string $host): bool
    {
        $body = $this->fetchBody("https://{$host}/___nonexistent_path_" . time());
        if (! $body) return false;

        $leakPatterns = [
            '/vendor\/(laravel|symfony|autoload)/i',
            '/\.php:\d+/',
            '/Stack trace:/i',
            '/Traceback \(most recent/i',
            '/DOCUMENT_ROOT/i',
        ];

        foreach ($leakPatterns as $pattern) {
            if (preg_match($pattern, $body)) {
                return true;
            }
        }
        return false;
    }

    private function checkOpenRedirect(string $host): bool
    {
        $testUrls = [
            "https://{$host}/redirect?url=https://evil.com",
            "https://{$host}/redirect?to=https://evil.com",
            "https://{$host}/?redirect=https://evil.com",
            "https://{$host}/login?next=https://evil.com",
        ];

        foreach ($testUrls as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADER         => true,
                CURLOPT_NOBODY         => true,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
            ]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (in_array($code, [301, 302, 303, 307, 308]) && $response) {
                if (preg_match('/^Location:\s*https?:\/\/evil\.com/mi', $response)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function checkRateLimiting(string $host): bool
    {
        // Send 5 rapid requests and check for 429
        for ($i = 0; $i < 5; $i++) {
            $ch = curl_init("https://{$host}/");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 429) {
                return false; // Rate limiting IS working
            }
        }

        // No 429 after 5 rapid requests — but this is inconclusive
        // Only flag if X-RateLimit headers are also absent
        $ch = curl_init("https://{$host}/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $headers = curl_exec($ch);
        curl_close($ch);

        if ($headers && preg_match('/x-ratelimit/i', $headers)) {
            return false; // Rate limit headers present
        }

        return true; // No rate limiting detected
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
            CURLOPT_RANGE          => '0-16383',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code >= 200 && $code < 500 && $body) ? $body : null;
    }
}
