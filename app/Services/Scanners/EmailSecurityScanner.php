<?php

namespace App\Services\Scanners;

class EmailSecurityScanner
{
    use HasSafeCall;

    private const TIMEOUT = 8;

    public function scan(string $host): array
    {
        $checks = [];
        $score  = 0;
        $max    = 40;

        // Check 1: SPF record strictness
        $spfResult = $this->safe(fn() => $this->checkSpfStrictness($host), null);
        if ($spfResult === 'strict') {
            $score += 10;
            $checks[] = [
                'id'          => 'email_spf_strict',
                'label'       => 'SPF record is strict (-all)',
                'status'      => 'pass',
                'description' => 'SPF record ends with -all (hard fail), properly preventing email spoofing.',
            ];
        } elseif ($spfResult === 'softfail') {
            $score += 5;
            $checks[] = [
                'id'             => 'email_spf_soft',
                'label'          => 'SPF uses soft fail (~all)',
                'status'         => 'warn',
                'description'    => 'SPF record uses ~all (soft fail) instead of -all (hard fail). Spoofed emails may still be delivered.',
                'recommendation' => 'Change SPF policy from ~all to -all once you have confirmed all legitimate mail sources are included.',
            ];
        } elseif ($spfResult === 'neutral') {
            $checks[] = [
                'id'             => 'email_spf_neutral',
                'label'          => 'SPF uses neutral policy (?all)',
                'status'         => 'fail',
                'description'    => 'SPF record uses ?all (neutral), providing no protection against email spoofing.',
                'recommendation' => 'Change SPF policy to -all to reject unauthorized senders.',
            ];
        } else {
            $checks[] = [
                'id'             => 'email_spf_missing',
                'label'          => 'No SPF record found',
                'status'         => 'fail',
                'description'    => 'No SPF record is configured for this domain, allowing anyone to send email as this domain.',
                'recommendation' => 'Add an SPF TXT record: "v=spf1 include:your-mail-provider -all".',
            ];
        }

        // Check 2: DMARC policy strictness
        $dmarcResult = $this->safe(fn() => $this->checkDmarcStrictness($host), null);
        if ($dmarcResult === 'reject') {
            $score += 15;
            $checks[] = [
                'id'          => 'email_dmarc_reject',
                'label'       => 'DMARC policy is reject',
                'status'      => 'pass',
                'description' => 'DMARC policy is set to reject, providing maximum protection against email spoofing and phishing.',
            ];
        } elseif ($dmarcResult === 'quarantine') {
            $score += 10;
            $checks[] = [
                'id'             => 'email_dmarc_quarantine',
                'label'          => 'DMARC policy is quarantine',
                'status'         => 'warn',
                'description'    => 'DMARC policy is set to quarantine. For maximum protection, consider upgrading to reject.',
                'recommendation' => 'After monitoring DMARC reports to confirm no legitimate mail is affected, change policy from p=quarantine to p=reject.',
            ];
        } elseif ($dmarcResult === 'none') {
            $score += 3;
            $checks[] = [
                'id'             => 'email_dmarc_none',
                'label'          => 'DMARC policy is none (monitoring only)',
                'status'         => 'warn',
                'description'    => 'DMARC policy is set to none, meaning failed emails are still delivered. This only monitors, it does not protect.',
                'recommendation' => 'Progress DMARC policy from p=none to p=quarantine, then to p=reject after monitoring reports.',
            ];
        } else {
            $checks[] = [
                'id'             => 'email_dmarc_missing',
                'label'          => 'No DMARC record found',
                'status'         => 'fail',
                'description'    => 'No DMARC record found. Without DMARC, receiving servers cannot verify your email authentication policies.',
                'recommendation' => 'Add a DMARC TXT record on _dmarc.yourdomain.com: "v=DMARC1; p=reject; rua=mailto:dmarc@yourdomain.com".',
            ];
        }

        // Check 3: MX record check
        $mxRecords = $this->safe(fn() => dns_get_record($host, DNS_MX), []);
        if (! empty($mxRecords)) {
            $score += 5;
            $checks[] = [
                'id'          => 'email_mx_present',
                'label'       => 'MX records configured',
                'status'      => 'pass',
                'description' => count($mxRecords) . ' MX record(s) found for this domain.',
            ];
        } else {
            $checks[] = [
                'id'          => 'email_mx_missing',
                'label'       => 'No MX records found',
                'status'      => 'warn',
                'description' => 'No MX records found. This domain cannot receive email. If email is not needed, consider adding a null MX record (RFC 7505).',
            ];
        }

        // Check 4: SMTP banner check (only if MX exists)
        if (! empty($mxRecords)) {
            $mxHost = $mxRecords[0]['target'] ?? null;
            $bannerResult = $this->safe(fn() => $this->checkSmtpBanner($mxHost), null);

            if ($bannerResult === null) {
                $score += 5;
                $checks[] = [
                    'id'          => 'email_smtp_banner',
                    'label'       => 'SMTP banner not exposing version',
                    'status'      => 'pass',
                    'description' => 'Could not retrieve SMTP banner or banner does not disclose software version.',
                ];
            } elseif ($bannerResult) {
                $checks[] = [
                    'id'             => 'email_smtp_version',
                    'label'          => 'SMTP banner discloses version info',
                    'status'         => 'warn',
                    'description'    => "SMTP banner reveals: {$bannerResult}. Version information helps attackers target specific exploits.",
                    'recommendation' => 'Configure your mail server to show a generic banner without version information.',
                ];
            } else {
                $score += 5;
                $checks[] = [
                    'id'          => 'email_smtp_clean',
                    'label'       => 'SMTP banner is clean',
                    'status'      => 'pass',
                    'description' => 'SMTP banner does not disclose software version information.',
                ];
            }
        }

        // Check 5: DANE/TLSA record (bonus)
        $tlsaRecords = $this->safe(fn() => dns_get_record("_25._tcp.{$host}", DNS_ANY), []);
        $hasTlsa = false;
        foreach ($tlsaRecords as $record) {
            if (($record['type'] ?? '') === 'TLSA') {
                $hasTlsa = true;
                break;
            }
        }
        if ($hasTlsa) {
            $score += 5;
            $checks[] = [
                'id'          => 'email_dane',
                'label'       => 'DANE/TLSA record present',
                'status'      => 'pass',
                'description' => 'A DANE TLSA record is published, enabling certificate verification for mail transport security.',
            ];
        } else {
            $checks[] = [
                'id'          => 'email_no_dane',
                'label'       => 'No DANE/TLSA record',
                'status'      => 'warn',
                'description' => 'No DANE TLSA record found. DANE provides additional verification for TLS certificates on mail servers.',
            ];
        }

        return [
            'category' => 'Email Security',
            'icon'     => 'envelope',
            'score'    => $max > 0 ? (int) round(($score / $max) * 100) : 100,
            'checks'   => $checks,
        ];
    }

