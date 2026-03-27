<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessScan;
use App\Models\Scan;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index()
    {
        return view('welcome');
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
            'status'     => 'pending',
            'ip_address' => $request->ip(),
        ]);

        ProcessScan::dispatch($scan);

        return redirect()->route('scan.show', $scan);
    }

    public function show(Scan $scan)
    {
        return view('scan.show', compact('scan'));
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
