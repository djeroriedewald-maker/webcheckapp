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
        $maxScore += 25;
        $spf = $this->checkSpf($host);
        if ($spf['found']) {
            $score += 25;
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
        $maxScore += 25;
        $dmarc = $this->checkDmarc($host);
        if ($dmarc['found']) {
            $score += 25;
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

        // DNSSEC
        $maxScore += 25;
        $dnssec = $this->checkDnssec($host);
        if ($dnssec) {
            $score += 25;
            $checks[] = [
                'id'          => 'dns_dnssec',
                'label'       => 'DNSSEC enabled',
                'status'      => 'pass',
                'description' => 'DNSSEC is enabled for this domain.',
            ];
        } else {
            $checks[] = [
                'id'          => 'dns_dnssec',
                'label'       => 'DNSSEC enabled',
                'status'      => 'warn',
                'description' => 'DNSSEC does not appear to be enabled.',
                'recommendation' => 'Enable DNSSEC through your domain registrar to protect against DNS spoofing.',
            ];
        }

        // CAA record
        $maxScore += 25;
        $caa = $this->checkCaa($host);
        if ($caa['found']) {
            $score += 25;
            $checks[] = [
                'id'          => 'dns_caa',
                'label'       => 'CAA record configured',
                'status'      => 'pass',
                'description' => 'CAA record found, restricting which CAs can issue certificates.',
            ];
        } else {
            $checks[] = [
                'id'          => 'dns_caa',
                'label'       => 'CAA record configured',
                'status'      => 'warn',
                'description' => 'No CAA record found.',
                'recommendation' => 'Add a CAA record to specify which Certificate Authorities may issue SSL certificates for your domain.',
            ];
        }

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

        foreach ($records as $record) {
            $txt = $record['txt'] ?? $record['entries'][0] ?? '';
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
            $txt = $record['txt'] ?? $record['entries'][0] ?? '';
            if (str_starts_with($txt, 'v=DMARC1')) {
                return ['found' => true, 'value' => $txt];
            }
        }

        return ['found' => false];
    }

    private function checkDnssec(string $host): bool
    {
        $records = @dns_get_record($host, DNS_DS + DNS_DNSKEY);
        return ! empty($records);
    }

    private function checkCaa(string $host): array
    {
        $records = @dns_get_record($host, DNS_CAA);
        if (! empty($records)) {
            return ['found' => true];
        }

        return ['found' => false];
    }
}
