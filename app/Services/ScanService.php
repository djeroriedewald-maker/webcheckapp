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

    public function run(string $host): array
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
            'ssl'         => fn() => (new SslScanner())->scan($canonicalHost),
            'headers'     => fn() => (new HeadersScanner())->scan($canonicalHost),
            'dns'         => fn() => (new DnsScanner())->scan($host),
            'performance' => fn() => (new PerformanceScanner())->scan($canonicalHost),
            'content'     => fn() => (new ContentScanner())->scan($canonicalHost),
            'technology'    => fn() => (new TechnologyScanner())->scan($canonicalHost),
            'trust'         => fn() => (new TrustScanner())->scan($canonicalHost),
            'malware'       => fn() => (new MalwareScanner())->scan($canonicalHost),
            'exposed_files' => fn() => (new ExposedFilesScanner())->scan($canonicalHost),
            'ports'         => fn() => (new PortScanner())->scan($canonicalHost),
            'privacy'       => fn() => (new PrivacyScanner())->scan($canonicalHost),
        ];

        foreach ($scanners as $key => $scanner) {
            try {
                $results[$key] = $scanner();
            } catch (\Throwable $e) {
                $results[$key] = [
                    'category' => ucfirst($key),
                    'icon'     => 'exclamation-triangle',
                    'score'    => 0,
                    'checks'   => [[
                        'id'          => "{$key}_error",
                        'label'       => 'Scanner error',
                        'status'      => 'fail',
                        'description' => 'This check could not be completed.',
                    ]],
                ];
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
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 8,
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

        return ! empty($parsed['host']) ? $parsed['host'] : null;
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
     */
    private function isPublicHost(string $host): bool
    {
        $lower = strtolower($host);

        // Block obvious internal names
        if (in_array($lower, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return false;
        }

        // Block .local / .internal / .test TLDs
        if (preg_match('/\.(local|internal|test|lan|intranet)$/i', $host)) {
            return false;
        }

        // Resolve to IP and verify it's a public address
        $ip = @gethostbyname($host);
        if ($ip === $host) {
            // Could not resolve — let scanners report errors naturally
            return true;
        }

        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
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
