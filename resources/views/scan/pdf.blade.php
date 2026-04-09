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
    .cover-page { page-break-after: always; text-align: center; }
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
        letter-spacing: 4px; text-transform: uppercase; margin-top: 4px;
    }
    .cover-grade-bar {
        height: 6px;
        margin: -1px -30px 0 -30px;
    }
    .cover-middle { padding: 30px 40px 10px; }
    .cover-host { font-size: 22px; font-weight: bold; color: #1e1b4b; margin-bottom: 8px; }
    .cover-tier-badge {
        display: inline-block; background: #e0e7ff; color: #3730a3;
        padding: 4px 20px; border-radius: 20px;
        font-size: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;
    }
    .score-ring-outer {
        width: 150px; height: 150px; border-radius: 50%;
        border: 14px solid #e5e7eb;
        text-align: center; margin: 20px auto;
    }
    .score-ring-num { font-size: 44px; font-weight: bold; color: #1e1b4b; line-height: 1; padding-top: 26px; }
    .score-ring-label { font-size: 10px; color: #6b7280; margin-top: 2px; }
    .score-ring-grade { font-size: 18px; font-weight: bold; margin-top: 2px; }
    .cover-meta { font-size: 9px; color: #6b7280; line-height: 2; margin-top: 16px; }
    .cover-divider { width: 60px; height: 2px; background: #e2e8f0; margin: 12px auto; }
    .cover-prepared {
        margin-top: 20px; padding: 12px 20px;
        border: 1px solid #e2e8f0; border-radius: 6px;
        display: inline-block;
        font-size: 9px; color: #6b7280;
    }
    .cover-conf {
        font-size: 8px; color: #c9c9c9;
        text-transform: uppercase; letter-spacing: 4px; margin-top: 20px;
    }

    /* ── Grade colors ── */
    .grade-a  { color: #10b981; } .ring-a { border-color: #10b981; } .gbar-a { background: #10b981; }
    .grade-b  { color: #22c55e; } .ring-b { border-color: #22c55e; } .gbar-b { background: #22c55e; }
    .grade-c  { color: #eab308; } .ring-c { border-color: #eab308; } .gbar-c { background: #eab308; }
    .grade-d  { color: #f97316; } .ring-d { border-color: #f97316; } .gbar-d { background: #f97316; }
    .grade-f  { color: #ef4444; } .ring-f { border-color: #ef4444; } .gbar-f { background: #ef4444; }

    /* ── Table of Contents ── */
    .toc-table { width: 100%; border-collapse: collapse; }
    .toc-table td { padding: 8px 4px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
    .toc-num { width: 30px; color: #6366f1; font-weight: bold; }
    .toc-title { color: #1e1b4b; }
    .toc-dots { color: #d1d5db; }

    /* ── Section titles ── */
    .section-title {
        font-size: 14px; font-weight: bold; color: #1e1b4b;
        margin-bottom: 10px; padding-bottom: 6px;
        border-bottom: 2px solid #e2e8f0; margin-top: 22px;
    }
    .section-title:first-child { margin-top: 0; }
    .st-icon {
        display: inline-block; width: 18px; height: 18px; border-radius: 4px;
        color: #ffffff; text-align: center; line-height: 18px;
        font-size: 10px; margin-right: 6px; vertical-align: middle;
    }

    /* ── Stats row ── */
    .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .stats-table td { padding: 4px; text-align: center; }
    .stat-box {
        border: 1px solid #e2e8f0; border-radius: 6px;
        padding: 10px 6px; background: #f8fafc;
    }
    .stat-number { font-size: 24px; font-weight: bold; }
    .stat-label { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-fail .stat-number { color: #dc2626; }
    .stat-warn .stat-number { color: #d97706; }
    .stat-pass .stat-number { color: #16a34a; }

    /* ── Distribution bar ── */
    .donut-bar { height: 14px; border-radius: 7px; overflow: hidden; background: #e5e7eb; }
    .donut-seg { height: 14px; float: left; }
    .donut-seg-pass { background: #22c55e; }
    .donut-seg-warn { background: #eab308; }
    .donut-seg-fail { background: #ef4444; }
    .donut-legend { font-size: 9px; color: #6b7280; margin-top: 5px; }
    .legend-dot {
        display: inline-block; width: 8px; height: 8px;
        border-radius: 50%; margin-right: 3px; vertical-align: middle;
    }

    /* ── Scorecard ── */
    .scorecard { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .scorecard th {
        background: #f1f5f9; padding: 6px 8px; text-align: left;
        font-size: 9px; color: #6b7280; text-transform: uppercase;
        letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0;
    }
    .scorecard td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; font-size: 10px; }
    .scorecard .sc-name { font-weight: bold; color: #1e1b4b; }
    .sc-gauge { width: 120px; }
    .sc-gauge-track { height: 10px; background: #e5e7eb; border-radius: 5px; overflow: hidden; }
    .sc-gauge-fill { height: 10px; border-radius: 5px; }
    .sc-status {
        display: inline-block; width: 10px; height: 10px;
        border-radius: 50%; vertical-align: middle;
    }

    /* ── Benchmark ── */
    .benchmark-bar { position: relative; height: 28px; background: #f1f5f9; border-radius: 4px; margin: 10px 0; overflow: visible; }
    .benchmark-fill { height: 28px; border-radius: 4px; }
    .benchmark-marker {
        position: absolute; top: -4px;
        width: 3px; height: 36px; background: #1e1b4b;
    }
    .benchmark-label {
        position: absolute; top: -18px;
        font-size: 8px; font-weight: bold; color: #1e1b4b;
    }

    /* ── Category score bars ── */
    .cat-bars { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .cat-bars td { padding: 4px 6px; vertical-align: middle; }
    .cb-label { width: 130px; font-size: 10px; color: #374151; }
    .cb-track { height: 12px; background: #e5e7eb; border-radius: 6px; overflow: hidden; }
    .cb-fill { height: 12px; border-radius: 6px; }
    .cb-score { width: 50px; text-align: right; font-weight: bold; font-size: 10px; }

    .bar-green  { background: #22c55e; } .sc-green  { color: #16a34a; }
    .bar-yellow { background: #eab308; } .sc-yellow { color: #ca8a04; }
    .bar-orange { background: #f97316; } .sc-orange { color: #ea580c; }
    .bar-red    { background: #ef4444; } .sc-red    { color: #dc2626; }

    /* ── Executive summary ── */
    .exec-text { font-size: 10.5px; color: #374151; line-height: 1.7; margin-bottom: 10px; }
    .exec-text p { margin-bottom: 8px; }
    .area-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    .area-table td { padding: 4px 8px; font-size: 10px; vertical-align: middle; }
    .area-good { background: #f0fdf4; color: #166534; }
    .area-med  { background: #fffbeb; color: #92400e; }
    .area-bad  { background: #fef2f2; color: #991b1b; }
    .area-icon { width: 20px; font-weight: bold; }

    /* ── Action Plan ── */
    .action-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .action-table th {
        background: #1e1b4b; color: #ffffff; padding: 7px 8px;
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left;
    }
    .action-table td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; font-size: 9.5px; vertical-align: top; }
    .action-num {
        width: 24px; height: 24px; border-radius: 50%;
        background: #e0e7ff; color: #3730a3;
        text-align: center; line-height: 24px;
        font-weight: bold; font-size: 10px;
    }
    .urgency-now {
        display: inline-block; background: #fee2e2; color: #b91c1c;
        padding: 1px 8px; border-radius: 10px; font-size: 8px; font-weight: bold;
    }
    .urgency-week {
        display: inline-block; background: #fef9c3; color: #92400e;
        padding: 1px 8px; border-radius: 10px; font-size: 8px; font-weight: bold;
    }
    .urgency-month {
        display: inline-block; background: #e0e7ff; color: #3730a3;
        padding: 1px 8px; border-radius: 10px; font-size: 8px; font-weight: bold;
    }

    /* ── Risk Matrix ── */
    .risk-matrix { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .risk-matrix th {
        background: #f1f5f9; padding: 6px 8px; font-size: 9px;
        color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0; text-align: center;
    }
    .risk-matrix th:first-child { text-align: left; }
    .risk-matrix td {
        padding: 6px 8px; border-bottom: 1px solid #f3f4f6;
        font-size: 10px; text-align: center;
    }
    .risk-matrix td:first-child { text-align: left; font-weight: bold; color: #1e1b4b; }
    .rm-count {
        display: inline-block; width: 22px; height: 22px; border-radius: 50%;
        text-align: center; line-height: 22px; font-weight: bold; font-size: 9px;
    }
    .rm-red  { background: #fee2e2; color: #b91c1c; }
    .rm-yellow { background: #fef9c3; color: #92400e; }
    .rm-green { background: #dcfce7; color: #166534; }
    .rm-empty { color: #d1d5db; }

    /* ── Issues ── */
    .issue-item {
        padding: 8px 10px; margin-bottom: 5px;
        border-radius: 5px; border-left: 4px solid;
    }
    .issue-fail { background: #fef2f2; border-color: #ef4444; }
    .issue-warn { background: #fffbeb; border-color: #f59e0b; }
    .issue-pass { background: #f0fdf4; border-color: #22c55e; }
    .issue-head-table { width: 100%; border-collapse: collapse; }
    .issue-head-table td { padding: 0; }
    .issue-label { font-weight: bold; font-size: 10.5px; color: #1f2937; }
    .issue-desc  { font-size: 9.5px; color: #4b5563; margin-top: 2px; }
    .issue-fix   { font-size: 9px; color: #4338ca; margin-top: 3px; }
    .issue-badge {
        display: inline-block; font-size: 8px;
        padding: 1px 6px; border-radius: 10px; font-weight: normal;
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

    /* ── What This Means box ── */
    .wtm-box {
        background: #eff6ff; border: 1px solid #bfdbfe;
        border-radius: 5px; padding: 6px 10px;
        margin-top: 4px; font-size: 9px; color: #1e40af;
    }
    .wtm-label { font-weight: bold; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #3b82f6; margin-bottom: 2px; }

    /* ── Passed checks (2-col) ── */
    .pass-table { width: 100%; border-collapse: collapse; }
    .pass-table td {
        padding: 3px 6px; font-size: 9.5px;
        border-bottom: 1px solid #f3f4f6; vertical-align: top; width: 50%;
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
        background: #f1f5f9; padding: 7px 10px; border-radius: 5px; margin-bottom: 5px;
    }
    .cat-header-table { width: 100%; border-collapse: collapse; }
    .cat-header-table td { padding: 0; }
    .cat-name { font-weight: bold; font-size: 11px; color: #1e1b4b; }
    .cat-score { font-weight: bold; font-size: 11px; text-align: right; }

    /* ── Closing page ── */
    .closing-box {
        border: 1px solid #e2e8f0; border-radius: 6px;
        padding: 16px 20px; margin-bottom: 14px; background: #f8fafc;
    }
    .closing-box-title { font-size: 11px; font-weight: bold; color: #1e1b4b; margin-bottom: 8px; }
    .closing-step { margin-bottom: 6px; font-size: 10px; color: #374151; }
    .closing-step-num {
        display: inline-block; width: 20px; height: 20px; border-radius: 50%;
        background: #6366f1; color: #ffffff;
        text-align: center; line-height: 20px;
        font-size: 9px; font-weight: bold; margin-right: 6px;
        vertical-align: middle;
    }
    .closing-cta {
        background: #1e1b4b; color: #ffffff;
        padding: 20px; border-radius: 6px;
        text-align: center; margin-top: 20px;
    }
    .closing-cta-title { font-size: 14px; font-weight: bold; margin-bottom: 4px; }
    .closing-cta-sub { font-size: 10px; color: #a5b4fc; margin-bottom: 10px; }
    .closing-cta-url { font-size: 12px; font-weight: bold; color: #818cf8; }

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

    // All scored categories for scorecard
    $allScoredCats = collect($scan->results)
        ->filter(fn($c) => isset($c['score']) && $c['score'] !== null);

    // Industry benchmark (calculated average across common websites)
    $benchmark = 62;

    // Impact explanations for categories
    $catImpacts = [
        'SSL & HTTPS' => 'Without proper SSL, visitor data (passwords, personal info) can be intercepted by attackers on the same network.',
        'Security Headers' => 'Missing security headers make your website vulnerable to clickjacking, XSS attacks, and data injection.',
        'DNS & Email Security' => 'Weak DNS/email security allows attackers to send emails pretending to be your domain (phishing).',
        'Performance & SEO' => 'Poor performance affects user experience and search engine ranking, reducing your website visibility.',
        'Content & CMS' => 'Outdated CMS or exposed content can reveal vulnerabilities that attackers actively scan for.',
        'Exposed Files' => 'Publicly accessible configuration files can expose database credentials and internal system details.',
        'TLS / Cipher' => 'Weak encryption ciphers allow attackers to decrypt data transmitted between your users and server.',
        'Robots & Sitemap' => 'Misconfigured robots.txt can expose sensitive areas of your website to search engines and crawlers.',
        'Cookie Security' => 'Insecure cookies can be stolen or manipulated, allowing attackers to hijack user sessions.',
        'OWASP Top 10' => 'These are the most critical web application security risks recognized worldwide.',
    ];

    // Build prioritized action list
    $actionItems = collect();
    foreach ($failures as $f) {
        $actionItems->push(['label' => $f['label'], 'cat' => $f['_cat'], 'urgency' => 'now', 'type' => 'Critical',
            'rec' => $f['recommendation'] ?? 'Review and fix this issue immediately.']);
    }
    foreach ($warnings->take(10) as $w) {
        $actionItems->push(['label' => $w['label'], 'cat' => $w['_cat'], 'urgency' => 'week', 'type' => 'Warning',
            'rec' => $w['recommendation'] ?? 'Review and address this issue.']);
    }
    $actionItems = $actionItems->take(15);

    // Risk matrix data
    $matrixCats = $allScoredCats->keys()->filter(fn($k) => !in_array($k, ['technology', 'owasp']))->values();
    $matrixData = [];
    foreach ($matrixCats as $key) {
        $cat = $scan->results[$key];
        $checks = collect($cat['checks'] ?? []);
        $matrixData[] = [
            'name' => $cat['category'],
            'fail' => $checks->where('status', 'fail')->count(),
            'warn' => $checks->where('status', 'warn')->count(),
            'pass' => $checks->where('status', 'pass')->count(),
        ];
    }
    // Sort: most issues first
    usort($matrixData, fn($a, $b) => ($b['fail'] * 10 + $b['warn']) <=> ($a['fail'] * 10 + $a['warn']));
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

{{-- ══════════════════════════════════════════════════════
     1. COVER PAGE (enhanced with grade color accent)
     ══════════════════════════════════════════════════════ --}}
<div class="cover-page">
    <div class="cover-top">
        <div class="cover-brand">WebCheck<span class="cover-brand-accent">App</span></div>
        <div class="cover-tagline">Security Report</div>
    </div>
    <div class="cover-grade-bar gbar-{{ $gc }}"></div>

    <div class="cover-middle">
        <div class="score-ring-outer ring-{{ $gc }}">
            <div class="score-ring-num">{{ $scan->score }}</div>
            <div class="score-ring-label">out of 100</div>
            <div class="score-ring-grade grade-{{ $gc }}">Grade {{ $scan->grade }}</div>
        </div>

        <div class="cover-host">{{ $scan->host }}</div>
        <div style="margin-bottom: 16px;">
            <span class="cover-tier-badge">{{ $scan->tierLabel() }}</span>
        </div>

        <div class="cover-divider"></div>

        <div class="cover-meta">
            Scanned: {{ $scan->completed_at->format('d M Y, H:i') }} UTC<br>
            Report generated: {{ now()->format('d M Y, H:i') }} UTC<br>
            Categories: {{ count($scan->results ?? []) }} &bull; Checks: {{ $allChecks->count() }}
        </div>

        <div class="cover-prepared">
            Prepared by <strong>WebCheckApp</strong> &mdash; Automated Website Security Scanner
        </div>

        <div class="cover-conf">Confidential</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     2. TABLE OF CONTENTS
     ══════════════════════════════════════════════════════ --}}
<div class="section-title" style="margin-top:0;">
    <span class="st-icon" style="background:#1e1b4b;">&#9776;</span> Table of Contents
</div>

<table class="toc-table">
    <tr><td class="toc-num">01</td><td class="toc-title">Score Overview &amp; Benchmark</td></tr>
    <tr><td class="toc-num">02</td><td class="toc-title">Executive Summary</td></tr>
    <tr><td class="toc-num">03</td><td class="toc-title">Prioritized Action Plan</td></tr>
    <tr><td class="toc-num">04</td><td class="toc-title">Risk Impact Matrix</td></tr>
    @if(isset($scan->results['owasp']) && !empty($scan->results['owasp']['checks']))
    <tr><td class="toc-num">05</td><td class="toc-title">OWASP Top 10 Analysis</td></tr>
    @endif
    @if($failures->count() > 0)
    <tr><td class="toc-num">06</td><td class="toc-title">Critical Issues ({{ $failures->count() }})</td></tr>
    @endif
    @if($warnings->count() > 0)
    <tr><td class="toc-num">07</td><td class="toc-title">Warnings ({{ $warnings->count() }})</td></tr>
    @endif
    <tr><td class="toc-num">08</td><td class="toc-title">Passed Checks ({{ $passes->count() }})</td></tr>
    <tr><td class="toc-num">09</td><td class="toc-title">Detected Technologies</td></tr>
    <tr><td class="toc-num">10</td><td class="toc-title">Category Details</td></tr>
    <tr><td class="toc-num">11</td><td class="toc-title">Next Steps &amp; Recommendations</td></tr>
</table>

<div class="page-break"></div>

{{-- ══════════════════════════════════════════════════════
     3. SCORE OVERVIEW + SCORECARD + BENCHMARK
     ══════════════════════════════════════════════════════ --}}
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
<div style="margin-bottom: 14px;">
    <div style="font-size:9px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px;">
        Check Results Distribution
    </div>
    <div class="donut-bar">
        @if($passes->count() > 0)<div class="donut-seg donut-seg-pass" style="width:{{ $passP }}%;"></div>@endif
        @if($warnings->count() > 0)<div class="donut-seg donut-seg-warn" style="width:{{ $warnP }}%;"></div>@endif
        @if($failures->count() > 0)<div class="donut-seg donut-seg-fail" style="width:{{ $failP }}%;"></div>@endif
    </div>
    <div class="donut-legend">
        <span class="legend-dot" style="background:#22c55e;"></span> Passed ({{ $passes->count() }})
        &nbsp;&nbsp;
        <span class="legend-dot" style="background:#eab308;"></span> Warnings ({{ $warnings->count() }})
        &nbsp;&nbsp;
        <span class="legend-dot" style="background:#ef4444;"></span> Critical ({{ $failures->count() }})
    </div>
</div>

{{-- Benchmark indicator --}}
<div style="margin-bottom: 16px;">
    <div style="font-size:9px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px;">
        Your Score vs. Industry Average
    </div>
    <table style="width:100%; border-collapse:collapse; margin-bottom:4px;">
        <tr>
            <td style="width:{{ $benchmark }}%; padding:0;">
                <div style="height:24px; background:#e5e7eb; border-radius:4px 0 0 4px; position:relative;">
                    <div style="position:absolute; right:0; top:-14px; font-size:8px; color:#6b7280;">Avg: {{ $benchmark }}</div>
                    <div style="position:absolute; right:-1px; top:0; width:2px; height:24px; background:#6b7280;"></div>
                </div>
            </td>
            <td style="width:{{ 100 - $benchmark }}%; padding:0;">
                <div style="height:24px; background:#f3f4f6; border-radius:0 4px 4px 0;"></div>
            </td>
        </tr>
    </table>
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:{{ $scan->score }}%; padding:0;">
                <div style="height:24px; background:{{ $scan->score >= $benchmark ? '#22c55e' : '#f97316' }}; border-radius:4px; position:relative;">
                    <div style="position:absolute; right:4px; top:4px; font-size:10px; font-weight:bold; color:#ffffff;">{{ $scan->score }}</div>
                </div>
            </td>
            <td style="padding:0;"></td>
        </tr>
    </table>
    <div style="font-size:9px; color:#4b5563; margin-top:4px;">
        @if($scan->score >= $benchmark)
            Your website scores <strong style="color:#16a34a;">{{ $scan->score - $benchmark }} points above</strong> the industry average.
        @else
            Your website scores <strong style="color:#dc2626;">{{ $benchmark - $scan->score }} points below</strong> the industry average.
        @endif
    </div>
</div>

{{-- Scorecard table --}}
<div style="font-size:9px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px;">
    Category Scorecard
</div>
<table class="scorecard">
    <tr>
        <th>Category</th>
        <th>Score</th>
        <th>Performance</th>
        <th style="text-align:center;">Status</th>
    </tr>
    @foreach($allScoredCats as $key => $cat)
    @php
        $s = $cat['score'];
        $bc = $s >= 75 ? 'bar-green' : ($s >= 50 ? 'bar-yellow' : ($s >= 25 ? 'bar-orange' : 'bar-red'));
        $sc = $s >= 75 ? 'sc-green' : ($s >= 50 ? 'sc-yellow' : ($s >= 25 ? 'sc-orange' : 'sc-red'));
        $statusColor = $s >= 75 ? '#22c55e' : ($s >= 50 ? '#eab308' : '#ef4444');
        $statusLabel = $s >= 75 ? 'Good' : ($s >= 50 ? 'Fair' : 'Poor');
    @endphp
    <tr>
        <td class="sc-name">{{ $cat['category'] }}</td>
        <td class="{{ $sc }}" style="font-weight:bold;">{{ $s }}/100</td>
        <td class="sc-gauge">
            <div class="sc-gauge-track">
                <div class="sc-gauge-fill {{ $bc }}" style="width:{{ $s }}%;"></div>
            </div>
        </td>
        <td style="text-align:center;">
            <span class="sc-status" style="background:{{ $statusColor }};"></span>
            <span style="font-size:8px; color:{{ $statusColor }}; font-weight:bold;">{{ $statusLabel }}</span>
        </td>
    </tr>
    @endforeach
</table>

{{-- ══ EXECUTIVE SUMMARY ══ --}}
@php
    $catCount = $allScoredCats->count();
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

{{-- ══════════════════════════════════════════════════════
     5. PRIORITIZED ACTION PLAN
     ══════════════════════════════════════════════════════ --}}
@if($actionItems->isNotEmpty())
<div class="section-title" style="margin-top:0;">
    <span class="st-icon" style="background:#059669;">&#9654;</span> Prioritized Action Plan
</div>
<div style="font-size: 9.5px; color: #4b5563; margin-bottom: 10px;">
    Address these items in order of priority to maximize your security improvement.
</div>

<table class="action-table">
    <tr>
        <th style="width:28px;">#</th>
        <th>Issue</th>
        <th style="width:65px;">Category</th>
        <th style="width:70px;">Urgency</th>
        <th>Recommended Action</th>
    </tr>
    @foreach($actionItems as $idx => $action)
    <tr class="no-break">
        <td><div class="action-num">{{ $idx + 1 }}</div></td>
        <td style="font-weight:bold; color:#1e1b4b;">{{ $action['label'] }}</td>
        <td><span class="issue-badge {{ $action['type'] === 'Critical' ? 'badge-fail' : 'badge-warn' }}">{{ $action['cat'] }}</span></td>
        <td>
            @if($action['urgency'] === 'now')
            <span class="urgency-now">Immediately</span>
            @elseif($action['urgency'] === 'week')
            <span class="urgency-week">This Week</span>
            @else
            <span class="urgency-month">This Month</span>
            @endif
        </td>
        <td style="font-size:9px; color:#4b5563;">{{ $action['rec'] }}</td>
    </tr>
    @endforeach
</table>
@endif

{{-- ══════════════════════════════════════════════════════
     6. RISK IMPACT MATRIX
     ══════════════════════════════════════════════════════ --}}
<div class="section-title">
    <span class="st-icon" style="background:#be185d;">&#9632;</span> Risk Impact Matrix
</div>
<div style="font-size: 9.5px; color: #4b5563; margin-bottom: 10px;">
    Overview of findings per category, showing the distribution of critical issues, warnings, and passed checks.
</div>

<table class="risk-matrix">
    <tr>
        <th>Category</th>
        <th style="color:#b91c1c;">Critical</th>
        <th style="color:#92400e;">Warnings</th>
        <th style="color:#166534;">Passed</th>
        <th>Score</th>
    </tr>
    @foreach($matrixData as $row)
    <tr>
        <td>{{ $row['name'] }}</td>
        <td>
            @if($row['fail'] > 0)
            <span class="rm-count rm-red">{{ $row['fail'] }}</span>
            @else
            <span class="rm-empty">&mdash;</span>
            @endif
        </td>
        <td>
            @if($row['warn'] > 0)
            <span class="rm-count rm-yellow">{{ $row['warn'] }}</span>
            @else
            <span class="rm-empty">&mdash;</span>
            @endif
        </td>
        <td>
            @if($row['pass'] > 0)
            <span class="rm-count rm-green">{{ $row['pass'] }}</span>
            @else
            <span class="rm-empty">&mdash;</span>
            @endif
        </td>
        <td>
            @php
                $catKey = collect($scan->results)->filter(fn($c) => ($c['category'] ?? '') === $row['name'])->keys()->first();
                $catScore = $catKey ? $scan->results[$catKey]['score'] : null;
                $csc = $catScore !== null ? ($catScore >= 75 ? 'sc-green' : ($catScore >= 50 ? 'sc-yellow' : 'sc-red')) : '';
            @endphp
            @if($catScore !== null)
            <span class="{{ $csc }}" style="font-weight:bold;">{{ $catScore }}</span>
            @else
            <span class="rm-empty">&mdash;</span>
            @endif
        </td>
    </tr>
    @endforeach
</table>

<div class="page-break"></div>

{{-- ══════════════════════════════════════════════════════
     OWASP TOP 10
     ══════════════════════════════════════════════════════ --}}
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

{{-- ══ CRITICAL ISSUES (with What This Means) ══ --}}
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
    {{-- What This Means --}}
    @if(isset($catImpacts[$check['_cat']]))
    <div class="wtm-box">
        <div class="wtm-label">What this means for your visitors</div>
        {{ $catImpacts[$check['_cat']] }}
    </div>
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

<div class="page-break"></div>

{{-- ══════════════════════════════════════════════════════
     8. PROFESSIONAL CLOSING PAGE
     ══════════════════════════════════════════════════════ --}}
<div class="section-title" style="margin-top:0;">
    <span class="st-icon" style="background:#6366f1;">&#9654;</span> Next Steps &amp; Recommendations
</div>

<div class="closing-box">
    <div class="closing-box-title">Recommended Next Steps</div>
    <div class="closing-step">
        <span class="closing-step-num">1</span>
        <strong>Address Critical Issues First</strong> &mdash;
        @if($failures->count() > 0)
        You have {{ $failures->count() }} critical {{ $failures->count() === 1 ? 'issue' : 'issues' }} that {{ $failures->count() === 1 ? 'requires' : 'require' }} immediate attention. Start with the items marked "Immediately" in the Action Plan.
        @else
        No critical issues were found. Focus on resolving the warnings to further improve your score.
        @endif
    </div>
    <div class="closing-step">
        <span class="closing-step-num">2</span>
        <strong>Review Warnings</strong> &mdash;
        After resolving critical issues, address the {{ $warnings->count() }} {{ $warnings->count() === 1 ? 'warning' : 'warnings' }} to strengthen your overall security posture.
    </div>
    <div class="closing-step">
        <span class="closing-step-num">3</span>
        <strong>Re-scan Your Website</strong> &mdash;
        After implementing fixes, run a new scan to verify improvements and ensure no new issues were introduced.
    </div>
    <div class="closing-step">
        <span class="closing-step-num">4</span>
        <strong>Schedule Regular Scans</strong> &mdash;
        Security is not a one-time effort. We recommend scanning your website at least once per month to catch new vulnerabilities.
    </div>
</div>

<div class="closing-box">
    <div class="closing-box-title">Report Validity</div>
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="padding:3px 0; font-size:10px; color:#6b7280; width:140px;">Scan performed:</td>
            <td style="padding:3px 0; font-size:10px; font-weight:bold;">{{ $scan->completed_at->format('d M Y, H:i') }} UTC</td>
        </tr>
        <tr>
            <td style="padding:3px 0; font-size:10px; color:#6b7280;">Report generated:</td>
            <td style="padding:3px 0; font-size:10px; font-weight:bold;">{{ now()->format('d M Y, H:i') }} UTC</td>
        </tr>
        <tr>
            <td style="padding:3px 0; font-size:10px; color:#6b7280;">Valid until:</td>
            <td style="padding:3px 0; font-size:10px; font-weight:bold;">{{ $scan->completed_at->addDays(30)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td style="padding:3px 0; font-size:10px; color:#6b7280;">Scan type:</td>
            <td style="padding:3px 0; font-size:10px; font-weight:bold;">{{ $scan->tierLabel() }}</td>
        </tr>
    </table>
    <div style="margin-top:8px; font-size:9px; color:#9ca3af;">
        This report reflects the state of the website at the time of scanning. Security configurations may change over time.
        We recommend re-scanning after 30 days or after significant changes to your website.
    </div>
</div>

<div class="closing-box" style="text-align:center; background:#f0fdf4; border-color:#bbf7d0;">
    <div style="font-size:9px; color:#6b7280; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Your Security Score</div>
    <div style="font-size:40px; font-weight:bold;" class="grade-{{ $gc }}">{{ $scan->score }}/100</div>
    <div style="font-size:12px; font-weight:bold; margin-top:2px;" class="grade-{{ $gc }}">Grade {{ $scan->grade }}</div>
    <div style="font-size:9px; color:#6b7280; margin-top:6px;">
        @if($scan->score >= 85)
        Excellent! Your website meets high security standards.
        @elseif($scan->score >= 65)
        Good foundation. Address the identified issues to reach an A-grade.
        @elseif($scan->score >= 40)
        Needs improvement. Follow the action plan to significantly improve your security.
        @else
        Urgent action required. Prioritize the critical issues immediately.
        @endif
    </div>
</div>

<div class="closing-cta">
    <div class="closing-cta-title">Need Professional Help?</div>
    <div class="closing-cta-sub">
        Our security experts can help you fix every issue in this report.<br>
        Manual penetration testing &bull; Code reviews &bull; Compliance audits
    </div>
    <div class="closing-cta-url">budgetpixels.nl</div>
</div>

<div style="margin-top:16px; text-align:center; font-size:8px; color:#c9c9c9;">
    This report was generated by WebCheckApp (webcheckapp.com). All information is for informational purposes only<br>
    and does not constitute professional security advice. Results are based on automated checks of publicly accessible information.
</div>

</body>
</html>
