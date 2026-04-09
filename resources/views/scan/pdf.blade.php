<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page {
        margin: 20px 30px 50px 30px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #1f2937;
        background: #ffffff;
        line-height: 1.5;
    }

    /* ── Running footer ── */
    footer {
        position: fixed;
        bottom: -30px;
        left: 0;
        right: 0;
        height: 30px;
        font-size: 8px;
        color: #9ca3af;
        border-top: 1px solid #e5e7eb;
        padding-top: 6px;
    }
    footer .pg::after { content: counter(page); }

    /* ── Cover ── */
    .cover-page {
        page-break-after: always;
        text-align: center;
    }
    .cover-top {
        background: #1e1b4b;
        color: #ffffff;
        padding: 50px 40px 40px;
        margin: -20px -30px 0 -30px;
    }
    .cover-brand { font-size: 32px; font-weight: bold; }
    .cover-brand-accent { color: #818cf8; }
    .cover-tagline {
        font-size: 10px; color: #a5b4fc;
        letter-spacing: 4px; text-transform: uppercase;
        margin-top: 4px;
    }
    .cover-middle {
        padding: 40px 40px 20px;
    }
    .cover-host {
        font-size: 22px; font-weight: bold; color: #1e1b4b;
        margin-bottom: 8px;
    }
    .cover-tier-badge {
        display: inline-block;
        background: #e0e7ff; color: #3730a3;
        padding: 4px 20px; border-radius: 20px;
        font-size: 10px; text-transform: uppercase;
        letter-spacing: 2px; font-weight: bold;
    }

    /* ── Score ring (pure CSS) ── */
    .score-ring-wrap { margin: 30px auto; }
    .score-ring-table { margin: 0 auto; }
    .score-ring-outer {
        width: 160px; height: 160px;
        border-radius: 50%;
        border: 14px solid #e5e7eb;
        text-align: center;
        position: relative;
    }
    .score-ring-num {
        font-size: 48px; font-weight: bold; color: #1e1b4b;
        line-height: 1; padding-top: 30px;
    }
    .score-ring-label {
        font-size: 10px; color: #6b7280; margin-top: 2px;
    }
    .score-ring-grade {
        font-size: 20px; font-weight: bold; margin-top: 4px;
    }

    .cover-meta {
        font-size: 9px; color: #6b7280; line-height: 2;
        margin-top: 20px;
    }
    .cover-divider {
        width: 60px; height: 2px; background: #e2e8f0;
        margin: 15px auto;
    }
    .cover-conf {
        font-size: 8px; color: #c9c9c9;
        text-transform: uppercase; letter-spacing: 4px;
        margin-top: 30px;
    }

    /* ── Grade colors ── */
    .grade-a  { color: #10b981; }
    .grade-b  { color: #22c55e; }
    .grade-c  { color: #eab308; }
    .grade-d  { color: #f97316; }
    .grade-f  { color: #ef4444; }
    .ring-a { border-color: #10b981; }
    .ring-b { border-color: #22c55e; }
    .ring-c { border-color: #eab308; }
    .ring-d { border-color: #f97316; }
    .ring-f { border-color: #ef4444; }

    /* ── Section titles ── */
    .section-title {
        font-size: 14px; font-weight: bold; color: #1e1b4b;
        margin-bottom: 10px; padding-bottom: 6px;
        border-bottom: 2px solid #e2e8f0;
        margin-top: 22px;
    }
    .section-title:first-child { margin-top: 0; }
    .st-icon {
        display: inline-block;
        width: 18px; height: 18px;
        border-radius: 4px;
        color: #ffffff;
        text-align: center; line-height: 18px;
        font-size: 10px;
        margin-right: 6px;
        vertical-align: middle;
    }

    /* ── Stats row ── */
    .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .stats-table td { padding: 10px; text-align: center; }
    .stat-box {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 12px 8px;
        background: #f8fafc;
    }
    .stat-number { font-size: 26px; font-weight: bold; }
    .stat-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-fail .stat-number { color: #dc2626; }
    .stat-warn .stat-number { color: #d97706; }
    .stat-pass .stat-number { color: #16a34a; }

    /* ── Donut chart (CSS) ── */
    .donut-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .donut-table td { vertical-align: middle; }
    .donut-bar { height: 16px; border-radius: 8px; overflow: hidden; background: #e5e7eb; }
    .donut-seg { height: 16px; float: left; }
    .donut-seg-pass { background: #22c55e; }
    .donut-seg-warn { background: #eab308; }
    .donut-seg-fail { background: #ef4444; }
    .donut-legend { font-size: 9px; color: #6b7280; margin-top: 6px; }
    .legend-dot {
        display: inline-block; width: 8px; height: 8px;
        border-radius: 50%; margin-right: 3px; vertical-align: middle;
    }

    /* ── Category score bars ── */
    .cat-bars { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .cat-bars td { padding: 5px 6px; vertical-align: middle; }
    .cb-label { width: 130px; font-size: 10px; color: #374151; }
    .cb-track { height: 12px; background: #e5e7eb; border-radius: 6px; overflow: hidden; width: 100%; }
    .cb-fill { height: 12px; border-radius: 6px; }
    .cb-score { width: 50px; text-align: right; font-weight: bold; font-size: 10px; }

    .bar-green  { background: #22c55e; }
    .bar-yellow { background: #eab308; }
    .bar-orange { background: #f97316; }
    .bar-red    { background: #ef4444; }
    .sc-green  { color: #16a34a; }
    .sc-yellow { color: #ca8a04; }
    .sc-orange { color: #ea580c; }
    .sc-red    { color: #dc2626; }

    /* ── Executive summary ── */
    .exec-text { font-size: 10.5px; color: #374151; line-height: 1.7; margin-bottom: 12px; }
    .exec-text p { margin-bottom: 8px; }

    .area-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .area-table td { padding: 4px 8px; font-size: 10px; vertical-align: middle; }
    .area-good { background: #f0fdf4; color: #166534; }
    .area-med  { background: #fffbeb; color: #92400e; }
    .area-bad  { background: #fef2f2; color: #991b1b; }
    .area-icon { width: 20px; font-weight: bold; }

    /* ── OWASP / Issues ── */
    .issue-item {
        padding: 8px 10px; margin-bottom: 5px;
        border-radius: 5px; border-left: 4px solid;
    }
    .issue-fail { background: #fef2f2; border-color: #ef4444; }
    .issue-warn { background: #fffbeb; border-color: #f59e0b; }
    .issue-pass { background: #f0fdf4; border-color: #22c55e; }

    .issue-head { margin-bottom: 2px; }
    .issue-head-table { width: 100%; border-collapse: collapse; }
    .issue-head-table td { padding: 0; }
    .issue-label { font-weight: bold; font-size: 10.5px; color: #1f2937; }
    .issue-desc  { font-size: 9.5px; color: #4b5563; margin-top: 2px; }
    .issue-fix   { font-size: 9px; color: #4338ca; margin-top: 3px; }

    .issue-badge {
        display: inline-block; font-size: 8px;
        padding: 1px 6px; border-radius: 10px;
        font-weight: normal;
    }
    .badge-fail { background: #fee2e2; color: #b91c1c; }
    .badge-warn { background: #fef9c3; color: #92400e; }

    .risk-badge {
        font-size: 8px; font-weight: bold;
        padding: 2px 8px; border-radius: 10px;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .risk-critical { background: #fee2e2; color: #b91c1c; }
    .risk-high     { background: #ffedd5; color: #c2410c; }
    .risk-medium   { background: #fef9c3; color: #a16207; }
    .risk-low      { background: #dcfce7; color: #166534; }

    /* ── Passed checks (2-col table) ── */
    .pass-table { width: 100%; border-collapse: collapse; }
    .pass-table td {
        padding: 3px 6px; font-size: 9.5px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: top; width: 50%;
    }
    .pass-tick { color: #16a34a; font-weight: bold; }
    .pass-cat  { color: #b0b0b0; font-size: 8px; }

    /* ── Technology ── */
    .tech-panel {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 6px; padding: 10px 12px;
    }
    .tech-row { margin-bottom: 4px; }
    .tech-type {
        font-size: 9px; color: #6b7280; text-transform: uppercase;
        letter-spacing: 0.5px; display: inline-block; width: 90px;
    }
    .tech-badge {
        display: inline-block; background: #e0e7ff; color: #3730a3;
        font-size: 9px; padding: 2px 8px; border-radius: 10px;
        margin-right: 3px; margin-bottom: 2px;
    }

    /* ── Category detail ── */
    .cat-section { margin-bottom: 14px; page-break-inside: avoid; }
    .cat-header {
        background: #f1f5f9; padding: 7px 10px;
        border-radius: 5px; margin-bottom: 5px;
    }
    .cat-header-table { width: 100%; border-collapse: collapse; }
    .cat-header-table td { padding: 0; }
    .cat-name { font-weight: bold; font-size: 11px; color: #1e1b4b; }
    .cat-score { font-weight: bold; font-size: 11px; text-align: right; }

    /* ── Report footer ── */
    .report-footer {
        margin-top: 24px; padding: 16px 0;
        border-top: 2px solid #e2e8f0;
        text-align: center; font-size: 9px;
        color: #9ca3af; line-height: 1.8;
    }
    .report-footer strong { color: #6b7280; }

    .page-break { page-break-after: always; }
    .no-break   { page-break-inside: avoid; }
</style>
</head>
<body>

{{-- ══ DATA PREP ══ --}}
@php
    $allChecks = collect($scan->results)
        ->filter(fn($c) => isset($c['score']) && $c['score'] !== null)
        ->flatMap(fn($c) => collect($c['checks'] ?? [])->map(fn($ch) => array_merge($ch, ['_cat' => $c['category']])));
    $failures  = $allChecks->where('status', 'fail');
    $warnings  = $allChecks->where('status', 'warn');
    $passes    = $allChecks->where('status', 'pass');
    $totalChecks = max($allChecks->count(), 1);

    $gc = $scan->score >= 90 ? 'a'
        : ($scan->score >= 75 ? 'b'
        : ($scan->score >= 60 ? 'c'
        : ($scan->score >= 40 ? 'd' : 'f')));

    $weightedKeys = ['ssl', 'headers', 'dns', 'performance', 'content', 'exposed_files'];

    $passP = round($passes->count() / $totalChecks * 100);
    $warnP = round($warnings->count() / $totalChecks * 100);
    $failP = max(100 - $passP - $warnP, 0);
@endphp

{{-- ══ RUNNING FOOTER ══ --}}
<footer>
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="text-align:left; padding:0; font-size:8px; color:#9ca3af;">WebCheckApp Security Report &mdash; {{ $scan->host }}</td>
            <td style="text-align:right; padding:0; font-size:8px; color:#9ca3af;">Page <span class="pg"></span></td>
        </tr>
    </table>
</footer>

{{-- ══════════════════════════════════════════════
     COVER PAGE
     ══════════════════════════════════════════════ --}}
<div class="cover-page">
    <div class="cover-top">
        <div class="cover-brand">WebCheck<span class="cover-brand-accent">App</span></div>
        <div class="cover-tagline">Security Report</div>
    </div>

    <div class="cover-middle">
        {{-- Score ring --}}
        <div class="score-ring-wrap">
            <table class="score-ring-table">
                <tr>
                    <td style="text-align:center;">
                        <div class="score-ring-outer ring-{{ $gc }}">
                            <div class="score-ring-num">{{ $scan->score }}</div>
                            <div class="score-ring-label">out of 100</div>
                            <div class="score-ring-grade grade-{{ $gc }}">Grade {{ $scan->grade }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="cover-host">{{ $scan->host }}</div>
        <div style="margin-bottom: 20px;">
            <span class="cover-tier-badge">{{ $scan->tierLabel() }}</span>
        </div>

        <div class="cover-divider"></div>

        <div class="cover-meta">
            Scanned: {{ $scan->completed_at->format('d M Y, H:i') }} UTC<br>
            Report generated: {{ now()->format('d M Y, H:i') }} UTC<br>
            Categories: {{ count($scan->results ?? []) }} &bull; Checks: {{ $allChecks->count() }}
        </div>

        <div class="cover-conf">Confidential</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     SCORE OVERVIEW
     ══════════════════════════════════════════════ --}}
<div class="section-title" style="margin-top:0;">
    <span class="st-icon" style="background:#4338ca;">&#9733;</span> Score Overview
</div>

{{-- Stat boxes --}}
<table class="stats-table">
    <tr>
        <td style="width:25%; padding:4px;">
            <div class="stat-box">
                <div class="stat-number" style="color:#1e1b4b;">{{ $scan->score }}</div>
                <div class="stat-label">Score /100</div>
            </div>
        </td>
        <td style="width:25%; padding:4px;">
            <div class="stat-box stat-fail">
                <div class="stat-number">{{ $failures->count() }}</div>
                <div class="stat-label">Critical</div>
            </div>
        </td>
        <td style="width:25%; padding:4px;">
            <div class="stat-box stat-warn">
                <div class="stat-number">{{ $warnings->count() }}</div>
                <div class="stat-label">Warnings</div>
            </div>
        </td>
        <td style="width:25%; padding:4px;">
            <div class="stat-box stat-pass">
                <div class="stat-number">{{ $passes->count() }}</div>
                <div class="stat-label">Passed</div>
            </div>
        </td>
    </tr>
</table>

{{-- Distribution bar --}}
<div style="margin-bottom: 16px;">
    <div style="font-size:9px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">
        Check Results Distribution
    </div>
    <div class="donut-bar">
        @if($passes->count() > 0)
        <div class="donut-seg donut-seg-pass" style="width:{{ $passP }}%;"></div>
        @endif
        @if($warnings->count() > 0)
        <div class="donut-seg donut-seg-warn" style="width:{{ $warnP }}%;"></div>
        @endif
        @if($failures->count() > 0)
        <div class="donut-seg donut-seg-fail" style="width:{{ $failP }}%;"></div>
        @endif
    </div>
    <div class="donut-legend">
        <span class="legend-dot" style="background:#22c55e;"></span> Passed ({{ $passes->count() }})
        &nbsp;&nbsp;
        <span class="legend-dot" style="background:#eab308;"></span> Warnings ({{ $warnings->count() }})
        &nbsp;&nbsp;
        <span class="legend-dot" style="background:#ef4444;"></span> Critical ({{ $failures->count() }})
    </div>
</div>

{{-- Category score bars --}}
<div style="font-size:9px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">
    Category Scores
</div>
<table class="cat-bars">
    @foreach($weightedKeys as $wk)
    @if(isset($scan->results[$wk]) && $scan->results[$wk]['score'] !== null)
    @php
        $s = $scan->results[$wk]['score'];
        $bc = $s >= 75 ? 'bar-green' : ($s >= 50 ? 'bar-yellow' : ($s >= 25 ? 'bar-orange' : 'bar-red'));
        $sc = $s >= 75 ? 'sc-green' : ($s >= 50 ? 'sc-yellow' : ($s >= 25 ? 'sc-orange' : 'sc-red'));
    @endphp
    <tr>
        <td class="cb-label">{{ $scan->results[$wk]['category'] }}</td>
        <td>
            <div class="cb-track">
                <div class="cb-fill {{ $bc }}" style="width:{{ $s }}%;"></div>
            </div>
        </td>
        <td class="cb-score {{ $sc }}">{{ $s }}/100</td>
    </tr>
    @endif
    @endforeach
</table>

{{-- ══ EXECUTIVE SUMMARY ══ --}}
@php
    $catCount = collect($scan->results)->filter(fn($c) => isset($c['score']) && $c['score'] !== null)->count();
    $strongAreas = collect($scan->results)
        ->filter(fn($c) => isset($c['score']) && $c['score'] >= 80)
        ->pluck('category')->take(5);
    $weakAreas = collect($scan->results)
        ->filter(fn($c) => isset($c['score']) && $c['score'] !== null && $c['score'] < 60)
        ->sortBy('score')->pluck('category')->take(5);
    $mediumAreas = collect($scan->results)
        ->filter(fn($c) => isset($c['score']) && $c['score'] >= 60 && $c['score'] < 80)
        ->pluck('category')->take(5);
@endphp

<div class="section-title">
    <span class="st-icon" style="background:#6366f1;">&#9998;</span> Executive Summary
</div>

<div class="exec-text">
    <p>
        We performed a comprehensive security analysis of <strong>{{ $scan->host }}</strong> across {{ $catCount }} categories.
        The website received an overall score of <strong>{{ $scan->score }}/100</strong> (grade <strong>{{ $scan->grade }}</strong>),
        with {{ $failures->count() }} critical {{ $failures->count() === 1 ? 'issue' : 'issues' }},
        {{ $warnings->count() }} {{ $warnings->count() === 1 ? 'warning' : 'warnings' }},
        and {{ $passes->count() }} passed {{ $passes->count() === 1 ? 'check' : 'checks' }}.
    </p>

    @if($scan->score >= 85)
    <p><strong>Overall assessment:</strong> {{ $scan->host }} demonstrates a strong security posture. The website follows most security best practices and is well-configured. Minor improvements are possible but no urgent issues were found.</p>
    @elseif($scan->score >= 65)
    <p><strong>Overall assessment:</strong> {{ $scan->host }} has a reasonable security foundation but there is room for improvement. Several issues were identified that could expose the website or its users to unnecessary risk. We recommend addressing the critical issues first.</p>
    @elseif($scan->score >= 40)
    <p><strong>Overall assessment:</strong> {{ $scan->host }} has significant security gaps that should be addressed as soon as possible. The current configuration leaves the website vulnerable to common attacks.</p>
    @else
    <p><strong>Overall assessment:</strong> {{ $scan->host }} has serious security deficiencies across multiple areas. The website is at high risk. Immediate action is required.</p>
    @endif
</div>

@if($strongAreas->isNotEmpty())
<table class="area-table">
    @foreach($strongAreas as $area)
    <tr><td class="area-good area-icon">&#10003;</td><td class="area-good">{{ $area }}</td></tr>
    @endforeach
</table>
@endif

@if($mediumAreas->isNotEmpty())
<table class="area-table">
    @foreach($mediumAreas as $area)
    <tr><td class="area-med area-icon">&#9888;</td><td class="area-med">{{ $area }}</td></tr>
    @endforeach
</table>
@endif

@if($weakAreas->isNotEmpty())
<table class="area-table">
    @foreach($weakAreas as $area)
    <tr><td class="area-bad area-icon">&#10007;</td><td class="area-bad">{{ $area }}</td></tr>
    @endforeach
</table>
@endif

<div class="page-break"></div>

{{-- ══════════════════════════════════════════════
     OWASP TOP 10
     ══════════════════════════════════════════════ --}}
@if(isset($scan->results['owasp']) && !empty($scan->results['owasp']['checks']))
<div class="section-title" style="margin-top:0;">
    <span class="st-icon" style="background:#7c3aed;">&#9888;</span> OWASP Top 10 Analysis
    <span style="float:right; font-size:11px; color:#7c3aed;">Score: {{ $scan->results['owasp']['score'] ?? 0 }}/100</span>
</div>
<div style="font-size: 9.5px; color: #4b5563; margin-bottom: 10px;">
    The OWASP Top 10 is the globally recognized standard for web application security risks.
</div>

@foreach($scan->results['owasp']['checks'] as $check)
@php
    $riskClass = match($check['risk'] ?? 'Medium') {
        'Critical' => 'risk-critical', 'High' => 'risk-high',
        'Medium'   => 'risk-medium',   'Low'  => 'risk-low',
        default    => 'risk-medium',
    };
    $itemClass = match($check['status']) {
        'pass' => 'issue-pass', 'warn' => 'issue-warn', default => 'issue-fail',
    };
    $icon = match($check['status']) {
        'pass' => '&#10003;', 'warn' => '&#9888;', default => '&#10007;',
    };
@endphp
<div class="issue-item {{ $itemClass }} no-break">
    <table class="issue-head-table">
        <tr>
            <td class="issue-label">{!! $icon !!} {{ $check['label'] }}</td>
            <td style="text-align:right;"><span class="risk-badge {{ $riskClass }}">{{ $check['risk'] ?? '' }} Risk</span></td>
        </tr>
    </table>
    <div class="issue-desc">{{ $check['description'] }}</div>
    @if(!empty($check['recommendation']))
    <div class="issue-fix">&#128161; {{ $check['recommendation'] }}</div>
    @endif
</div>
@endforeach
@endif

{{-- ══ CRITICAL ISSUES ══ --}}
@if($failures->count() > 0)
<div class="section-title">
    <span class="st-icon" style="background:#dc2626;">&#10007;</span> Critical Issues ({{ $failures->count() }})
</div>
<div style="font-size: 9.5px; color: #4b5563; margin-bottom: 8px;">
    These issues pose an immediate security risk and should be addressed as a priority.
</div>
@foreach($failures as $check)
<div class="issue-item issue-fail no-break">
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
@endif

{{-- ══ WARNINGS ══ --}}
@if($warnings->count() > 0)
<div class="section-title">
    <span class="st-icon" style="background:#f59e0b;">&#9888;</span> Warnings ({{ $warnings->count() }})
</div>
<div style="font-size: 9.5px; color: #4b5563; margin-bottom: 8px;">
    These items are not immediately critical but should be reviewed to strengthen your security posture.
</div>
@foreach($warnings as $check)
<div class="issue-item issue-warn no-break">
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
@endif

{{-- ══ PASSED CHECKS (2-column) ══ --}}
@if($passes->count() > 0)
<div class="section-title">
    <span class="st-icon" style="background:#16a34a;">&#10003;</span> Passed Checks ({{ $passes->count() }})
</div>
<div style="font-size: 9.5px; color: #4b5563; margin-bottom: 8px;">
    These checks were all successfully validated.
</div>
@php $half = ceil($passes->count() / 2); $passArr = $passes->values(); @endphp
<table class="pass-table">
    @for($i = 0; $i < $half; $i++)
    <tr>
        <td>
            <span class="pass-tick">&#10003;</span>
            {{ $passArr[$i]['label'] }}
            <span class="pass-cat">{{ $passArr[$i]['_cat'] }}</span>
        </td>
        <td>
            @if(isset($passArr[$i + $half]))
            <span class="pass-tick">&#10003;</span>
            {{ $passArr[$i + $half]['label'] }}
            <span class="pass-cat">{{ $passArr[$i + $half]['_cat'] }}</span>
            @endif
        </td>
    </tr>
    @endfor
</table>
@endif

<div class="page-break"></div>

{{-- ══ TECHNOLOGY STACK ══ --}}
@if(!empty($scan->results['technology']['technologies']))
<div class="section-title" style="margin-top:0;">
    <span class="st-icon" style="background:#0891b2;">&#9881;</span> Detected Technologies
</div>
<div style="font-size: 9.5px; color: #4b5563; margin-bottom: 8px;">
    The following technologies were detected on {{ $scan->host }}.
</div>
<div class="tech-panel">
    @php $byType = collect($scan->results['technology']['technologies'])->groupBy('type'); @endphp
    @foreach($byType as $type => $items)
    <div class="tech-row">
        <span class="tech-type">{{ $type }}</span>
        @foreach($items as $item)
        <span class="tech-badge">{{ $item['name'] }}</span>
        @endforeach
    </div>
    @endforeach
    @foreach($scan->results['technology']['checks'] ?? [] as $check)
    <div style="margin-top:5px; font-size:9.5px; color:#374151;">
        <span style="color:{{ $check['status'] === 'pass' ? '#16a34a' : '#d97706' }};">
            {{ $check['status'] === 'pass' ? '&#10003;' : '&#9888;' }}
        </span>
        <strong>{{ $check['label'] }}</strong> &mdash; {{ $check['description'] }}
    </div>
    @endforeach
</div>
@endif

{{-- ══ CATEGORY DETAILS ══ --}}
<div class="section-title">
    <span class="st-icon" style="background:#1e1b4b;">&#9776;</span> Category Details
</div>

@php
    $detailCategories = collect($scan->results)
        ->filter(fn($cat, $key) => isset($cat['score']) && $cat['score'] !== null && !in_array($key, ['technology', 'owasp']))
        ->sortBy('score');
@endphp

@foreach($detailCategories as $key => $cat)
@php
    $s = $cat['score'];
    $sc = $s >= 75 ? 'sc-green' : ($s >= 50 ? 'sc-yellow' : ($s >= 25 ? 'sc-orange' : 'sc-red'));
    $nonPassing = collect($cat['checks'] ?? [])->whereIn('status', ['fail', 'warn']);
@endphp
<div class="cat-section">
    <div class="cat-header">
        <table class="cat-header-table">
            <tr>
                <td class="cat-name">{{ $cat['category'] }}</td>
                <td class="cat-score {{ $sc }}">{{ $s }}/100</td>
            </tr>
        </table>
    </div>
    @if($nonPassing->isEmpty())
    <div style="padding: 5px 10px; font-size: 10px; color: #16a34a;">&#10003; All checks passed</div>
    @else
    @foreach($nonPassing as $check)
    @php $ic = $check['status'] === 'warn' ? 'issue-warn' : 'issue-fail'; @endphp
    <div class="issue-item {{ $ic }}">
        <div class="issue-label">{{ $check['label'] }}</div>
        <div class="issue-desc">{{ $check['description'] }}</div>
        @if(!empty($check['recommendation']))
        <div class="issue-fix">&#128161; {{ $check['recommendation'] }}</div>
        @endif
    </div>
    @endforeach
    @endif
</div>
@endforeach

{{-- ══ REPORT FOOTER ══ --}}
<div class="report-footer">
    <strong>WebCheckApp</strong> &mdash; Website Security Scanner<br>
    Report generated on {{ now()->format('d M Y, H:i') }} UTC &mdash; {{ $scan->tierLabel() }}<br><br>
    Scan results are for informational purposes only and do not constitute professional security advice.
    Results are based on automated checks of publicly accessible information only.<br><br>
    <strong>Need a professional security audit?</strong><br>
    Visit budgetpixels.nl for manual penetration tests, code reviews, and compliance checks.
</div>

</body>
</html>
