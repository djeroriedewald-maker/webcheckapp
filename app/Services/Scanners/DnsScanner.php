<?php

namespace App\Services\Scanners;

class DnsScanner
{
    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        // SPF, DMARC and CAA always live on the apex domain, never on www.
        // Strip the www. prefix so scans of www.example.com check the right place.
        $apexHost = preg_replace('/^www\./i', '', $host);

        // --- Check 1: SPF record (email spoofing protection) ---
        $maxScore += 35;
        $spf = $this->safe(fn() => $this->checkSpf($apexHost), ['found' => false]);
        if ($spf['found'] && !empty($spf['multiple'])) {
            // RFC 7208: exactly one SPF record allowed — multiple = broken SPF
            $checks[] = [
                'id'             => 'dns_spf',
                'label'          => 'SPF record configured',
                'status'         => 'fail',
                'description'    => 'Multiple SPF records found. RFC 7208 requires exactly one SPF record — having multiple causes SPF validation to fail.',
                'recommendation' => 'Remove duplicate SPF records and merge all mail providers into a single TXT record.',
            ];
        } elseif ($spf['found'] && empty($spf['permissive'])) {
            $score += 35;
            $checks[] = [
                'id'          => 'dns_spf',
                'label'       => 'SPF record configured',
                'status'      => 'pass',
                'description' => "SPF record found: \"{$spf['value']}\".",
            ];
        } elseif ($spf['found'] && !empty($spf['permissive'])) {
            // +all means anyone can send — record exists but offers no protection
            $checks[] = [
                'id'             => 'dns_spf',
                'label'          => 'SPF record configured',
                'status'         => 'fail',
                'description'    => "SPF record uses \"+all\" which allows any server to send email as your domain. This provides no protection against spoofing: \"{$spf['value']}\".",
                'recommendation' => 'Replace \"+all\" with \"~all\" (softfail) or \"-all\" (hard fail) to restrict which servers may send mail for your domain.',
            ];
        } else {
            $checks[] = [
                'id'             => 'dns_spf',
                'label'          => 'SPF record configured',
                'status'         => 'fail',
                'description'    => 'No SPF record found. Anyone can send emails pretending to be from your domain.',
                'recommendation' => 'Add a TXT record to your DNS: v=spf1 include:yourmailprovider.com ~all',
            ];
        }

        // --- Check 2: DMARC record (email authentication policy) ---
        $maxScore += 35;
        $dmarc = $this->safe(fn() => $this->checkDmarc($apexHost), ['found' => false, 'policy' => null]);
        if ($dmarc['found']) {
            $score += $dmarc['policy'] === 'none' ? 10 : 35;
            $status = $dmarc['policy'] === 'none' ? 'warn' : 'pass';
            $description = $dmarc['policy'] === 'none'
                ? "DMARC found but policy is \"none\" — emails are monitored but not rejected. Value: \"{$dmarc['value']}\"."
                : "DMARC record found with policy \"{$dmarc['policy']}\": \"{$dmarc['value']}\".";
            $recommendation = $dmarc['policy'] === 'none'
                ? 'Upgrade your DMARC policy from p=none to p=quarantine or p=reject to actively block spoofed emails.'
                : null;
            $check = [
                'id'          => 'dns_dmarc',
                'label'       => 'DMARC record configured',
                'status'      => $status,
                'description' => $description,
            ];
            if ($recommendation) {
                $check['recommendation'] = $recommendation;
            }
            $checks[] = $check;
        } else {
            $checks[] = [
                'id'             => 'dns_dmarc',
                'label'          => 'DMARC record configured',
                'status'         => 'fail',
                'description'    => 'No DMARC record found at _dmarc.' . $apexHost . '.',
                'recommendation' => 'Add a TXT record to _dmarc.' . $apexHost . ': v=DMARC1; p=quarantine; rua=mailto:dmarc@' . $apexHost,
            ];
        }

