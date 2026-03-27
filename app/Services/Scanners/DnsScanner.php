<?php

namespace App\Services\Scanners;

class DnsScanner
{
    public function scan(string $host): array
    {
        $checks = [];
        $score = 0;
        $maxScore = 0;

        // SPF record
        $maxScore += 30;
        $spf = $this->safe(fn() => $this->checkSpf($host), ['found' => false]);
        if ($spf['found']) {
            $score += 30;
            $checks[] = [
                'id'          => 'dns_spf',
                'label'       => 'SPF record configured',
                'status'      => 'pass',
                'description' => "SPF record found: \"{$spf['value']}\".",
            ];
        } else {
            $checks[] = [
                'id'          => 'dns_spf',
                'label'       => 'SPF record configured',
                'status'      => 'fail',
                'description' => 'No SPF record found in DNS.',
                'recommendation' => 'Add a TXT record to your DNS: v=spf1 include:yourmailserver.com ~all',
            ];
        }

        // DMARC record
        $maxScore += 30;
        $dmarc = $this->safe(fn() => $this->checkDmarc($host), ['found' => false]);
        if ($dmarc['found']) {
            $score += 30;
            $checks[] = [
                'id'          => 'dns_dmarc',
                'label'       => 'DMARC record configured',
                'status'      => 'pass',
                'description' => "DMARC record found: \"{$dmarc['value']}\".",
            ];
        } else {
            $checks[] = [
                'id'          => 'dns_dmarc',
                'label'       => 'DMARC record configured',
                'status'      => 'fail',
                'description' => 'No DMARC record found.',
                'recommendation' => 'Add a TXT record to _dmarc.yourdomain.com: v=DMARC1; p=quarantine; rua=mailto:dmarc@yourdomain.com',
            ];
        }

        // CAA record
        $maxScore += 20;
        $caa = $this->safe(fn() => $this->checkCaa($host), ['found' => false]);
        if ($caa['found']) {
            $score += 20;
            $checks[] = [
                'id'          => 'dns_caa',
                'label'       => 'CAA record configured',
                'status'      => 'pass',
                'description' => 'CAA record found — only authorized Certificate Authorities can issue SSL certificates for this domain.',
            ];
        } else {
            $checks[] = [
                'id'          => 'dns_caa',
                'label'       => 'CAA record configured',
                'status'      => 'warn',
                'description' => 'No CAA record found.',
                'recommendation' => 'Add a CAA DNS record to restrict which Certificate Authorities may issue SSL certs for your domain.',
            ];
        }

        // DNSSEC — note: basic check via DNS, result is indicative only
        $maxScore += 20;
        $dnssec = $this->safe(fn() => $this->checkDnssec($host), false);
        if ($dnssec) {
            $score += 20;
            $checks[] = [
                'id'          => 'dns_dnssec',
                'label'       => 'DNSSEC enabled',
                'status'      => 'pass',
                'description' => 'DNSSEC records detected for this domain.',
            ];
        } else {
            $checks[] = [
                'id'          => 'dns_dnssec',
                'label'       => 'DNSSEC enabled',
                'status'      => 'warn',
                'description' => 'DNSSEC could not be confirmed. Check with your domain registrar.',
                'recommendation' => 'Enable DNSSEC through your domain registrar to protect against DNS spoofing attacks.',
            ];
        }

        return [
            'category' => 'DNS & Email Security',
            'icon'     => 'server',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function safe(callable $fn, mixed $default): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return $default;
        }
    }

    private function checkSpf(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT);
        if (! $records) {
            return ['found' => false];
        }

        foreach ($records as $record) {
            $txt = $record['txt'] ?? ($record['entries'][0] ?? '');
            if (str_starts_with($txt, 'v=spf1')) {
                return ['found' => true, 'value' => $txt];
            }
        }

        return ['found' => false];
    }

    private function checkDmarc(string $host): array
    {
        $records = @dns_get_record("_dmarc.{$host}", DNS_TXT);
        if (! $records) {
            return ['found' => false];
        }

        foreach ($records as $record) {
            $txt = $record['txt'] ?? ($record['entries'][0] ?? '');
            if (str_starts_with($txt, 'v=DMARC1')) {
                return ['found' => true, 'value' => $txt];
            }
        }

        return ['found' => false];
    }

    private function checkCaa(string $host): array
    {
        // Try DNS_CAA directly first (PHP 7.0.16+)
        if (defined('DNS_CAA')) {
            $records = @dns_get_record($host, DNS_CAA);
            if (! empty($records)) {
                return ['found' => true];
            }
        }

        // Fallback: check via dig-style lookup using DNS_ANY
        $records = @dns_get_record($host, DNS_ANY);
        if ($records) {
            foreach ($records as $record) {
                if (($record['type'] ?? '') === 'CAA') {
                    return ['found' => true];
                }
            }
        }

        return ['found' => false];
    }

    private function checkDnssec(string $host): bool
    {
        // Check for DS records on the parent zone (most reliable via standard PHP DNS)
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
}
