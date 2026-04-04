<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessScan;
use App\Models\Payment;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $now   = now();
        $today = $now->copy()->startOfDay();
        $week  = $now->copy()->subDays(7);
        $month = $now->copy()->subDays(30);

        // ── Scan counts ──
        $totalScans   = Scan::count();
        $scansToday   = Scan::where('created_at', '>=', $today)->count();
        $scansWeek    = Scan::where('created_at', '>=', $week)->count();
        $scansMonth   = Scan::where('created_at', '>=', $month)->count();
        $completedAll = Scan::where('status', 'completed')->count();
        $failedAll    = Scan::where('status', 'failed')->count();
        $pendingNow   = Scan::whereIn('status', ['pending', 'running'])->count();

        // ── Unique visitors (distinct IPs) ──
        $visitorsToday = Scan::where('created_at', '>=', $today)->distinct('ip_address')->count('ip_address');
        $visitorsWeek  = Scan::where('created_at', '>=', $week)->distinct('ip_address')->count('ip_address');
        $visitorsMonth = Scan::where('created_at', '>=', $month)->distinct('ip_address')->count('ip_address');

        // ── Users ──
        $totalUsers    = User::count();
        $newUsersWeek  = User::where('created_at', '>=', $week)->count();

        // ── Average score ──
        $avgScore = (int) round(Scan::where('status', 'completed')->whereNotNull('score')->avg('score') ?? 0);

        // ── Grade distribution ──
        $gradeDistribution = Scan::where('status', 'completed')
            ->whereNotNull('grade')
            ->select('grade', DB::raw('count(*) as total'))
            ->groupBy('grade')
            ->orderByDesc('total')
            ->pluck('total', 'grade')
            ->toArray();

        // ── Scans per day (last 30 days) ──
        $scansPerDay = Scan::where('created_at', '>=', $month)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Fill in missing days with 0
        $chart = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->format('Y-m-d');
            $chart[$date] = $scansPerDay[$date] ?? 0;
        }

        // ── Top scanned domains ──
        $topDomains = Scan::select('host', DB::raw('count(*) as total'), DB::raw('max(score) as best_score'))
            ->where('created_at', '>=', $month)
            ->groupBy('host')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        // ── Recent scans ──
        $recentScans = Scan::latest()->limit(20)->get();

        // ── Revenue & Payments ──
        $totalRevenue    = Payment::where('status', 'completed')->sum('amount_cents');
        $revenueMonth    = Payment::where('status', 'completed')->where('paid_at', '>=', $month)->sum('amount_cents');
        $revenueWeek     = Payment::where('status', 'completed')->where('paid_at', '>=', $week)->sum('amount_cents');
        $totalPayments   = Payment::where('status', 'completed')->count();
        $paymentsMonth   = Payment::where('status', 'completed')->where('paid_at', '>=', $month)->count();
        $recentPayments  = Payment::with('user', 'scan')->latest()->limit(10)->get();

        // ── Tier breakdown ──
        $tierBreakdown = Scan::where('status', 'completed')
            ->select('tier', DB::raw('count(*) as total'))
            ->groupBy('tier')
            ->pluck('total', 'tier')
            ->toArray();

        // ── Pro/Deep scans ──
        $proScansMonth  = Scan::where('tier', 'pro')->where('created_at', '>=', $month)->count();
        $deepScansMonth = Scan::where('tier', 'deep')->where('created_at', '>=', $month)->count();

        // ── Top 3 best & worst scoring sites (by period) ──
        $periods = [
            'today' => $today,
            'week'  => $week,
            'month' => $month,
            'year'  => $now->copy()->subYear(),
        ];

        $topBest  = [];
        $topWorst = [];
        foreach ($periods as $label => $since) {
            $topBest[$label] = Scan::where('status', 'completed')
                ->whereNotNull('score')
                ->where('completed_at', '>=', $since)
                ->orderByDesc('score')
                ->limit(3)
                ->get();

            $topWorst[$label] = Scan::where('status', 'completed')
                ->whereNotNull('score')
                ->where('score', '>', 0)
                ->where('completed_at', '>=', $since)
                ->orderBy('score')
                ->limit(3)
                ->get();
        }

        // ── Users list (for grant tier feature) ──
        $users = User::withCount(['scans', 'payments'])->orderByDesc('created_at')->limit(50)->get();

        return view('admin.index', compact(
            'totalScans', 'scansToday', 'scansWeek', 'scansMonth',
            'completedAll', 'failedAll', 'pendingNow',
            'visitorsToday', 'visitorsWeek', 'visitorsMonth',
            'totalUsers', 'newUsersWeek',
            'avgScore', 'gradeDistribution', 'chart',
            'topDomains', 'recentScans',
            'totalRevenue', 'revenueMonth', 'revenueWeek',
            'totalPayments', 'paymentsMonth', 'recentPayments',
            'tierBreakdown', 'proScansMonth', 'deepScansMonth',
            'topBest', 'topWorst',
            'users',
        ));
    }

    /**
     * Update a user's granted tier (permanent free access).
     */
    public function updateTier(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'tier'    => ['required', 'in:none,pro,deep'],
        ]);

        $user = User::findOrFail($request->input('user_id'));
        $tier = $request->input('tier');

        $user->update([
            'granted_tier' => $tier === 'none' ? null : $tier,
        ]);

        $label = $tier === 'none' ? 'revoked (back to free)' : $tier;

        return back()->with('success', "Tier for {$user->email} set to: {$label}");
    }

    /**
     * Show user detail page.
     */
    public function showUser(User $user)
    {
        $scans = Scan::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $payments = Payment::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.user', compact('user', 'scans', 'payments'));
    }

    /**
     * Delete a user and all their data.
     */
    public function deleteUser(User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'Cannot delete an admin user.');
        }

        $email = $user->email;
        $user->monitoredSites()->delete();
        $user->payments()->delete();
        Scan::where('user_id', $user->id)->update(['user_id' => null]);
        $user->delete();

        return redirect()->route('admin')->with('success', "User {$email} has been deleted.");
    }

    /**
     * Toggle admin status.
     */
    public function toggleAdmin(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot remove your own admin status.');
        }

        $user->update(['is_admin' => ! $user->is_admin]);
        $status = $user->is_admin ? 'promoted to admin' : 'removed as admin';

        return back()->with('success', "{$user->email} has been {$status}.");
    }

    /**
     * Delete a scan.
     */
    public function deleteScan(Scan $scan)
    {
        $host = $scan->host;
        $scan->delete();

        return back()->with('success', "Scan for {$host} has been deleted.");
    }

    /**
     * Bulk delete scans.
     */
    public function bulkDeleteScans(Request $request)
    {
        $ids = $request->input('scan_ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No scans selected.');
        }

        $count = Scan::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} scan(s) deleted.");
    }

    /**
     * Search users and scans.
     */
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return redirect()->route('admin')->with('error', 'Search query must be at least 2 characters.');
        }

        $users = User::where('email', 'like', "%{$q}%")
            ->orWhere('name', 'like', "%{$q}%")
            ->withCount(['scans', 'payments'])
            ->limit(20)
            ->get();

        $scans = Scan::where('host', 'like', "%{$q}%")
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.search', compact('q', 'users', 'scans'));
    }

    /**
     * System status overview.
     */
    public function system()
    {
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        // Database size — works for both MySQL and SQLite
        $dbSize = 0;
        try {
            $driver = config('database.default');
            if ($driver === 'sqlite') {
                $result = DB::select("SELECT page_count * page_size as size FROM pragma_page_count(), pragma_page_size()");
                $dbSize = $result[0]->size ?? 0;
            } else {
                $dbName = config('database.connections.' . $driver . '.database');
                $result = DB::select("SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
                $dbSize = $result[0]->size ?? 0;
            }
        } catch (\Throwable) {
            // Ignore — show 0
        }

        $scanCount = Scan::count();
        $userCount = User::count();
        $paymentCount = Payment::count();

        return view('admin.system', compact('pendingJobs', 'failedJobs', 'dbSize', 'scanCount', 'userCount', 'paymentCount'));
    }
}
