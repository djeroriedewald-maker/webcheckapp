<?php

namespace App\Services;

use App\Services\Scanners\ContentScanner;
use App\Services\Scanners\DnsScanner;
use App\Services\Scanners\HeadersScanner;
use App\Services\Scanners\PerformanceScanner;
use App\Services\Scanners\SslScanner;

class ScanService
{
    // Weights for the overall score calculation
    private array $weights = [
        'ssl'         => 30,
        'headers'     => 25,
        'dns'         => 20,
        'performance' => 15,
        'content'     => 10,
    ];

    public function run(string $host): array
    {
        $results = [];

        $scanners = [
            'ssl'         => fn() => (new SslScanner())->scan($host),
            'headers'     => fn() => (new HeadersScanner())->scan($host),
            'dns'         => fn() => (new DnsScanner())->scan($host),
            'performance' => fn() => (new PerformanceScanner())->scan($host),
            'content'     => fn() => (new ContentScanner())->scan($host),
        ];

        foreach ($scanners as $key => $scanner) {
            try {
                $results[$key] = $scanner();
            } catch (\Throwable $e) {
                $results[$key] = [
                    'category' => ucfirst($key),
                    'icon'     => 'exclamation-triangle',
                    'score'    => 0,
                    'checks'   => [[
                        'id'          => "{$key}_error",
                        'label'       => 'Scanner error',
                        'status'      => 'fail',
                        'description' => 'This check could not be completed.',
                    ]],
                ];
            }
        }

        $overallScore = $this->calculateOverallScore($results);
        $grade = $this->scoreToGrade($overallScore);

        return [
            'score'      => $overallScore,
            'grade'      => $grade,
            'categories' => $results,
        ];
    }

    private function calculateOverallScore(array $results): int
    {
        $totalWeight = array_sum($this->weights);
        $weightedScore = 0;

        foreach ($this->weights as $key => $weight) {
            if (isset($results[$key]['score'])) {
                $weightedScore += $results[$key]['score'] * $weight;
            }
        }

        return (int) round($weightedScore / $totalWeight);
    }

    private function scoreToGrade(int $score): string
    {
        return match(true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'A-',
            $score >= 80 => 'B+',
            $score >= 75 => 'B',
            $score >= 70 => 'B-',
            $score >= 65 => 'C+',
            $score >= 60 => 'C',
            $score >= 55 => 'C-',
            $score >= 50 => 'D+',
            $score >= 45 => 'D',
            $score >= 40 => 'D-',
            default      => 'F',
        };
    }
}
