<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessScan implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries = 1;

    public function __construct(public readonly Scan $scan) {}

    public function handle(ScanService $scanService): void
    {
        $this->scan->update(['status' => 'running']);

        $results = $scanService->run(
            $this->scan->host,
            fn(array $partial) => $this->scan->update(['results' => $partial]),
        );

        $this->scan->update([
            'status'       => 'completed',
            'score'        => $results['score'],
            'grade'        => $results['grade'],
            'results'      => $results['categories'],
            'completed_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('ProcessScan job failed', [
            'scan_id' => $this->scan->id,
            'host'    => $this->scan->host,
            'error'   => $exception->getMessage(),
            'class'   => get_class($exception),
            'file'    => $exception->getFile() . ':' . $exception->getLine(),
        ]);

        $this->scan->update([
            'status'     => 'failed',
            'results'    => ['_error' => $exception->getMessage()],
        ]);
    }
}
