<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessScan;
use App\Models\Scan;
use App\Services\ScanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index()
    {
        $scanCount = Scan::where('status', 'completed')->count() + 2_406_521;

        return response()
            ->view('welcome', compact('scanCount'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function store(Request $request)
    {
        $request->validate([
            'url' => ['required', 'string', 'max:255'],
        ]);

        $url = $this->normalizeUrl($request->input('url'));
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return back()->withErrors(['url' => 'Please enter a valid website URL.'])->withInput();
        }

        // Block IP addresses
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return back()->withErrors(['url' => 'Please enter a domain name, not an IP address.'])->withInput();
        }

        // Must look like a real domain: label.tld (e.g. example.com)
        $host = strtolower($host);
        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $host)) {
            return back()->withErrors(['url' => 'Please enter a valid domain name (e.g. example.com).'])->withInput();
        }

        // Block internal/reserved TLDs
        if (preg_match('/\.(local|internal|test|lan|intranet|corp|home|arpa|localhost)$/', $host)) {
            return back()->withErrors(['url' => 'Internal or reserved domains cannot be scanned.'])->withInput();
        }

        // Quick DNS check — does this domain actually exist?
        $resolved = @gethostbyname($host);
        if ($resolved === $host) {
            return back()->withErrors(['url' => "The domain \"{$host}\" does not appear to exist. Please check for typos."])->withInput();
        }

        // Use the user's granted tier if they have one, otherwise free
        $tier = 'free';
        if ($request->user() && $request->user()->granted_tier) {
            $tier = $request->user()->granted_tier;
        }

        $scan = Scan::create([
            'url'        => $url,
            'host'       => $host,
            'status'     => 'pending',
            'tier'       => $tier,
            'user_id'    => $request->user()?->id,
            'ip_address' => $request->ip(),
        ]);

        ProcessScan::dispatch($scan);

        return redirect()->route('scan.show', $scan);
    }

    public function show(Scan $scan)
    {
        $percentile = null;
        if ($scan->isCompleted() && $scan->score !== null) {
            $total = Scan::where('status', 'completed')->whereNotNull('score')->count();
            if ($total > 1) {
                $better     = Scan::where('status', 'completed')->whereNotNull('score')->where('score', '<', $scan->score)->count();
                $percentile = (int) round($better / $total * 100);
            }
        }

        // Is there a newer completed scan for this host? (someone else triggered a fresh scan)
        $newerScan = null;
        if ($scan->completed_at !== null) {
            $newerScan = Scan::where('host', $scan->host)
                ->where('status', 'completed')
                ->where('id', '!=', $scan->id)
                ->where('completed_at', '>', $scan->completed_at)
                ->latest('completed_at')
                ->first();
        }

        // Previous scan for "wat veranderde" diff
        $prevScan = null;
        $diff     = null;
        if ($scan->isCompleted() && $scan->completed_at !== null) {
            $prevScan = Scan::where('host', $scan->host)
                ->where('status', 'completed')
                ->where('id', '!=', $scan->id)
                ->where('completed_at', '<', $scan->completed_at)
                ->latest('completed_at')
                ->first();

            if ($prevScan && $prevScan->results && $scan->results) {
                $diff = $this->buildDiff($scan, $prevScan);
            }
        }

        return view('scan.show', compact('scan', 'percentile', 'newerScan', 'prevScan', 'diff'));
    }

    public function card(Scan $scan)
    {
        abort_unless($scan->isCompleted(), 404);
        return view('scan.card', compact('scan'));
    }

    public function pdf(Scan $scan)
    {
        abort_unless($scan->isCompleted(), 404);
        abort_if($scan->isFree(), 403, 'PDF reports are available with Pro and Deep scans.');

        $pdf = Pdf::loadView('scan.pdf', compact('scan'))
            ->setPaper('a4', 'portrait');

        $filename = 'security-report-' . $scan->host . '-' . $scan->completed_at->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function compare(Request $request)
    {
        $urlA = trim($request->input('a', ''));
        $urlB = trim($request->input('b', ''));

        $scanA = $this->scanForCompare($urlA, $request->ip());
        $scanB = $this->scanForCompare($urlB, $request->ip());

        return view('scan.compare', compact('scanA', 'scanB', 'urlA', 'urlB'));
    }

    public function badge(Scan $scan)
    {
        abort_unless($scan->isCompleted(), 404);

        $score = $scan->score ?? 0;
        $grade = $scan->grade ?? 'F';
        $host  = $scan->host;

        // Pick a colour based on the grade
        $color = match(true) {
            $score >= 80 => '#22c55e', // green
            $score >= 60 => '#f59e0b', // amber
            default      => '#ef4444', // red
        };

        $labelWidth = 130;
        $valueWidth = 70;
        $totalWidth = $labelWidth + $valueWidth;
        $label      = htmlspecialchars('WebCheckApp', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $value      = htmlspecialchars("{$grade}  {$score}/100", ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$totalWidth}" height="20">
          <linearGradient id="s" x2="0" y2="100%">
            <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
            <stop offset="1" stop-opacity=".1"/>
          </linearGradient>
          <clipPath id="r">
            <rect width="{$totalWidth}" height="20" rx="3"/>
          </clipPath>
          <g clip-path="url(#r)">
            <rect width="{$labelWidth}" height="20" fill="#555"/>
            <rect x="{$labelWidth}" width="{$valueWidth}" height="20" fill="{$color}"/>
            <rect width="{$totalWidth}" height="20" fill="url(#s)"/>
          </g>
          <g fill="#fff" text-anchor="middle" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">
            <text x="65" y="15" fill="#010101" fill-opacity=".3">{$label}</text>
            <text x="65" y="14">{$label}</text>
            <text x="{$this->badgeValueX($labelWidth, $valueWidth)}" y="15" fill="#010101" fill-opacity=".3">{$value}</text>
            <text x="{$this->badgeValueX($labelWidth, $valueWidth)}" y="14">{$value}</text>
          </g>
        </svg>
        SVG;

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function badgeValueX(int $labelWidth, int $valueWidth): int
    {
        return $labelWidth + (int) round($valueWidth / 2);
    }

    public function status(Scan $scan)
    {
        $error = null;
        if ($scan->isFailed() && is_array($scan->results) && isset($scan->results['_error'])) {
            $error = $scan->results['_error'];
        }

        return response()->json([
            'status'             => $scan->status,
            'score'              => $scan->score,
            'grade'              => $scan->grade,
            'completed'          => $scan->isCompleted(),
            'failed'             => $scan->isFailed(),
            'completed_scanners' => is_array($scan->results) ? count(array_filter(array_keys($scan->results ?? []), fn($k) => $k !== '_error')) : 0,
            'error'              => $error,
        ]);
    }

    private function scanForCompare(string $rawUrl, string $ip): ?Scan
    {
        if (empty(trim($rawUrl))) {
            return null;
        }

        $norm = $this->normalizeUrl($rawUrl);
        $host = parse_url($norm, PHP_URL_HOST);

        if (! $host || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        $scan = Scan::create(['url' => $norm, 'host' => $host, 'status' => 'running', 'ip_address' => $ip]);

        try {
            $results = app(ScanService::class)->run($host);
            $scan->update([
                'status'       => 'completed',
                'score'        => $results['score'],
                'grade'        => $results['grade'],
                'results'      => $results['categories'],
                'completed_at' => now(),
            ]);

            return $scan->fresh();
        } catch (\Throwable $e) {
            \Log::error('Compare scan failed', ['host' => $host, 'error' => $e->getMessage()]);
            $scan->update(['status' => 'failed']);

            return null;
        }
    }

    private function buildDiff(Scan $current, Scan $prev): array
    {
        // Flatten checks from both scans keyed by a stable identifier (category + label)
        $flatten = function (array $categories): array {
            $map = [];
            foreach ($categories as $cat) {
                foreach ($cat['checks'] ?? [] as $check) {
                    $key       = ($cat['category'] ?? '') . '::' . ($check['label'] ?? '');
                    $map[$key] = $check['status'] ?? 'pass';
                }
            }
            return $map;
        };

        $current->results = is_array($current->results) ? $current->results : [];
        $prev->results    = is_array($prev->results)    ? $prev->results    : [];

        $nowMap  = $flatten($current->results);
        $prevMap = $flatten($prev->results);

        $fixed    = [];
        $broken   = [];

        foreach ($nowMap as $key => $nowStatus) {
            $prevStatus = $prevMap[$key] ?? null;
            if ($prevStatus === null) continue;

            $wasGood  = $prevStatus === 'pass';
            $isGood   = $nowStatus  === 'pass';

            // Extract readable label (after '::')
            $label = substr($key, strpos($key, '::') + 2);

            if (! $wasGood && $isGood) {
                $fixed[]  = $label;
            } elseif ($wasGood && ! $isGood) {
                $broken[] = $label;
            }
        }

        return [
            'score_before' => $prev->score,
            'score_after'  => $current->score,
            'score_delta'  => ($current->score ?? 0) - ($prev->score ?? 0),
            'fixed'        => array_slice($fixed, 0, 10),
            'broken'       => array_slice($broken, 0, 10),
            'scan_date'    => $prev->completed_at,
        ];
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/');
    }
}
