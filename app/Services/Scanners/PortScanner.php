<?php

namespace App\Services\Scanners;

class PortScanner
{
    private const TIMEOUT = 1.0; // seconds per port (float supported by stream_socket_client)

    private const PORTS = [
        21    => ['label' => 'FTP',           'dangerous' => true,
                  'desc_open'   => 'FTP (port 21) is publicly reachable. FTP transmits data including passwords in plaintext.',
                  'rec'         => 'Disable FTP and use SFTP (SSH File Transfer Protocol) instead. If FTP is needed, use FTPS (FTP over TLS).'],
        22    => ['label' => 'SSH',           'dangerous' => false,
                  'desc_open'   => 'SSH (port 22) is open. This is common for server management — ensure key-based authentication is enforced and password login is disabled.',
                  'rec'         => 'Disable password authentication: set "PasswordAuthentication no" in /etc/ssh/sshd_config. Consider moving SSH to a non-standard port.'],
        23    => ['label' => 'Telnet',        'dangerous' => true,
                  'desc_open'   => 'Telnet (port 23) is open. Telnet is completely unencrypted — credentials and all data are transmitted in plaintext.',
                  'rec'         => 'Disable Telnet immediately and replace with SSH. Telnet has been obsolete since SSH became available in the 1990s.'],
        3306  => ['label' => 'MySQL',         'dangerous' => true,
                  'desc_open'   => 'MySQL (port 3306) is publicly reachable. Database servers should never be directly accessible from the internet.',
                  'rec'         => 'Block port 3306 in your firewall. Only allow MySQL connections from trusted internal IPs or via SSH tunneling.'],
        5432  => ['label' => 'PostgreSQL',    'dangerous' => true,
                  'desc_open'   => 'PostgreSQL (port 5432) is publicly reachable. Database ports should never be exposed to the internet.',
                  'rec'         => 'Block port 5432 in your firewall (iptables, UFW, or cloud security groups). Allow connections only from application servers.'],
        6379  => ['label' => 'Redis',         'dangerous' => true,
                  'desc_open'   => 'Redis (port 6379) is publicly reachable. Redis has no authentication by default and exposed instances are frequently exploited for data theft and cryptomining.',
                  'rec'         => 'Block port 6379 immediately. Bind Redis to 127.0.0.1 in redis.conf and enable AUTH password if external access is needed.'],
        27017 => ['label' => 'MongoDB',       'dangerous' => true,
                  'desc_open'   => 'MongoDB (port 27017) is publicly reachable. Exposed MongoDB instances have been repeatedly mass-wiped by attackers demanding ransom.',
                  'rec'         => 'Block port 27017 in your firewall. Bind MongoDB to 127.0.0.1 and enable authentication in mongod.conf.'],
        9200  => ['label' => 'Elasticsearch', 'dangerous' => true,
                  'desc_open'   => 'Elasticsearch (port 9200) is publicly reachable. Exposed Elasticsearch has caused numerous major data breaches affecting millions of records.',
                  'rec'         => 'Block port 9200 in your firewall. Elasticsearch should never be directly internet-facing — put it behind an application layer.'],
        11211 => ['label' => 'Memcached',     'dangerous' => true,
                  'desc_open'   => 'Memcached (port 11211) is publicly reachable. Exposed Memcached servers are abused for DDoS amplification attacks and data exfiltration.',
                  'rec'         => 'Block port 11211 in your firewall. Bind Memcached to 127.0.0.1 using the -l 127.0.0.1 flag.'],
    ];

    public function scan(string $host): array
    {
        $ip = $this->safe(fn() => $this->resolveIp($host), null);

        $checks     = [];
        $openDanger = 0;

        foreach (self::PORTS as $port => $info) {
            $open = $ip ? $this->safe(fn() => $this->isPortOpen($ip, $port), false) : false;

            if ($open && $info['dangerous']) {
                $openDanger++;
                $checks[] = [
                    'id'             => "port_{$port}",
                    'label'          => "{$info['label']} (port {$port})",
                    'status'         => 'fail',
                    'description'    => $info['desc_open'],
                    'recommendation' => $info['rec'],
                ];
            } elseif ($open) {
                // SSH — informational warning
                $checks[] = [
                    'id'             => "port_{$port}",
                    'label'          => "{$info['label']} (port {$port})",
                    'status'         => 'warn',
                    'description'    => $info['desc_open'],
                    'recommendation' => $info['rec'],
                ];
            } else {
                $checks[] = [
                    'id'          => "port_{$port}",
                    'label'       => "{$info['label']} (port {$port})",
                    'status'      => 'pass',
                    'description' => "{$info['label']} (port {$port}) is not publicly reachable — good.",
                ];
            }
        }

        return [
            'category'    => 'Open Ports',
            'icon'        => 'server',
            'score'       => null,
            'open_danger' => $openDanger,
            'checks'      => $checks,
        ];
    }

    private function resolveIp(string $host): ?string
    {
        $ip = @gethostbyname($host);
        return ($ip && $ip !== $host) ? $ip : null;
    }

    private function isPortOpen(string $ip, int $port): bool
    {
        $sock = @stream_socket_client(
            "tcp://{$ip}:{$port}",
            $errno,
            $errstr,
            self::TIMEOUT,
            STREAM_CLIENT_CONNECT
        );

        if ($sock) {
            fclose($sock);
            return true;
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