    private function checkSpfStrictness(string $host): ?string
    {
        $records = dns_get_record($host, DNS_TXT);
        foreach ($records ?: [] as $record) {
            $txt = $record['txt'] ?? '';
            if (stripos($txt, 'v=spf1') !== false) {
                if (str_contains($txt, '-all')) return 'strict';
                if (str_contains($txt, '~all')) return 'softfail';
                if (str_contains($txt, '?all')) return 'neutral';
                return 'softfail'; // no explicit all = permissive
            }
        }
        return null;
    }

    private function checkDmarcStrictness(string $host): ?string
    {
        $records = dns_get_record("_dmarc.{$host}", DNS_TXT);
        foreach ($records ?: [] as $record) {
            $txt = $record['txt'] ?? '';
            if (stripos($txt, 'v=DMARC1') !== false) {
                if (preg_match('/p\s*=\s*reject/i', $txt)) return 'reject';
                if (preg_match('/p\s*=\s*quarantine/i', $txt)) return 'quarantine';
                if (preg_match('/p\s*=\s*none/i', $txt)) return 'none';
                return 'none';
            }
        }
        return null;
    }

    private function checkSmtpBanner(string $mxHost): ?string
    {
        $fp = @fsockopen($mxHost, 25, $errno, $errstr, 5);
        if (! $fp) return null;

        stream_set_timeout($fp, 5);
        $banner = fgets($fp, 1024);
        fclose($fp);

        if (! $banner) return null;

        // Check for version disclosure patterns
        if (preg_match('/(Postfix|Exim|Sendmail|Exchange|Dovecot|hMailServer)[\s\/]\d/i', $banner, $m)) {
            return trim($banner);
        }

        return false; // Banner exists but no version info
    }
}
