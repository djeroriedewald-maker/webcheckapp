<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessScan;
use App\Models\MonitoredSite;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $sites = Auth::user()
            ->monitoredSites()
            ->orderBy('domain')
            ->with('lastScan')
            ->get();

        $stats = [
            'total'     => $sites->count(),
            'avg_score' => $sites->whereNotNull('last_score')->avg('last_score'),
            'critical'  => $sites->where('last_score', '<', 60)->count(),
        ];

        return view('dashboard.index', compact('sites', 'stats'));
    }

    public function addSite(Request $request)
    {
        $request->validate([
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = $this->normalizeDomain($request->input('domain'));

        if (! $domain) {
            return back()->withErrors(['domain' => 'Please enter a valid domain name.']);
        }

        $user = Auth::user();

        // Limit to 10 monitored sites per user
        if ($user->monitoredSites()->count() >= 10) {
            return back()->withErrors(['domain' => 'You can monitor up to 10 sites.']);
        }

        $site = $user->monitoredSites()->firstOrCreate(['domain' => $domain]);

        // Trigger an immediate scan
        $this->runScanForSite($site);

        return redirect()->route('dashboard')->with('success', "Added {$domain} to monitoring.");
    }

    public function removeSite(MonitoredSite $site)
    {
        abort_unless($site->user_id === Auth::id(), 403);

        $site->delete();

        return redirect()->route('dashboard')->with('success', 'Site removed from monitoring.');
    }

    public function refreshSite(MonitoredSite $site)
    {
        abort_unless($site->user_id === Auth::id(), 403);

        // Check for a recent cached scan first
        $existing = Scan::where('host', $site->domain)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subHour())
            ->latest('completed_at')
            ->first();

        if ($existing) {
            return redirect()->route('scan.show', $existing);
        }

        $scan = Scan::create([
            'url'        => 'https://' . $site->domain,
            'host'       => $site->domain,
            'status'     => 'pending',
            'ip_address' => request()->ip(),
        ]);

        ProcessScan::dispatch($scan);

        return redirect()->route('scan.show', $scan);
    }

    public function updateNotifications(Request $request, MonitoredSite $site)
    {
        abort_unless($site->user_id === Auth::id(), 403);

        $site->update([
            'notify_score_drop'  => $request->boolean('notify_score_drop'),
            'notify_cert_expiry' => $request->boolean('notify_cert_expiry'),
        ]);

        return back()->with('success', 'Notification preferences updated.');
    }

    public function history(string $domain)
    {
        // Ensure the user actually monitors this domain
        $site = Auth::user()->monitoredSites()->where('domain', $domain)->firstOrFail();

        $scans = Scan::where('host', $domain)
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->orderBy('completed_at', 'desc')
            ->limit(30)
            ->get();

        return view('dashboard.history', compact('site', 'scans'));
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'domains' => ['required', 'string', 'max:5000'],
        ]);

        $user  = Auth::user();
        $lines = preg_split('/[\r\n,]+/', $request->input('domains'));
        $added = 0;
        $skippedLimit = 0;
        $skippedInvalid = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if ($user->monitoredSites()->count() >= 10) {
                $skippedLimit++;
                continue;
            }

            $domain = $this->normalizeDomain($line);

            if (! $domain) {
                $skippedInvalid++;
                continue;
            }

            $existed = $user->monitoredSites()->where('domain', $domain)->exists();
            if (! $existed) {
                $user->monitoredSites()->create(['domain' => $domain]);
                $added++;
            }
        }

        $parts = [];
        if ($added > 0) {
            $parts[] = "{$added} " . ($added === 1 ? 'site' : 'sites') . ' toegevoegd';
        }
        if ($skippedInvalid > 0) {
            $parts[] = "{$skippedInvalid} ongeldig";
        }
        if ($skippedLimit > 0) {
            $parts[] = "{$skippedLimit} overgeslagen (limiet van 10 bereikt)";
        }

        $message = $parts ? implode(', ', $parts) . '.' : 'Geen nieuwe sites toegevoegd.';

        return redirect()->route('dashboard')->with('success', $message);
    }

    private function runScanForSite(MonitoredSite $site): void
    {
        // Check for a recent cached scan first
        $existing = Scan::where('host', $site->domain)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subHour())
            ->latest('completed_at')
            ->first();

        if ($existing) {
            $site->update([
                'last_score'      => $existing->score,
                'last_grade'      => $existing->grade,
                'last_scan_id'    => $existing->id,
                'last_checked_at' => now(),
            ]);
            return;
        }

        $scan = Scan::create([
            'url'        => 'https://' . $site->domain,
            'host'       => $site->domain,
            'status'     => 'running',
            'ip_address' => request()->ip(),
        ]);

        try {
            set_time_limit(120);
            $results = app(ScanService::class)->run($site->domain);

            $scan->update([
                'status'       => 'completed',
                'score'        => $results['score'],
                'grade'        => $results['grade'],
                'results'      => $results['categories'],
                'completed_at' => now(),
            ]);

            $site->update([
                'previous_score'  => $site->last_score,
                'last_score'      => $results['score'],
                'last_grade'      => $results['grade'],
                'last_scan_id'    => $scan->id,
                'last_checked_at' => now(),
            ]);
        } catch (\Throwable) {
            $scan->update(['status' => 'failed']);
        }
    }

    private function normalizeDomain(string $input): ?string
    {
        $input = trim($input);

        if (! str_starts_with($input, 'http://') && ! str_starts_with($input, 'https://')) {
            $input = 'https://' . $input;
        }

        $host = parse_url($input, PHP_URL_HOST);

        if (! $host || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        $host = strtolower($host);

        // Must look like a real domain: label.tld (each label 1-63 chars, valid chars)
        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $host)) {
            return null;
        }

        // Block internal TLDs
        if (preg_match('/\.(local|internal|test|lan|intranet|corp|home|arpa)$/', $host)) {
            return null;
        }

        return $host;
    }
}
