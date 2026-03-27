<?php

namespace App\Services\Scanners;

class ExposedFilesScanner
{
    private const TIMEOUT = 6;

    // Files to probe with content fingerprints to avoid custom-404 false positives
    private const TARGETS = [
        [
            'id'          => 'exposed_env',
            'label'       => '.env file exposed',
            'path'        => '/.env',
            'fingerprint' => ['APP_', 'DB_', 'SECRET', 'KEY=', 'PASSWORD'],
            'points'      => 30,
            'desc_pass'   => '.env file is not publicly accessible — environment secrets are protected.',
            'desc_fail'   => '.env file is publicly accessible. This file typically contains database credentials, API keys, and application secrets in plaintext.',
            'rec_fail'    => 'Restrict access immediately. Nginx: add "location ~ /\\.env { deny all; }" Apache: add "Deny from all" in .htaccess for the .env file.',
        ],
        [
            'id'          => 'exposed_git',
            'label'       => '.git directory exposed',
            'path'        => '/.git/HEAD',
            'fingerprint' => ['ref:', 'HEAD'],
            'points'      => 25,
            'desc_pass'   => '.git directory is not publicly accessible — source code history is protected.',
            'desc_fail'   => '.git/HEAD is publicly accessible. Attackers can download your entire source code history, including any secrets ever committed.',
            'rec_fail'    => 'Block .git access in your web server. Nginx: "location ~ /\\.git { deny all; }" Apache: "RedirectMatch 404 /\\.git"',
        ],
        [
            'id'          => 'exposed_phpinfo',
            'label'       => 'phpinfo() page exposed',
            'path'        => '/phpinfo.php',
            'fingerprint' => ['phpinfo', 'PHP Version', 'PHP Extension'],
            'points'      => 20,
            'desc_pass'   => 'No exposed phpinfo.php page detected.',
            'desc_fail'   => 'phpinfo.php is publicly accessible — reveals PHP version, server configuration, installed extensions, and internal paths to attackers.',
            'rec_fail'    => 'Delete phpinfo.php from your web root immediately. Never leave diagnostic files on a production server.',
        ],
        [
            'id'          => 'exposed_backup',
            'label'       => 'Database backup file exposed',
            'path'        => '/backup.sql',
            'fingerprint' => ['CREATE TABLE', 'INSERT INTO', 'DROP TABLE', '-- MySQL', '-- MariaDB'],
            'points'      => 15,
            'desc_pass'   => 'No exposed database backup files detected.',
            'desc_fail'   => 'A database backup (backup.sql) is publicly accessible — contains your entire database including user data and credentials.',
            'rec_fail'    => 'Remove all SQL files from the web root immediately. Store backups outside the public directory or in password-protected storage.',
        ],
        [
            'id'          => 'exposed_wpconfig',
            'label'       => 'WordPress config backup exposed',
            'path'        => '/wp-config.php.bak',
            'fingerprint' => ['DB_NAME', 'DB_PASSWORD', 'DB_USER', 'table_prefix', 'AUTH_KEY'],
            'points'      => 10,
            'desc_pass'   => 'No exposed WordPress configuration backup detected.',
            'desc_fail'   => 'wp-config.php.bak is publicly accessible — contains WordPress database credentials and secret keys.',
            'rec_fail'    => 'Delete wp-config.php.bak from the server. WordPress config backups must never be stored in the web root.',
        ],
        [
            'id'          => 'exposed_htpasswd',
            'label'       => '.htpasswd file exposed',
            'path'        => '/.htpasswd',
            'fingerprint' => ['$apr1$', '$2y$', '$1$', '{SHA}'],
            'points'      => 20,
            'desc_pass'   => '.htpasswd is not publicly accessible.',
            'desc_fail'   => '.htpasswd is publicly accessible. This file contains hashed HTTP Basic Authentication passwords that can be cracked offline.',
            'rec_fail'    => 'Store .htpasswd outside the web root, or block access: Nginx: "location ~ /\\.htpasswd { deny all; }" — Apache normally protects it automatically, but verify your configuration.',
        ],
        [
            'id'          => 'exposed_webconfig',
            'label'       => 'web.config not exposed',
            'path'        => '/web.config',
            'fingerprint' => ['<configuration', 'connectionStrings', 'appSettings', 'system.web'],
            'points'      => 15,
            'desc_pass'   => 'No exposed web.config detected.',
            'desc_fail'   => 'web.config is publicly accessible. This IIS configuration file may contain database connection strings and application secrets.',
            'rec_fail'    => 'Add a URL rewrite rule in IIS to deny requests to web.config. Verify IIS is not serving configuration files directly.',
        ],
        [
            'id'          => 'exposed_gitconfig',
            'label'       => '.git/config not exposed',
            'path'        => '/.git/config',
            'fingerprint' => ['[core]', 'repositoryformatversion', '[remote "'],
            'points'      => 15,
            'desc_pass'   => '.git configuration is not publicly accessible.',
            'desc_fail'   => '.git/config is publicly accessible. This reveals your Git remote URL and may expose embedded credentials or your private repository location.',
            'rec_fail'    => 'Block all .git access in your server config: Nginx: "location ~ /\\.git { deny all; }" Apache: "RedirectMatch 404 /\\.git"',
        ],
        [
            'id'          => 'exposed_composerlock',
            'label'       => 'composer.lock not exposed',
            'path'        => '/composer.lock',
            'fingerprint' => ['"packages":', '"version":', '"require":'],
            'points'      => 10,
            'desc_pass'   => 'composer.lock is not publicly accessible.',
            'desc_fail'   => 'composer.lock is publicly accessible. This reveals the exact versions of all PHP dependencies, making it trivial to look up known CVEs in your specific stack.',
            'rec_fail'    => 'Move composer.json and composer.lock above the public web root (e.g. outside public/). Block them via the web server if moving is not possible.',
        ],
        [
            'id'          => 'exposed_serverstatus',
            'label'       => 'Apache server-status not exposed',
            'path'        => '/server-status',
            'fingerprint' => ['Apache Server Status', 'Server Version:', 'requests currently being processed'],
            'points'      => 10,
            'desc_pass'   => '/server-status is not publicly accessible.',
            'desc_fail'   => 'Apache /server-status is publicly accessible. This diagnostic page exposes live request data, client IP addresses, and internal server details.',
            'rec_fail'    => 'Restrict server-status to localhost: in Apache config add "Require local" inside <Location /server-status>. Or disable mod_status entirely.',
        ],
    ];

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        foreach (self::TARGETS as $target) {
            $maxScore += $target['points'];

            $found = $this->safe(fn() => $this->probeFile($host, $target['path'], $target['fingerprint']), false);

            if ($found) {
                $checks[] = [
                    'id'             => $target['id'],
                    'label'          => $target['label'],
                    'status'         => 'fail',
                    'description'    => $target['desc_fail'],
                    'recommendation' => $target['rec_fail'],
                ];
            } else {
                $score += $target['points'];
                $checks[] = [
                    'id'          => $target['id'],
                    'label'       => $target['label'],
                    'status'      => 'pass',
                    'description' => $target['desc_pass'],
                ];
            }
        }

        return [
            'category' => 'Exposed Files',
            'icon'     => 'folder-open',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 100,
            'checks'   => $checks,
        ];
    }

    /**
     * Probe a URL and validate the response body against content fingerprints.
     * Returns true only if the HTTP 200 response contains at least one fingerprint string.
     * This prevents false positives from "soft 404" pages that return HTTP 200.
     */
    private function probeFile(string $host, string $path, array $fingerprints): bool
    {
        $ch = curl_init("https://{$host}{$path}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => false, // redirect = not directly exposed
            CURLOPT_RANGE          => '0-4095', // only need first 4 KB for fingerprinting
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || ! $body) {
            return false;
        }

        foreach ($fingerprints as $fingerprint) {
            if (stripos($body, $fingerprint) !== false) {
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