        // --- Check 3: CAA record (restricts who can issue SSL certs) ---
        $maxScore += 30;
        $caa = $this->safe(fn() => $this->checkCaa($apexHost), ['found' => false, 'checked' => true]);
        if ($caa['found']) {
            $score += 30;
            $checks[] = [
                'id'          => 'dns_caa',
                'label'       => 'CAA record configured',
                'status'      => 'pass',
                'description' => 'CAA record found — only authorized Certificate Authorities can issue SSL certificates for this domain.',
            ];
        } elseif (! $caa['checked']) {
            // DNS_CAA not available on this PHP build — cannot check
            $score += 15; // neutral: we can't verify either way
            $checks[] = [
                'id'          => 'dns_caa',
                'label'       => 'CAA record configured',
                'status'      => 'warn',
                'description' => 'CAA record check is not supported on this server. Verify manually.',
            ];
        } else {
            $checks[] = [
                'id'             => 'dns_caa',
                'label'          => 'CAA record configured',
                'status'         => 'warn',
                'description'    => 'No CAA record found. Any Certificate Authority can issue SSL certs for your domain.',
                'recommendation' => 'Add a CAA DNS record, e.g.: 0 issue "letsencrypt.org" to restrict SSL issuance.',
            ];
        }

        // --- Informational: DNSSEC (not scored — PHP cannot reliably verify this) ---
        // We show it as informational only. Most systems will report not confirmed,
        // even for domains that do have DNSSEC, because php dns_get_record uses the
        // system resolver which strips DNSSEC records.
        $dnssec = $this->safe(fn() => $this->checkDnssec($apexHost), false);
        $checks[] = [
            'id'          => 'dns_dnssec',
            'label'       => 'DNSSEC',
            'status'      => $dnssec ? 'pass' : 'warn',
            'description' => $dnssec
                ? 'DNSSEC signatures detected for this domain.'
                : 'DNSSEC could not be confirmed via this check. Verify with your domain registrar.',
            'recommendation' => $dnssec ? null : 'Enable DNSSEC through your domain registrar to protect against DNS cache poisoning.',
        ];

        return [
            'category' => 'DNS & Email Security',
            'icon'     => 'server',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function checkSpf(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT);
        if (! $records) {
            return ['found' => false];
        }

        $spfRecords = [];
        foreach ($records as $record) {
            $txt = $record['txt'] ?? ($record['entries'][0] ?? '');
            if (str_starts_with($txt, 'v=spf1')) {
                $spfRecords[] = $txt;
            }
        }

        if (empty($spfRecords)) {
            return ['found' => false];
        }

        // RFC 7208 §3.2: a domain MUST NOT have more than one SPF record
        if (count($spfRecords) > 1) {
            return ['found' => true, 'multiple' => true, 'value' => implode(' | ', $spfRecords), 'permissive' => false];
        }

        $txt = $spfRecords[0];
        $dangerouslyPermissive = (bool) preg_match('/\+all\b/i', $txt);

        return ['found' => true, 'multiple' => false, 'value' => $txt, 'permissive' => $dangerouslyPermissive];
    }

    private function checkDmarc(string $host): array
    {
        $records = @dns_get_record("_dmarc.{$host}", DNS_TXT);
        if (! $records) {
            return ['found' => false, 'policy' => null, 'value' => null];
        }

        foreach ($records as $record) {
            $txt = $record['txt'] ?? ($record['entries'][0] ?? '');
            if (str_starts_with($txt, 'v=DMARC1')) {
                $policy = null;
                if (preg_match('/\bp=(\w+)/i', $txt, $m)) {
                    $policy = strtolower($m[1]);
                }
                return ['found' => true, 'policy' => $policy, 'value' => $txt];
            }
        }

        return ['found' => false, 'policy' => null, 'value' => null];
    }

    private function checkCaa(string $host): array
    {
        if (! defined('DNS_CAA')) {
            // Cannot perform this check on this server build
            return ['found' => false, 'checked' => false];
        }

        $records = @dns_get_record($host, DNS_CAA);

        return ['found' => ! empty($records), 'checked' => true];
    }

    private function checkDnssec(string $host): bool
    {
        // Note: This is a best-effort check. Standard PHP DNS resolvers
        // typically strip DNSSEC records. A false result does NOT mean
        // DNSSEC is definitely not configured.
        $records = @dns_get_record($host, DNS_ANY);
        if (! $records) {
            return false;
        }

        foreach ($records as $record) {
            if (in_array($record['type'] ?? '', ['DS', 'DNSKEY', 'RRSIG'])) {
                return true;
            }
        }

        return false;
    }

    private function safe(callable $fn, mixed $default): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return $default;
        }
    }
}
