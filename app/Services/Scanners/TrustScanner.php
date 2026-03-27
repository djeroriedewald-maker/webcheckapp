<?php

namespace App\Services\Scanners;

class TrustScanner
{
    private const TIMEOUT = 7;

    public function scan(string $host): array
    {
        $apexHost = preg_replace('/^www\./i', '', $host);

        // Resolve IP once — reused for geolocation and DNS comparison
        $ip = $this->resolveIp($host);

        // 1. Geolocation
        $location = $ip ? $this->safe(fn() => $this->getGeoLocation($ip), null) : null;

        // 2. Domain age via RDAP
        $domainAge = $this->safe(fn() => $this->getDomainAge($apexHost), null);

        // 3. Security DNS checks (Quad9, DNSFilter)
        $quad9Result     = $this->safe(fn() => $this->checkDoh('https://dns.quad9.net/dns-query', $host, $ip), 'unknown');
        $dnsfilterResult = $this->safe(fn() => $this->checkDnsFilter($host, $ip), 'unknown');

        // 4. APWG — backed by Spamhaus DBL (the primary phishing/malware domain list)
        $apwgResult = $this->safe(fn() => $this->checkSpamhausDBL($apexHost), 'unknown');

        // 5. IQ Global — IPQualityScore (optional, requires IPQS_KEY in .env)
        $ipqsResult = $this->safe(fn() => $this->checkIpqs($host), null);

        // Build checks array
        $checks   = [];
        $threats  = 0;
        $warnings = 0;

        // Location (informational)
        $checks[] = $this->buildLocationCheck($ip, $location);

        // Domain age
        $ageCheck = $this->buildDomainAgeCheck($domainAge);
        $checks[] = $ageCheck;
        if ($ageCheck['status'] === 'fail')  $threats++;
        if ($ageCheck['status'] === 'warn')  $warnings++;

        // Quad9
        $q9 = $this->buildDnsCheck('trust_quad9', 'Quad9', $quad9Result);
        $checks[] = $q9;
        if ($q9['status'] === 'fail') $threats++;

        // DNSFilter
        $df = $this->buildDnsCheck('trust_dnsfilter', 'DNSFilter', $dnsfilterResult);
        $checks[] = $df;
        if ($df['status'] === 'fail') $threats++;

        // APWG
        $apwg = $this->buildApwgCheck($apwgResult);
        $checks[] = $apwg;
        if ($apwg['status'] === 'fail') $threats++;

        // IQ Global / IPQS (only shown if API key is configured)
        if ($ipqsResult !== null) {
            $checks[] = $ipqsResult;
            if ($ipqsResult['status'] === 'fail') $threats++;
        }

        // 6. Expiry warning — add as a check if domain expires within 60 days
        if ($domainAge && isset($domainAge['expires_in_days'])) {
            $expiresIn = $domainAge['expires_in_days'];
            if ($expiresIn <= 0) {
                $expCheck = [
                    'id'             => 'trust_expiry',
                    'label'          => 'Domain expiry',
                    'status'         => 'fail',
                    'description'    => 'This domain has expired! It may be taken over by a third party at any moment.',
                    'recommendation' => 'Renew this domain immediately through your registrar.',
                ];
                $checks[] = $expCheck;
                $threats++;
            } elseif ($expiresIn <= 30) {
                $expCheck = [
                    'id'             => 'trust_expiry',
                    'label'          => 'Domain expiry',
                    'status'         => 'fail',
                    'description'    => "Domain expires in {$domainAge['expires_in_text']} ({$domainAge['expires_formatted']}). Domains that lapse are immediately available for others to register.",
                    'recommendation' => 'Renew this domain immediately to avoid losing it.',
                ];
                $checks[] = $expCheck;
                $threats++;
            } elseif ($expiresIn <= 60) {
                $checks[] = [
                    'id'             => 'trust_expiry',
                    'label'          => 'Domain expiry',
                    'status'         => 'warn',
                    'description'    => "Domain expires in {$domainAge['expires_in_text']} ({$domainAge['expires_formatted']}). Renew soon to avoid accidental loss.",
                    'recommendation' => 'Log in to your domain registrar and renew for at least one year.',
                ];
                $warnings++;
            }
        }

        return [
            'category'          => 'Trust & Reputation',
            'icon'              => 'shield-check',
            'score'             => null,
            'verdict'           => $this->calculateVerdict($threats, $warnings, $domainAge),
            'location'          => $this->buildLocationData($ip, $location),
            'domain_registered' => $domainAge ? $domainAge['registered_formatted'] : null,
            'domain_age_text'   => $domainAge ? $domainAge['age_text'] : null,
            'whois'             => $domainAge ? [
                'registered'   => $domainAge['registered_formatted'],
                'expires'      => $domainAge['expires_formatted'] ?? null,
                'expires_in'   => $domainAge['expires_in_text'] ?? null,
                'expires_soon' => isset($domainAge['expires_in_days']) && $domainAge['expires_in_days'] <= 60,
                'updated'      => $domainAge['updated_formatted'] ?? null,
                'registrar'    => $domainAge['registrar'] ?? null,
                'nameservers'  => $domainAge['nameservers'] ?? [],
                'status'       => $domainAge['status'] ?? [],
                'age_text'     => $domainAge['age_text'],
            ] : null,
            'checks'            => $checks,
        ];
    }

