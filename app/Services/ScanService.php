<?php

namespace App\Services;

use App\Services\Scanners\ContentScanner;
use App\Services\Scanners\DnsScanner;
use App\Services\Scanners\HeadersScanner;
use App\Services\Scanners\PerformanceScanner;
use App\Services\Scanners\SslScanner;
use App\Services\Scanners\TechnologyScanner;
use App\Services\Scanners\TrustScanner;
use App\Services\Scanners\MalwareScanner;
use App\Services\Scanners\ExposedFilesScanner;
use App\Services\Scanners\PortScanner;
use App\Services\Scanners\PrivacyScanner;
use App\Services\Scanners\AccessibilityScanner;
use App\Services\Scanners\TlsCipherScanner;
use App\Services\Scanners\RobotsScanner;
use App\Services\Scanners\ApiSecurityScanner;
use App\Services\Scanners\CarbonScanner;
use App\Services\Scanners\BrokenLinksScanner;
use App\Services\Scanners\BrandingScanner;
use App\Services\Scanners\SubdomainTakeoverScanner;
use Illuminate\Support\Facades\Log;

class ScanService
{
    // Weights for the overall score calculation
    private array $weights = [
        'ssl'           => 25,
        'headers'       => 20,
        'dns'           => 15,
        'performance'   => 10,
        'content'       => 10,
        'exposed_files' => 20,
    ];

    public function run(string $host, ?callable $onScannerDone = null): array
    {
        // Block SSRF attempts — private IPs, localhost, metadata services
        if (! $this->isPublicHost($host)) {
            throw new \InvalidArgumentException("Host \"{$host}\" is not a publicly reachable domain.");
        }

        $results = [];

        // Resolve the canonical host once before running any scanner.
        // Many sites have SSL/services only on www.domain.com while the apex
        // domain has no open port 443. Without this step every scanner would
        // fail for those sites (TTFB null, no compression detected, etc.).
        $canonicalHost = $this->resolveCanonicalHost($host);

        // DNS scanner always uses the user-supplied host (apex domain),
        // because SPF/DMARC/CAA records live on the apex regardless of www.
        $scanners = [
            'ssl'                 => fn() => (new SslScanner())->scan($canonicalHost),
            'headers'             => fn() => (new HeadersScanner())->scan($canonicalHost),
            'dns'                 => fn() => (new DnsScanner())->scan($host),
            'performance'         => fn() => (new PerformanceScanner())->scan($canonicalHost),
            'content'             => fn() => (new ContentScanner())->scan($canonicalHost),
            'technology'          => fn() => (new TechnologyScanner())->scan($canonicalHost),
            'trust'               => fn() => (new TrustScanner())->scan($canonicalHost),
            'malware'             => fn() => (new MalwareScanner())->scan($canonicalHost),
            'exposed_files'       => fn() => (new ExposedFilesScanner())->scan($canonicalHost),
            'ports'               => fn() => (new PortScanner())->scan($canonicalHost),
            'privacy'             => fn() => (new PrivacyScanner())->scan($canonicalHost),
            'accessibility'       => fn() => (new AccessibilityScanner())->scan($canonicalHost),
            'tls'                 => fn() => (new TlsCipherScanner())->scan($canonicalHost),
            'robots'              => fn() => (new RobotsScanner())->scan($canonicalHost),
            'api_security'        => fn() => (new ApiSecurityScanner())->scan($canonicalHost),
            'carbon'              => fn() => (new CarbonScanner())->scan($canonicalHost),
            'broken_links'        => fn() => (new BrokenLinksScanner())->scan($canonicalHost),
            'branding'            => fn() => (new BrandingScanner())->scan($canonicalHost),
            'subdomain_takeover'  => fn() => (new SubdomainTakeoverScanner())->scan($host),
        ];

        foreach ($scanners as $key => $scanner) {
            try {
                $results[$key] = $scanner();
            } catch (\Throwable $e) {
                Log::warning("Scanner [{$key}] failed for host [{$host}]", [
                    'scanner' => $key,
                    'host'    => $host,
                    'error'   => $e->getMessage(),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ]);

                $results[$key] = [
                    'category' => ucfirst(str_replace('_', ' ', $key)),
                    'icon'     => 'exclamation-triangle',
                    'score'    => null,
                    'checks'   => [[
                        'id'          => "{$key}_error",
                        'label'       => 'Scanner error',
                        'status'      => 'warn',
                        'description' => 'This check could not be completed.',
                    ]],
                ];
            }

            if ($onScannerDone !== null) {
                $onScannerDone($results);
            }
        }

        $overallScore = $this->calculateOverallScore($results);
        $grade = $this->scoreToGrade($overallScore);

        return [
            'score'      => $overallScore,
            'grade'      => $grade,
            'categories' => $results,
        ];
    }

