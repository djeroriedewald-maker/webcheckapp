<?php

namespace App\Services\Scanners;

class SubdomainTakeoverScanner
{
    use HasSafeCall;
    private const TIMEOUT = 5;

    /**
     * Known services vulnerable to subdomain takeover, keyed by CNAME suffix.
     * Value is an array of response body strings that indicate an unclaimed resource.
     */
    private const FINGERPRINTS = [
        'github.io'          => ["There isn't a GitHub Pages site here", '404 There is no GitHub Pages site here'],
        'herokudns.com'      => ['No such app', 'herokucdn.com/error-pages/no-such-app'],
        'netlify.com'        => ['Not Found - Request ID'],
        'amazonaws.com'      => ['NoSuchBucket', 'The specified bucket does not exist'],
        's3.amazonaws.com'   => ['NoSuchBucket', 'The specified bucket does not exist'],
        'azurewebsites.net'  => ['404 Web Site not found'],
        'cloudapp.net'       => ['404 Web Site not found'],
        'fastly.net'         => ['Fastly error: unknown domain'],
        'pantheon.io'        => ['404 error unknown site'],
        'surge.sh'           => ['project not found'],
        'bitbucket.io'       => ['Repository not found'],
        'ghost.io'           => ['The thing you were looking for is no longer here'],
        'uservoice.com'      => ['This UserVoice subdomain is currently available'],
        'zendesk.com'        => ['Help Center Closed'],
        'freshdesk.com'      => ['There is no helpdesk here'],
        'statuspage.io'      => ['Better luck next time'],
        'unbouncepages.com'  => ['The requested URL was not found on this server'],
        'vercel.app'         => ['The deployment could not be found', 'DEPLOYMENT_NOT_FOUND', 'This deployment has been disabled'],
        'vercel-dns.com'     => ['The deployment could not be found', 'DEPLOYMENT_NOT_FOUND'],
        'onrender.com'       => ['No Such App', 'There\'s nothing here, yet'],
        'railway.app'        => ['Application not found', 'This application has no deployed services'],
        'fly.dev'            => ['404: No such app', 'fly.io/docs'],
        'flyapps.io'         => ['404: No such app'],
        'firebaseapp.com'    => ['Firebase App Not Found', 'Requested route doesn\'t match'],
        'web.app'            => ['Firebase App Not Found', 'Requested route doesn\'t match'],
        'ondigitalocean.app' => ['Service Not Found', '404 Page Not Found'],
        'glitch.me'          => ['No such app'],
        'replit.dev'         => ['No such Repl'],
        'myshopify.com'      => ['Sorry, this shop is currently unavailable'],
        'wpengine.com'       => ['The site you were looking for couldn\'t be found'],
    ];

    /** Common subdomains to check in addition to the main host */
    private const COMMON_SUBDOMAINS = [
        'www', 'dev', 'staging', 'test', 'blog', 'shop',
        'api', 'cdn', 'mail', 'status', 'docs', 'help',
        'support', 'app', 'portal', 'assets',
    ];

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 100;

        // Strip www to get apex domain for subdomain generation
        $apex    = preg_replace('/^www\./i', '', $host);
        $targets = [$host];
        foreach (self::COMMON_SUBDOMAINS as $sub) {
            $candidate = "{$sub}.{$apex}";
            if ($candidate !== $host) {
                $targets[] = $candidate;
            }
        }

        $vulnerable = [];
        $checked    = 0;

        foreach (array_unique($targets) as $target) {
            $result = $this->safe(fn() => $this->checkTarget($target), null);
            if ($result === null) {
                continue;
            }
            $checked++;
            if ($result['vulnerable']) {
                $vulnerable[] = array_merge(['host' => $target], $result);
            }
        }

        if (empty($vulnerable)) {
            $score = 100;
            $note  = $checked > 0 ? " ({$checked} subdomains checked)" : '';
            $checks[] = [
                'id'          => 'takeover_clean',
                'label'       => 'Subdomain takeover',
                'status'      => 'pass',
                'description' => "No subdomain takeover vulnerabilities detected{$note}.",
            ];
        } else {
            foreach ($vulnerable as $v) {
                $checks[] = [
                    'id'             => 'takeover_' . md5($v['host']),
                    'label'          => 'Subdomain takeover risk',
                    'status'         => 'fail',
                    'description'    => "{$v['host']} has a dangling CNAME to {$v['cname']} which appears unclaimed on {$v['service']}.",
                    'recommendation' => "Remove the DNS CNAME record for {$v['host']} pointing to {$v['cname']}, or claim the resource at that service. An attacker could take over this subdomain and serve malicious content.",
                ];
            }
        }

        return [
            'category' => 'Subdomain Takeover',
            'icon'     => 'server',
            'score'    => $score,
            'checks'   => $checks,
        ];
    }

    private function checkTarget(string $target): ?array
    {
        $records = @dns_get_record($target, DNS_CNAME);
        if (empty($records)) {
            return null;
        }

        $cname = $records[0]['target'] ?? null;
        if (! $cname) {
            return null;
        }

        // Check if CNAME points to a known takeover-prone service
        $matchedService = null;
        $cnameLower     = strtolower($cname);
        foreach (self::FINGERPRINTS as $pattern => $fingerprints) {
            if (str_ends_with($cnameLower, strtolower($pattern)) || str_contains($cnameLower, strtolower($pattern))) {
                $matchedService = $pattern;
                break;
            }
        }

        if ($matchedService === null) {
            return null;
        }

        // Fetch the target and look for takeover fingerprints
        $body = $this->safe(fn() => $this->fetchBody($target), '');
        foreach (self::FINGERPRINTS[$matchedService] as $fingerprint) {
            if (str_contains($body, $fingerprint)) {
                return ['vulnerable' => true, 'cname' => $cname, 'service' => $matchedService];
            }
        }

        return ['vulnerable' => false, 'cname' => $cname, 'service' => $matchedService];
    }

    private function fetchBody(string $host): string
    {
        foreach (['https', 'http'] as $scheme) {
            $ch = curl_init("{$scheme}://{$host}");
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
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body && $code > 0 && $code < 500) {
                return (string) $body;
            }
        }

        return '';
    }

}