    // ── IP resolution ──────────────────────────────────────────────────────────

    private function resolveIp(string $host): ?string
    {
        $ip = @gethostbyname($host);
        return ($ip && $ip !== $host) ? $ip : null;
    }

    // ── Geolocation ────────────────────────────────────────────────────────────

    private function getGeoLocation(string $ip): ?array
    {
        $ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,isp,org");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        if (! $body) return null;
        $data = json_decode($body, true);

        return ($data && ($data['status'] ?? '') === 'success') ? $data : null;
    }

    private function buildLocationData(?string $ip, ?array $geo): array
    {
        return [
            'ip'           => $ip,
            'country'      => $geo['country'] ?? null,
            'country_code' => $geo['countryCode'] ?? null,
            'city'         => $geo['city'] ?? null,
            'org'          => $geo['isp'] ?? $geo['org'] ?? null,
        ];
    }

    private function buildLocationCheck(?string $ip, ?array $geo): array
    {
        if ($geo) {
            $desc = "Server located in {$geo['city']}, {$geo['country']}";
            $org  = $geo['isp'] ?? $geo['org'] ?? null;
            if ($org) $desc .= " — hosted by {$org}";
            $desc .= ". IP: {$ip}.";
        } elseif ($ip) {
            $desc = "Server IP address: {$ip}. Location lookup unavailable.";
        } else {
            $desc = 'Could not resolve the server IP address.';
        }

        return [
            'id'          => 'trust_location',
            'label'       => 'Server Location',
            'status'      => 'info',
            'description' => $desc,
        ];
    }

    // ── Domain age via RDAP ────────────────────────────────────────────────────

