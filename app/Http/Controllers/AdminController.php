<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Models\User;
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

        return view('admin.index', compact(
            'totalScans', 'scansToday', 'scansWeek', 'scansMonth',
            'completedAll', 'failedAll', 'pendingNow',
            'visitorsToday', 'visitorsWeek', 'visitorsMonth',
            'totalUsers', 'newUsersWeek',
            'avgScore', 'gradeDistribution', 'chart',
            'topDomains', 'recentScans',
        ));
    }
}
