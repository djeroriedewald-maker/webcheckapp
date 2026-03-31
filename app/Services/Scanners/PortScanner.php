<?php

namespace App\Services\Scanners;

class PortScanner
{
    use HasSafeCall;
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
        25    => ['label' => 'SMTP',          'dangerous' => false,
                  'desc_open'   => 'SMTP (port 25) is publicly reachable. This is expected on mail servers. If this server does not handle email, this port should be closed.',
                  'rec'         => 'If this server does not run a mail server, block port 25 in your firewall. If it does, ensure your MTA is not configured as an open relay and is up to date.'],
        2375  => ['label' => 'Docker API',    'dangerous' => true,
                  'desc_open'   => 'Docker API (port 2375) is publicly accessible without TLS. Anyone on the internet can control all containers on this server — create, delete, and execute commands with full root access.',
                  'rec'         => 'Close port 2375 immediately. Never expose the Docker daemon on a TCP port without mutual TLS. Use the Unix socket (/var/run/docker.sock) for local access instead.'],
        8080  => ['label' => 'HTTP (alt)',    'dangerous' => false,
                  'desc_open'   => 'HTTP alternate port 8080 is open. This commonly indicates a development server, admin panel, or proxy running on a non-standard port.',
                  'rec'         => 'Verify what service is running on port 8080. If it is a development or admin interface, restrict it to trusted IP addresses via firewall rules.'],
        8443  => ['label' => 'HTTPS (alt)',    'dangerous' => false,
                  'desc_open'   => 'HTTPS alternate port 8443 is open. This may be a development environment, staging server, or admin panel with a self-signed certificate.',
                  'rec'         => 'Verify what service runs on port 8443. Restrict access to trusted IPs if it is an admin or dev interface.'],
        6443  => ['label' => 'Kubernetes API', 'dangerous' => true,
                  'desc_open'   => 'Kubernetes API server (port 6443) is publicly reachable. Full control over your container cluster could be accessible to attackers.',
                  'rec'         => 'Restrict port 6443 to trusted management IPs only. Never expose the Kubernetes API server directly to the internet.'],
        2379  => ['label' => 'etcd',           'dangerous' => true,
                  'desc_open'   => 'etcd (port 2379) is publicly reachable. etcd stores Kubernetes cluster state including secrets and credentials — exposure is critical.',
                  'rec'         => 'Block port 2379 immediately. etcd should only be accessible between cluster control-plane nodes.'],
        9090  => ['label' => 'Prometheus',     'dangerous' => true,
                  'desc_open'   => 'Prometheus (port 9090) is publicly reachable. Metrics endpoints can expose sensitive internal system information and infrastructure details.',
                  'rec'         => 'Restrict port 9090 to internal monitoring networks. Add authentication or place Prometheus behind a reverse proxy with access control.'],
        3000  => ['label' => 'Grafana',        'dangerous' => false,
                  'desc_open'   => 'Grafana (port 3000) is publicly reachable. This may be a monitoring dashboard — ensure it requires authentication and is not using default credentials.',
                  'rec'         => 'If this is Grafana or another dashboard, ensure strong authentication is enforced and default admin/admin credentials are changed. Consider restricting to internal IPs.'],
        5984  => ['label' => 'CouchDB',        'dangerous' => true,
                  'desc_open'   => 'CouchDB (port 5984) is publicly reachable. Exposed CouchDB instances have been mass-wiped in ransomware attacks similar to MongoDB.',
                  'rec'         => 'Block port 5984 in your firewall. Enable CouchDB authentication and bind to 127.0.0.1.'],
        9000  => ['label' => 'MinIO / PHP-FPM', 'dangerous' => true,
                  'desc_open'   => 'Port 9000 is publicly reachable. This is commonly used by MinIO (S3-compatible storage) or PHP-FPM — neither should be internet-facing.',
                  'rec'         => 'Block port 9000. MinIO and PHP-FPM should only be accessible within your internal network.'],
        7474  => ['label' => 'Neo4j',          'dangerous' => true,
                  'desc_open'   => 'Neo4j HTTP interface (port 7474) is publicly reachable. Graph database admin interfaces should never be directly exposed to the internet.',
                  'rec'         => 'Block port 7474 in your firewall. Neo4j should be accessible only from application servers via the Bolt protocol on trusted internal networks.'],
    ];

    public function scan(string $host): array
    {
        $ip = $this->safe(fn() => $this->resolveIp($host), null);

        $checks     = [];
        $openDanger = 0;

        // Check all ports in parallel
        $openPorts = $ip ? $this->safe(fn() => $this->checkPortsParallel($ip, array_keys(self::PORTS)), []) : [];

        foreach (self::PORTS as $port => $info) {
            $open = $openPorts[$port] ?? false;

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

    /**
     * Check multiple ports in parallel using non-blocking sockets.
     * Replaces sequential checking (21 × 1s = 21s max) with a single ~1s pass.
     */
    private function checkPortsParallel(string $ip, array $ports): array
    {
        $sockets = [];
        $results = [];

        // Open all sockets non-blocking simultaneously
        foreach ($ports as $port) {
            $sock = @stream_socket_client(
                "tcp://{$ip}:{$port}",
                $errno,
                $errstr,
                0,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );
            if ($sock) {
                stream_set_blocking($sock, false);
                $sockets[$port] = $sock;
            } else {
                $results[$port] = false;
            }
        }

        if (empty($sockets)) {
            return $results;
        }

        // Wait up to TIMEOUT seconds for any connections to complete
        $deadline = microtime(true) + self::TIMEOUT;
        while (!empty($sockets) && microtime(true) < $deadline) {
            $read   = null;
            $write  = array_values($sockets);
            $except = null;
            $ready  = @stream_select($read, $write, $except, 0, 200000); // 200ms poll

            if ($ready === false) break;

            foreach ($write as $sock) {
                $port = array_search($sock, $sockets, true);
                if ($port === false) continue;

                // A writable socket means the connection succeeded
                $results[$port] = true;
                fclose($sock);
                unset($sockets[$port]);
            }
        }

        // Remaining sockets timed out — port closed
        foreach ($sockets as $port => $sock) {
            $results[$port] = false;
            fclose($sock);
        }

        return $results;
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

}
