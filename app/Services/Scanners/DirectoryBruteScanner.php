<?php

namespace App\Services\Scanners;

class DirectoryBruteScanner
{
    use HasSafeCall;

    private const TIMEOUT = 5;

    private const PATHS = [
        '/admin'          => 'Admin panel',
        '/administrator'  => 'Admin panel',
        '/wp-admin'       => 'WordPress admin',
        '/phpmyadmin'     => 'phpMyAdmin',
        '/pma'            => 'phpMyAdmin (alias)',
        '/adminer'        => 'Adminer database tool',
        '/cpanel'         => 'cPanel',
        '/webmail'        => 'Webmail interface',
        '/backup'         => 'Backup directory',
        '/backups'        => 'Backup directory',
        '/test'           => 'Test directory',
        '/staging'        => 'Staging environment',
        '/dev'            => 'Development directory',
        '/debug'          => 'Debug page',
        '/tmp'            => 'Temporary directory',
        '/temp'           => 'Temporary directory',
        '/log'            => 'Log files',
        '/logs'           => 'Log files',
        '/old'            => 'Old/archived files',
        '/archive'        => 'Archive directory',
        '/dump'           => 'Data dump',
        '/sql'            => 'SQL files',
        '/db'             => 'Database files',
        '/config'         => 'Configuration directory',
        '/install'        => 'Installation wizard',
        '/setup'          => 'Setup wizard',
        '/.svn'           => 'SVN repository',
        '/.hg'            => 'Mercurial repository',
        '/.DS_Store'      => 'macOS metadata file',
        '/xmlrpc.php'     => 'XML-RPC endpoint',
    ];

    public function scan(string $host): array
    {
        $checks   = [];
        $found    = [];
        $score    = 0;
        $maxScore = count(self::PATHS) * 5;

        // Use curl_multi for parallel probing
        $multiHandle = curl_multi_init();
        $handles = [];

        foreach (self::PATHS as $path => $label) {
            $ch = curl_init("https://{$host}{$path}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_NOBODY         => true,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
            ]);
            $handles[$path] = $ch;
            curl_multi_add_handle($multiHandle, $ch);
        }

        // Execute all requests in parallel
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        foreach ($handles as $path => $ch) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $label = self::PATHS[$path];
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);

            // 200 or 301/302 (protected but exists) is interesting
            if (in_array($code, [200, 301, 302, 403])) {
                $statusText = match($code) {
                    200     => 'accessible',
                    403     => 'forbidden but exists',
                    default => 'redirects',
                };
                $found[] = [
                    'path'   => $path,
                    'label'  => $label,
                    'code'   => $code,
                    'status' => $statusText,
                ];
            } else {
                $score += 5;
            }
        }

        curl_multi_close($multiHandle);

        if (empty($found)) {
            $checks[] = [
                'id'          => 'dir_brute_none',
                'label'       => 'No common sensitive paths found',
                'status'      => 'pass',
                'description' => 'None of the ' . count(self::PATHS) . ' common sensitive directories or files were detected on this server.',
            ];
        } else {
            foreach ($found as $item) {
                $isCritical = in_array($item['code'], [200]);
                $checks[] = [
                    'id'             => 'dir_brute_' . md5($item['path']),
                    'label'          => "{$item['label']} found at {$item['path']}",
                    'status'         => $isCritical ? 'fail' : 'warn',
                    'description'    => "Path {$item['path']} returned HTTP {$item['code']} ({$item['status']}). This could expose {$item['label']} to attackers.",
                    'recommendation' => $isCritical
                        ? "Restrict access to {$item['path']} or remove it from the public web root."
                        : "Verify that {$item['path']} requires proper authentication.",
                ];
            }
        }

        return [
            'category' => 'Directory Discovery',
            'icon'     => 'magnifying-glass',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 100,
            'checks'   => $checks,
        ];
    }
}
