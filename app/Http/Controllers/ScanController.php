<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Services\ScanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index()
    {
        $scanCount = Scan::where('status', 'completed')->count();
        return view('welcome', compact('scanCount'));
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
        } catch (\Throwable $e) {
            $scan->update(['status' => 'failed']);
        }

        return redirect()->route('scan.show', $scan);
    }

    public function show(Scan $scan)
    {
        return view('scan.show', compact('scan'));
    }

    public function pdf(Scan $scan)
    {
        abort_unless($scan->isCompleted(), 404);

        $pdf = Pdf::loadView('scan.pdf', compact('scan'))
            ->setPaper('a4', 'portrait');

        $filename = 'security-report-' . $scan->host . '-' . $scan->completed_at->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function status(Scan $scan)
    {
        return response()->json([
            'status'    => $scan->status,
            'score'     => $scan->score,
            'grade'     => $scan->grade,
            'completed' => $scan->isCompleted(),
            'failed'    => $scan->isFailed(),
        ]);
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
