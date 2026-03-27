<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #1f2937;
        background: #ffffff;
        line-height: 1.5;
    }

    /* ── Header ── */
    .header {
        background: #1e1b4b;
        color: #ffffff;
        padding: 24px 32px;
        margin-bottom: 0;
    }
    .header-inner {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .brand {
        font-size: 18px;
        font-weight: bold;
        color: #ffffff;
        letter-spacing: 0.5px;
    }
    .brand span { color: #818cf8; }
    .header-meta {
        text-align: right;
        font-size: 10px;
        color: #a5b4fc;
    }
    .header-host {
        font-size: 13px;
        font-weight: bold;
        color: #ffffff;
        margin-top: 10px;
    }
    .header-subtitle {
        font-size: 10px;
        color: #a5b4fc;
        margin-top: 2px;
    }

    /* ── Score banner ── */
    .score-banner {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding: 20px 32px;
        display: flex;
        align-items: center;
        gap: 32px;
    }
    .score-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 6px solid #6366f1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        flex-shrink: 0;
    }
    .score-circle .score-num {
        font-size: 22px;
        font-weight: bold;
        color: #1e1b4b;
        line-height: 1;
    }
    .score-circle .score-denom {
        font-size: 9px;
        color: #6b7280;
    }
    .grade-box {
        text-align: center;
        flex-shrink: 0;
    }
    .grade-letter {
        font-size: 48px;
        font-weight: 900;
        line-height: 1;
    }
    .grade-label {
        font-size: 9px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .grade-a  { color: #10b981; }
    .grade-b  { color: #22c55e; }
    .grade-c  { color: #eab308; }
    .grade-d  { color: #f97316; }
    .grade-f  { color: #ef4444; }

    .category-scores {
        flex: 1;
    }
    .category-scores table {
        width: 100%;
        border-collapse: collapse;
    }
    .category-scores td {
        padding: 3px 8px 3px 0;
        font-size: 10px;
        vertical-align: middle;
    }
    .cat-label { color: #374151; width: 140px; }
    .cat-bar-wrap {
        width: 100%;
        background: #e5e7eb;
        border-radius: 3px;
        height: 7px;
    }
    .cat-bar { height: 7px; border-radius: 3px; }
    .bar-green  { background: #22c55e; }
    .bar-yellow { background: #eab308; }
    .bar-red    { background: #ef4444; }
    .cat-score { width: 40px; text-align: right; font-weight: bold; }
    .score-green  { color: #16a34a; }
    .score-yellow { color: #ca8a04; }
    .score-red    { color: #dc2626; }

    /* ── Content area ── */
    .content { padding: 24px 32px; }

    /* ── Section title ── */
    .section-title {
        font-size: 13px;
        font-weight: bold;
        color: #1e1b4b;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #e2e8f0;
    }

    /* ── Issue boxes ── */
    .issue-list { margin-bottom: 20px; }
    .issue-item {
        padding: 8px 10px;
        margin-bottom: 6px;
        border-radius: 4px;
        border-left: 3px solid;
    }
    .issue-fail   { background: #fef2f2; border-color: #ef4444; }
    .issue-warn   { background: #fffbeb; border-color: #f59e0b; }
    .issue-pass   { background: #f0fdf4; border-color: #22c55e; }
    .issue-info   { background: #f0f9ff; border-color: #60a5fa; }
    .issue-label  { font-weight: bold; font-size: 10px; margin-bottom: 2px; }
    .issue-desc   { font-size: 9.5px; color: #4b5563; }
    .issue-fix    { font-size: 9px; color: #4338ca; margin-top: 3px; }
    .issue-badge  {
        display: inline-block;
        font-size: 8px;
        padding: 1px 5px;
        border-radius: 10px;
        margin-left: 5px;
        font-weight: normal;
        vertical-align: middle;
    }
    .badge-fail   { background: #fee2e2; color: #b91c1c; }
    .badge-warn   { background: #fef9c3; color: #92400e; }
    .badge-pass   { background: #dcfce7; color: #166534; }

    /* ── Category sections ── */
    .cat-section { margin-bottom: 18px; page-break-inside: avoid; }
    .cat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f1f5f9;
        padding: 6px 10px;
        border-radius: 4px;
        margin-bottom: 6px;
    }
    .cat-header-name { font-weight: bold; font-size: 11px; color: #1e1b4b; }
    .cat-header-score { font-weight: bold; font-size: 11px; }

    /* ── Technology panel ── */
    .tech-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 10px 12px;
        margin-bottom: 20px;
    }
    .tech-group { margin-bottom: 6px; }
    .tech-type-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; width: 100px; }
    .tech-badge {
        display: inline-block;
        background: #e0e7ff;
        color: #3730a3;
        font-size: 9px;
        padding: 1px 7px;
        border-radius: 10px;
        margin-right: 4px;
        margin-bottom: 2px;
    }

    /* ── Footer ── */
    .footer {
        margin-top: 30px;
        padding: 14px 32px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        font-size: 9px;
        color: #9ca3af;
        text-align: center;
    }

    .page-break { page-break-after: always; }
    .no-break   { page-break-inside: avoid; }
</style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="header">
    <div class="header-inner">
        <div>
            <div class="brand">WebCheck<span>App</span></div>
            <div class="header-host">{{ $scan->host }}</div>
            <div class="header-subtitle">Security Scan Report</div>
        </div>
        <div class="header-meta">
            <div>Generated: {{ now()->format('d M Y, H:i') }} UTC</div>
            <div style="margin-top:3px;">Scanned: {{ $scan->completed_at->format('d M Y, H:i') }} UTC</div>
            <div style="margin-top:3px;">webcheckapp.com</div>
        </div>
    </div>
</div>

{{-- ── SCORE BANNER ── --}}
<div class="score-banner">

    {{-- Score circle --}}
    <div class="score-circle">
        <div class="score-num">{{ $scan->score }}</div>
        <div class="score-denom">/100</div>
    </div>

    {{-- Grade --}}
    <div class="grade-box">
        @php
            $gradeClass = $scan->score >= 90 ? 'grade-a' : ($scan->score >= 75 ? 'grade-b' : ($scan->score >= 60 ? 'grade-c' : ($scan->score >= 40 ? 'grade-d' : 'grade-f')));
        @endphp
        <div class="grade-letter {{ $gradeClass }}">{{ $scan->grade }}</div>
        <div class="grade-label">Overall Grade</div>
    </div>

    {{-- Category score bars --}}
    <div class="category-scores">
        <table>
            @foreach($scan->results as $key => $cat)
            @if($cat['score'] !== null)
            @php
                $s = $cat['score'];
                $barClass   = $s >= 75 ? 'bar-green'    : ($s >= 50 ? 'bar-yellow'    : 'bar-red');
                $scoreClass = $s >= 75 ? 'score-green'  : ($s >= 50 ? 'score-yellow'  : 'score-red');
            @endphp
            <tr>
                <td class="cat-label">{{ $cat['category'] }}</td>
                <td>
                    <div class="cat-bar-wrap">
                        <div class="cat-bar {{ $barClass }}" style="width:{{ $s }}%;"></div>
                    </div>
                </td>
                <td class="cat-score {{ $scoreClass }}">{{ $s }}/100</td>
            </tr>
            @endif
            @endforeach
        </table>
    </div>
</div>

{{-- ── MAIN CONTENT ── --}}
<div class="content">

    {{-- Technology stack --}}
    @if(!empty($scan->results['technology']['technologies']))
    <div class="no-break">
        <div class="section-title">Detected Technologies</div>
        <div class="tech-panel">
            @php $byType = collect($scan->results['technology']['technologies'])->groupBy('type'); @endphp
            @foreach($byType as $type => $items)
            <div class="tech-group">
                <span class="tech-type-label">{{ $type }}</span>
                @foreach($items as $item)
                <span class="tech-badge">{{ $item['name'] }}</span>
                @endforeach
            </div>
            @endforeach

            @foreach($scan->results['technology']['checks'] as $check)
            <div style="margin-top:8px; font-size:9.5px; color:#374151;">
                @if($check['status'] === 'pass')
                    <span style="color:#16a34a;">&#10003;</span>
                @else
                    <span style="color:#d97706;">&#9888;</span>
                @endif
                <strong>{{ $check['label'] }}</strong> — {{ $check['description'] }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Critical issues --}}
    @php
        $allChecks = collect($scan->results)
            ->filter(fn($c) => $c['score'] !== null)
            ->flatMap(fn($c) => collect($c['checks'])->map(fn($ch) => array_merge($ch, ['_cat' => $c['category']])));
        $failures = $allChecks->where('status', 'fail');
        $warnings = $allChecks->where('status', 'warn');
    @endphp

    @if($failures->count() > 0)
    <div class="no-break">
        <div class="section-title">Critical Issues ({{ $failures->count() }})</div>
        <div class="issue-list">
            @foreach($failures as $check)
            <div class="issue-item issue-fail">
                <div class="issue-label">
                    {{ $check['label'] }}
                    <span class="issue-badge badge-fail">{{ $check['_cat'] }}</span>
                </div>
                <div class="issue-desc">{{ $check['description'] }}</div>
                @if(!empty($check['recommendation']))
                <div class="issue-fix">&#128161; {{ $check['recommendation'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($warnings->count() > 0)
    <div class="no-break">
        <div class="section-title">Warnings ({{ $warnings->count() }})</div>
        <div class="issue-list">
            @foreach($warnings as $check)
            <div class="issue-item issue-warn">
                <div class="issue-label">
                    {{ $check['label'] }}
                    <span class="issue-badge badge-warn">{{ $check['_cat'] }}</span>
                </div>
                <div class="issue-desc">{{ $check['description'] }}</div>
                @if(!empty($check['recommendation']))
                <div class="issue-fix">&#128161; {{ $check['recommendation'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Full report per category --}}
    <div class="section-title" style="margin-top:8px;">Full Report</div>

    @foreach($scan->results as $key => $cat)
    @if($cat['score'] === null) @continue @endif
    @php
        $s = $cat['score'];
        $scoreClass = $s >= 75 ? 'score-green' : ($s >= 50 ? 'score-yellow' : 'score-red');
    @endphp
    <div class="cat-section">
        <div class="cat-header">
            <span class="cat-header-name">{{ $cat['category'] }}</span>
            <span class="cat-header-score {{ $scoreClass }}">{{ $s }}/100</span>
        </div>
        @foreach($cat['checks'] as $check)
        @php
            $itemClass = match($check['status']) {
                'pass' => 'issue-pass',
                'warn' => 'issue-warn',
                'info' => 'issue-info',
                default => 'issue-fail',
            };
        @endphp
        <div class="issue-item {{ $itemClass }}">
            <div class="issue-label">{{ $check['label'] }}</div>
            <div class="issue-desc">{{ $check['description'] }}</div>
            @if(!empty($check['recommendation']))
            <div class="issue-fix">&#128161; {{ $check['recommendation'] }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endforeach

</div>

{{-- ── FOOTER ── --}}
<div class="footer">
    Report generated by WebCheckApp (webcheckapp.com) &mdash; {{ now()->format('d M Y') }}<br>
    Scan results are for informational purposes only and do not constitute professional security advice.
    Results are based on automated checks of publicly accessible information only.
</div>

</body>
</html>