    /**
     * Find the host that actually serves HTTPS content.
     *
     * Steps:
     *  1. Try https://{host} with redirect following — covers the common case
     *     where the apex domain redirects to www over HTTPS.
     *  2. If that fails (e.g. port 443 not open on apex), try https://www.{host}.
     *  3. Fall back to the original host so scanners can still report errors.
     */
    private function resolveCanonicalHost(string $host): string
    {
        // Step 1: follow HTTPS redirects from the given host
        $resolved = $this->tryResolveHost("https://{$host}");
        if ($resolved !== null) {
            return $resolved;
        }

        // Step 2: try www prefix when the apex domain has no port 443
        if (! str_starts_with($host, 'www.')) {
            $resolved = $this->tryResolveHost("https://www.{$host}");
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return $host;
    }

    private function tryResolveHost(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        curl_exec($ch);
        $errno        = curl_errno($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if ($errno || empty($effectiveUrl)) {
            return null;
        }

        $parsed = parse_url($effectiveUrl);
        $host   = ! empty($parsed['host']) ? $parsed['host'] : null;

        // SSRF: verify the redirect target resolves to a public IP
        if ($host === null || ! $this->isPublicHost($host)) {
            return null;
        }

        return $host;
    }

    private function calculateOverallScore(array $results): int
    {
        $totalWeight = array_sum($this->weights);
        $weightedScore = 0;

        foreach ($this->weights as $key => $weight) {
            if (isset($results[$key]['score'])) {
                $weightedScore += $results[$key]['score'] * $weight;
            }
        }

        return (int) min(100, max(0, round($weightedScore / $totalWeight)));
    }

    /**
     * Block scanning of private/reserved IP ranges and localhost (SSRF prevention).
     * Checks both IPv4 (A records) and IPv6 (AAAA records).
     */
    private function isPublicHost(string $host): bool
    {
        // Strip IPv6 brackets e.g. [::1]
        $bare = ltrim(rtrim($host, ']'), '[');

        // If the input itself is an IP address, validate it directly
        if (filter_var($bare, FILTER_VALIDATE_IP)) {
            return (bool) filter_var(
                $bare,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        $lower = strtolower($host);

        // Block obvious internal hostnames
        if (in_array($lower, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return false;
        }

        // Block internal TLDs
        if (preg_match('/\.(local|internal|test|lan|intranet|corp|home|arpa)$/i', $host)) {
            return false;
        }

        // Resolve A + AAAA records and verify every resolved IP is public
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        // Fallback to gethostbyname when dns_get_record returns nothing
        if (empty($records)) {
            $ip = @gethostbyname($host);
            if ($ip === $host) {
                // Unresolvable — allow scanners to report connection errors naturally
                return true;
            }
            $records = [['ip' => $ip]];
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip === null) {
                continue;
            }

            // Explicit IPv6 private/reserved ranges not covered by FILTER_FLAG_NO_PRIV_RANGE
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                // Loopback (::1), link-local (fe80::/10), unique-local (fc00::/7),
                // IPv4-mapped (::ffff:0:0/96), documentation (2001:db8::/32)
                if (preg_match('/^(::1|fe[89ab][0-9a-f]:|f[cd][0-9a-f]{2}:|::ffff:|2001:db8:)/i', $ip)) {
                    return false;
                }
            }

            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    private function scoreToGrade(int $score): string
    {
        return match(true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'A-',
            $score >= 80 => 'B+',
            $score >= 75 => 'B',
            $score >= 70 => 'B-',
            $score >= 65 => 'C+',
            $score >= 60 => 'C',
            $score >= 55 => 'C-',
            $score >= 50 => 'D+',
            $score >= 45 => 'D',
            $score >= 40 => 'D-',
            default      => 'F',
        };
    }
}
