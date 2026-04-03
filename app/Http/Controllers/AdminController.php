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
            $baseQuery = Scan::where('status', 'completed')
                ->whereNotNull('score')
                ->where('completed_at', '>=', $since);

            $topBest[$label] = (clone $baseQuery)
                ->orderByDesc('score')
                ->select('host', 'score', 'grade', 'completed_at', 'uid')
                ->groupBy('host')
                ->limit(3)
                ->get();

            $topWorst[$label] = (clone $baseQuery)
                ->orderBy('score')
                ->select('host', 'score', 'grade', 'completed_at', 'uid')
                ->groupBy('host')
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
}
