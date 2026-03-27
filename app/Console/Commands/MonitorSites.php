<?php

namespace App\Console\Commands;

use App\Mail\CertExpiryAlert;
use App\Mail\ScoreDropAlert;
use App\Models\MonitoredSite;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MonitorSites extends Command
{
    protected $signature   = 'sites:monitor';
    protected $description = 'Scan all monitored sites and send alerts when needed';

    public function handle(): void
    {
        $sites = MonitoredSite::with('user')->get();

        foreach ($sites as $site) {
            $this->info("Scanning {$site->domain}...");

            $previousScore = $site->last_score;

            // Run a fresh scan (bypass 1-hour cache for weekly monitoring)
            $scan = Scan::create([
                'url'        => 'https://' . $site->domain,
                'host'       => $site->domain,
                'status'     => 'running',
                'ip_address' => '127.0.0.1',
            ]);

            try {
                $results = app(ScanService::class)->run($site->domain);

                $scan->update([
                    'status'       => 'completed',
                    'score'        => $results['score'],
                    'grade'        => $results['grade'],
                    'results'      => $results['categories'],
                    'completed_at' => now(),
                ]);

                $scan->refresh();

                $site->update([
                    'last_score'      => $results['score'],
                    'last_grade'      => $results['grade'],
                    'last_scan_id'    => $scan->id,
                    'last_checked_at' => now(),
                ]);

                // Score drop alert
                if ($site->notify_score_drop
                    && $previousScore !== null
                    && $results['score'] < $previousScore - 5
                ) {
                    Mail::to($site->user->email)
                        ->send(new ScoreDropAlert($site, $scan, $previousScore, $results['score']));

                    $this->line("  → Score drop alert sent ({$previousScore} → {$results['score']})");
                }

                // Certificate expiry alert
                if ($site->notify_cert_expiry) {
                    $daysLeft = $results['categories']['ssl']['checks'][0]['days_left']
                        ?? $this->extractCertDaysLeft($results['categories']['ssl'] ?? []);

                    if ($daysLeft !== null && $daysLeft <= 30 && $daysLeft > 0) {
                        Mail::to($site->user->email)
                            ->send(new CertExpiryAlert($site, $scan, $daysLeft));

                        $this->line("  → Cert expiry alert sent ({$daysLeft} days left)");
                    }
                }

            } catch (\Throwable $e) {
                $scan->update(['status' => 'failed']);
                $this->error("  → Scan failed: {$e->getMessage()}");
            }
        }

        $this->info('Done.');
    }

    private function extractCertDaysLeft(array $sslCategory): ?int
    {
        foreach ($sslCategory['checks'] ?? [] as $check) {
            if (isset($check['days_left']) && is_int($check['days_left'])) {
                return $check['days_left'];
            }
            // Also check description for days_left stored in metadata
            if (($check['id'] ?? '') === 'ssl_cert_valid' && isset($check['days_left'])) {
                return (int) $check['days_left'];
            }
        }
        return null;
    }
}