    private function getDomainAge(string $host): ?array
    {
        $ch = curl_init("https://rdap.org/domain/{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || ! $body) return null;

        $data = json_decode($body, true);
        if (! $data || empty($data['events'])) return null;

        // ── Parse events ──────────────────────────────────────────────────────
        $registered = null;
        $expires    = null;
        $updated    = null;

        foreach ($data['events'] as $event) {
            $action = strtolower($event['eventAction'] ?? '');
            $date   = $event['eventDate'] ?? null;
            if (! $date) continue;

            if ($action === 'registration')  $registered = $date;
            elseif ($action === 'expiration') $expires   = $date;
            elseif ($action === 'last changed') $updated  = $date;
        }

        if (! $registered) return null;

        // ── Parse registrar (from entities array) ─────────────────────────────
        $registrar = null;
        foreach ($data['entities'] ?? [] as $entity) {
            if (in_array('registrar', $entity['roles'] ?? [], true)) {
                // vCard fn field holds the registrar name
                foreach ($entity['vcardArray'][1] ?? [] as $vcard) {
                    if (($vcard[0] ?? '') === 'fn' && ! empty($vcard[3])) {
                        $registrar = $vcard[3];
                        break;
                    }
                }
                // Fallback: publicIds (IANA registrar ID with name)
                if (! $registrar) {
                    foreach ($entity['publicIds'] ?? [] as $pub) {
                        if (($pub['type'] ?? '') === 'IANA Registrar ID') {
                            $registrar = 'IANA #' . $pub['identifier'];
                            break;
                        }
                    }
                }
                break;
            }
        }

        // ── Parse nameservers ─────────────────────────────────────────────────
        $nameservers = [];
        foreach ($data['nameservers'] ?? [] as $ns) {
            if (! empty($ns['ldhName'])) {
                $nameservers[] = strtolower(rtrim($ns['ldhName'], '.'));
            }
        }

        // ── Parse status ──────────────────────────────────────────────────────
        $statusRaw = $data['status'] ?? [];
        // Human-readable labels for common RDAP status codes
        $statusLabels = [
            'active'                   => 'Active',
            'inactive'                 => 'Inactive',
            'clientTransferProhibited' => 'Transfer locked',
            'clientDeleteProhibited'   => 'Delete locked',
            'clientUpdateProhibited'   => 'Update locked',
            'serverTransferProhibited' => 'Transfer locked (registry)',
            'serverDeleteProhibited'   => 'Delete locked (registry)',
            'pendingDelete'            => 'Pending deletion',
            'redemptionPeriod'         => 'Redemption period',
            'pendingTransfer'          => 'Pending transfer',
        ];
        $statusFormatted = array_values(array_filter(array_map(
            fn($s) => $statusLabels[$s] ?? null,
            $statusRaw
        )));

        try {
            $utc     = new \DateTimeZone('UTC');
            $now     = new \DateTime('now', $utc);
            $regDate = new \DateTime($registered, $utc);
            $ageDays = (int) $now->diff($regDate)->days;

            $result = [
                'registered_formatted' => $regDate->format('d M Y'),
                'age_days'             => $ageDays,
                'age_text'             => $this->formatAge($ageDays),
                'registrar'            => $registrar,
                'nameservers'          => $nameservers,
                'status'               => $statusFormatted,
            ];

            if ($expires) {
                $expDate          = new \DateTime($expires, $utc);
                $expDiff          = $now->diff($expDate);
                $expiresInDays    = $expDate > $now ? (int) $expDiff->days : -(int) $expDiff->days;

                $result['expires_formatted'] = $expDate->format('d M Y');
                $result['expires_in_days']   = $expiresInDays;
                $result['expires_in_text']   = $expiresInDays > 0
                    ? $this->formatAge($expiresInDays)
                    : 'expired';
            }

            if ($updated) {
                $result['updated_formatted'] = (new \DateTime($updated, $utc))->format('d M Y');
            }

            return $result;
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatAge(int $days): string
    {
        if ($days < 30)  return "{$days} day" . ($days === 1 ? '' : 's');
        if ($days < 365) {
            $m = (int) round($days / 30.5);
            return "{$m} month" . ($m === 1 ? '' : 's');
        }
        $y = round($days / 365.25, 1);
        return rtrim(rtrim((string) $y, '0'), '.') . ' year' . ($y == 1 ? '' : 's');
    }

    private function buildDomainAgeCheck(?array $age): array
    {
        if (! $age) {
            return [
                'id'          => 'trust_domain_age',
                'label'       => 'Domain age',
                'status'      => 'info',
                'description' => 'Domain registration date could not be determined.',
            ];
        }

        $days = $age['age_days'];
        $text = $age['age_text'];
        $date = $age['registered_formatted'];

        if ($days < 30) {
            return [
                'id'             => 'trust_domain_age',
                'label'          => 'Domain age',
                'status'         => 'fail',
                'description'    => "Domain registered only {$text} ago ({$date}). Very new domains are frequently used for phishing and scams.",
                'recommendation' => 'Be extremely cautious — this domain was registered very recently.',
            ];
        }

        if ($days < 180) {
            return [
                'id'             => 'trust_domain_age',
                'label'          => 'Domain age',
                'status'         => 'warn',
                'description'    => "Domain was registered {$text} ago ({$date}). Relatively new domains carry a higher risk of fraud.",
                'recommendation' => 'Verify the legitimacy of this website before submitting personal information.',
            ];
        }

        return [
            'id'          => 'trust_domain_age',
            'label'       => 'Domain age',
            'status'      => 'pass',
            'description' => "Domain has been registered for {$text} (since {$date}).",
        ];
    }

    // ── DNS-over-HTTPS security checks ─────────────────────────────────────────

    /**
     * Query a DoH resolver and compare result to what we already resolved via system DNS.
     * If the domain resolves normally (system DNS) but returns NXDOMAIN on the security
     * resolver, the domain has been blocked by that resolver.
     */
    private function checkDoh(string $dohUrl, string $host, ?string $resolvedIp): string
    {
        $ch = curl_init("{$dohUrl}?name={$host}&type=A");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => ['Accept: application/dns-json'],
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || ! $body) return 'unknown';

        $data = json_decode($body, true);
        if (! is_array($data)) return 'unknown';

        $status = $data['Status'] ?? -1;

        // NOERROR with answer = resolves = not blocked
        if ($status === 0 && ! empty($data['Answer'])) return 'clean';

        // NXDOMAIN on security DNS but the domain resolves normally → blocked
        if ($status === 3 && $resolvedIp !== null) return 'blocked';

        return 'unknown';
    }

    private function checkDnsFilter(string $host, ?string $resolvedIp): string
    {
        // DNSFilter provides a public threat-intelligence endpoint.
        // We use their DoH resolver; malicious domains return NXDOMAIN.
        return $this->checkDoh('https://doh.dnsfilter.com/dns-query', $host, $resolvedIp);
    }

    private function buildDnsCheck(string $id, string $service, string $result): array
    {
        return match($result) {
            'clean'   => [
                'id'          => $id,
                'label'       => $service,
                'status'      => 'pass',
                'description' => "{$service} has not flagged this domain — no known threats detected.",
            ],
            'blocked' => [
                'id'             => $id,
                'label'          => $service,
                'status'         => 'fail',
                'description'    => "{$service}'s security filter is blocking this domain. It has been identified as malware, phishing, or otherwise malicious.",
                'recommendation' => 'Do not enter personal information or credentials on this website.',
            ],
            default   => [
                'id'          => $id,
                'label'       => $service,
                'status'      => 'info',
                'description' => "Could not retrieve {$service} classification for this domain.",
            ],
        };
    }

    // ── APWG / Spamhaus DBL ────────────────────────────────────────────────────

    private function checkSpamhausDBL(string $host): string
    {
        // Spamhaus Domain Block List — the primary source behind APWG phishing data.
        // Returns NXDOMAIN for clean domains; 127.0.1.x for listed domains.
        $records = @dns_get_record("{$host}.dbl.spamhaus.org", DNS_A);

        if (! $records) return 'clean';

        foreach ($records as $record) {
            if (str_starts_with($record['ip'] ?? '', '127.0.1.')) {
                return 'listed';
            }
        }

        return 'clean';
    }

    private function buildApwgCheck(string $result): array
    {
        return match($result) {
            'listed' => [
                'id'             => 'trust_apwg',
                'label'          => 'APWG',
                'status'         => 'fail',
                'description'    => 'This domain is listed in the APWG / Spamhaus threat database as a known source of phishing, spam, or malware.',
                'recommendation' => 'Do not use this website — it has been flagged as malicious.',
            ],
            'clean'  => [
                'id'          => 'trust_apwg',
                'label'       => 'APWG',
                'status'      => 'pass',
                'description' => 'Domain is not listed in the APWG phishing and malware database.',
            ],
            default  => [
                'id'          => 'trust_apwg',
                'label'       => 'APWG',
                'status'      => 'info',
                'description' => 'Could not check APWG threat database status.',
            ],
        };
    }

    // ── IQ Global / IPQualityScore (optional) ─────────────────────────────────

    private function checkIpqs(string $host): ?array
    {
        $apiKey = config('services.ipqs.key') ?: env('IPQS_KEY');
        if (! $apiKey) return null;

        $url = "https://www.ipqualityscore.com/api/json/url/" . urlencode($apiKey) . "/" . urlencode("https://{$host}");
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        if (! $body) return null;
        $d = json_decode($body, true);
        if (! $d || ! ($d['success'] ?? false)) return null;

        $risk      = (int) ($d['risk_score'] ?? 0);
        $malware   = (bool) ($d['malware'] ?? false);
        $phishing  = (bool) ($d['phishing'] ?? false);
        $suspicious = (bool) ($d['suspicious'] ?? false);

        if ($malware || $phishing) {
            return [
                'id'             => 'trust_ipqs',
                'label'          => 'IQ Global',
                'status'         => 'fail',
                'description'    => 'IQ Global flagged this domain as ' . ($malware ? 'malware' : 'phishing') . " (risk score: {$risk}/100).",
                'recommendation' => 'This domain has been identified as malicious — do not enter personal data.',
            ];
        }

        if ($suspicious || $risk > 50) {
            return [
                'id'          => 'trust_ipqs',
                'label'       => 'IQ Global',
                'status'      => 'warn',
                'description' => "IQ Global considers this domain suspicious (risk score: {$risk}/100).",
            ];
        }

        return [
            'id'          => 'trust_ipqs',
            'label'       => 'IQ Global',
            'status'      => 'pass',
            'description' => "Domain appears clean according to IQ Global (risk score: {$risk}/100).",
        ];
    }

    // ── Verdict ────────────────────────────────────────────────────────────────

    private function calculateVerdict(int $threats, int $warnings, ?array $domainAge): array
    {
        if ($threats > 0) {
            return [
                'text'  => 'This website is likely unsafe',
                'level' => 'danger',
            ];
        }

        if ($domainAge && $domainAge['age_days'] < 30) {
            return [
                'text'  => 'Suspicious — very recently registered domain',
                'level' => 'danger',
            ];
        }

        if ($domainAge && $domainAge['age_days'] < 180) {
            return [
                'text'  => 'Proceed with caution — relatively new domain',
                'level' => 'warning',
            ];
        }

        if ($warnings > 0) {
            return [
                'text'  => 'Probably trustworthy, but has minor concerns',
                'level' => 'warning',
            ];
        }

        return [
            'text'  => 'This website is probably trustworthy',
            'level' => 'safe',
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
}
