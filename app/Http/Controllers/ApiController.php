<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function scan(Request $request)
    {
        $url = trim($request->input('url', ''));

        if (empty($url)) {
            return response()->json(['error' => 'The url parameter is required.'], 422);
        }

        // Normalise URL
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }
        $url  = rtrim($url, '/');
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return response()->json(['error' => 'Invalid URL.'], 422);
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return response()->json(['error' => 'IP addresses are not allowed — enter a domain name.'], 422);
        }

        // Return cached result if scanned within the last hour
        $cached = Scan::where('host', $host)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subHour())
            ->latest('completed_at')
            ->first();

        if ($cached) {
            return $this->formatResponse($cached, cached: true);
        }

        // Run a fresh scan
        $scan = Scan::create([
            'url'        => $url,
            'host'       => $host,
            'status'     => 'running',
            'ip_address' => $request->ip(),
        ]);

        try {
            set_time_limit(120);
            $results = app(ScanService::class)->run($host);

            $scan->update([
                'status'       => 'completed',
                'score'        => $results['score'],
                'grade'        => $results['grade'],
                'results'      => $results['categories'],
                'completed_at' => now(),
            ]);

            return $this->formatResponse($scan->fresh(), cached: false);
        } catch (\Throwable $e) {
            $scan->update(['status' => 'failed']);
            return response()->json(['error' => 'Scan failed. The host may be unreachable.'], 500);
        }
    }

    private function formatResponse(Scan $scan, bool $cached): \Illuminate\Http\JsonResponse
    {
        $categories = [];
        foreach ($scan->results ?? [] as $key => $cat) {
            $categories[$key] = [
                'category' => $cat['category'],
                'score'    => $cat['score'],
                'checks'   => collect($cat['checks'])->map(fn($c) => [
                    'id'             => $c['id'],
                    'label'          => $c['label'],
                    'status'         => $c['status'],
                    'description'    => $c['description'],
                    'recommendation' => $c['recommendation'] ?? null,
                ])->values()->all(),
            ];
        }

        return response()->json([
            'url'          => $scan->url,
            'host'         => $scan->host,
            'score'        => $scan->score,
            'grade'        => $scan->grade,
            'scanned_at'   => $scan->completed_at?->toIso8601String(),
            'cached'       => $cached,
            'report_url'   => route('scan.show', $scan),
            'categories'   => $categories,
        ]);
    }

    public function docs()
    {
        return view('api.docs');
    }
}
