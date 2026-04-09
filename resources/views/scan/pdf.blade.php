<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page {
        margin: 0;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10.5px;
        color: #1f2937;
        background: #ffffff;
        line-height: 1.55;
    }

    /* ── Running footer with page numbers ── */
    .page-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 36px;
        padding: 0 40px;
        font-size: 8px;
        color: #9ca3af;
        border-top: 1px solid #e5e7eb;
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .page-footer .footer-left { float: left; }
    .page-footer .footer-right { float: right; }
    .page-footer .pagenum::before { content: counter(page); }
    .page-footer .pagetotal::before { content: counter(pages); }

    /* ── Cover page ── */
    .cover {
        width: 100%;
        height: 100%;
        min-height: 980px;
        background: linear-gradient(160deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
        color: #ffffff;
        text-align: center;
        padding: 80px 50px 40px;
        page-break-after: always;
        position: relative;
        overflow: hidden;
    }
    .cover-decoration {
        position: absolute;
        top: -120px;
        right: -120px;
        width: 400px;
        height: 400px;
        border-radius: 50%;
        background: rgba(255,255,255,0.03);
    }
    .cover-decoration-2 {
        position: absolute;
        bottom: -80px;
        left: -80px;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: rgba(255,255,255,0.02);
    }
    .cover-brand {
        font-size: 28px;
        font-weight: bold;
        letter-spacing: -0.5px;
        margin-bottom: 4px;
    }
    .cover-brand span { color: #a5b4fc; }
    .cover-tagline {
        font-size: 11px;
        color: #c7d2fe;
        letter-spacing: 3px;
        text-transform: uppercase;
        margin-bottom: 60px;
    }
    .cover-score-ring {
        margin: 0 auto 30px;
    }
    .cover-host {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 6px;
        color: #ffffff;
    }
    .cover-tier {
        display: inline-block;
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.2);
        padding: 4px 18px;
        border-radius: 20px;
        font-size: 10px;
        color: #c7d2fe;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 40px;
    }
    .cover-meta {
        font-size: 9.5px;
        color: #a5b4fc;
        line-height: 2;
    }
    .cover-bottom {
        position: absolute;
        bottom: 50px;
        left: 0;
        right: 0;
        text-align: center;
    }
    .cover-confidential {
        font-size: 8px;
        color: rgba(255,255,255,0.3);
        text-transform: uppercase;
        letter-spacing: 4px;
        margin-top: 20px;
    }

    /* ── Page padding (all pages except cover) ── */
    .page { padding: 40px 40px 50px; }

    /* ── Section titles ── */
    .section-title {
        font-size: 14px;
        font-weight: bold;
        color: #1e1b4b;
        margin-bottom: 14px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
        margin-top: 28px;
        display: flex;
        align-items: center;
    }
    .section-title:first-child { margin-top: 0; }
    .section-icon {
        display: inline-block;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        text-align: center;
        line-height: 24px;
        font-size: 12px;
        margin-right: 10px;
        color: #ffffff;
        flex-shrink: 0;
    }

    /* ── Score overview page ── */
    .overview-grid {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    .overview-left { flex: 1; }
    .overview-right { flex: 1; }

    .stat-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 10px;
    }
    .stat-card-title {
        font-size: 9px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 6px;
    }

    /* ── Summary pills ── */
    .stat-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        margin-right: 4px;
    }
    .pill-fail { background: #fee2e2; color: #b91c1c; }
    .pill-warn { background: #fef9c3; color: #92400e; }
    .pill-pass { background: #dcfce7; color: #166534; }

    /* ── Category score bars ── */
    .cat-bar-row {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }
    .cat-bar-label {
        width: 110px;
        font-size: 10px;
        color: #374151;
        flex-shrink: 0;
    }
    .cat-bar-track {
        flex: 1;
        height: 10px;
        background: #e5e7eb;
        border-radius: 5px;
        overflow: hidden;
    }
    .cat-bar-fill {
        height: 100%;
        border-radius: 5px;
        transition: width 0.3s;
    }
    .cat-bar-score {
        width: 40px;
        text-align: right;
        font-size: 10px;
        font-weight: bold;
        flex-shrink: 0;
        padding-left: 8px;
    }

    .bar-green  { background: #22c55e; }
    .bar-yellow { background: #eab308; }
    .bar-orange { background: #f97316; }
    .bar-red    { background: #ef4444; }

    .score-green  { color: #16a34a; }
    .score-yellow { color: #ca8a04; }
    .score-orange { color: #ea580c; }
    .score-red    { color: #dc2626; }

    /* ── Executive summary ── */
    .exec-summary {
        font-size: 10.5px;
        color: #374151;
        line-height: 1.7;
    }
    .exec-summary p { margin-bottom: 8px; }

    .strength-list {
        margin: 6px 0 10px;
    }
    .strength-item {
        padding: 5px 10px;
        margin-bottom: 4px;
        border-radius: 4px;
        font-size: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .strength-good { background: #f0fdf4; color: #166534; }
    .strength-med  { background: #fffbeb; color: #92400e; }
    .strength-bad  { background: #fef2f2; color: #991b1b; }

    /* ── OWASP section ── */
    .owasp-item {
        padding: 10px 12px;
        margin-bottom: 6px;
        border-radius: 6px;
        border-left: 4px solid;
    }
    .owasp-pass { background: #f0fdf4; border-color: #22c55e; }
    .owasp-warn { background: #fffbeb; border-color: #f59e0b; }
    .owasp-fail { background: #fef2f2; border-color: #ef4444; }

    .owasp-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3px;
    }
    .owasp-label { font-weight: bold; font-size: 10.5px; color: #1f2937; }
    .owasp-risk {
        font-size: 8.5px;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .risk-critical { background: #fee2e2; color: #b91c1c; }
    .risk-high     { background: #ffedd5; color: #c2410c; }
    .risk-medium   { background: #fef9c3; color: #a16207; }
    .risk-low      { background: #dcfce7; color: #166534; }

    .owasp-desc { font-size: 9.5px; color: #4b5563; }
    .owasp-fix  { font-size: 9px; color: #4338ca; margin-top: 4px; }

    /* ── Issues ── */
    .issue-item {
        padding: 10px 12px;
        margin-bottom: 6px;
        border-radius: 6px;
        border-left: 4px solid;
    }
    .issue-fail { background: #fef2f2; border-color: #ef4444; }
    .issue-warn { background: #fffbeb; border-color: #f59e0b; }

    .issue-label { font-weight: bold; font-size: 10.5px; color: #1f2937; margin-bottom: 2px; }
    .issue-desc  { font-size: 9.5px; color: #4b5563; }
    .issue-fix   { font-size: 9px; color: #4338ca; margin-top: 4px; }

    .issue-badge {
        display: inline-block;
        font-size: 8px;
        padding: 1px 6px;
        border-radius: 10px;
        margin-left: 6px;
        font-weight: normal;
        vertical-align: middle;
    }
    .badge-fail { background: #fee2e2; color: #b91c1c; }
    .badge-warn { background: #fef9c3; color: #92400e; }

    /* ── Passed checks (2-column) ── */
    .pass-grid {
        display: flex;
        flex-wrap: wrap;
    }
    .pass-col {
        width: 50%;
        padding-right: 8px;
    }
    .pass-item {
        padding: 4px 0;
        font-size: 9.5px;
        color: #374151;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: flex-start;
        gap: 5px;
    }
    .pass-tick {
        color: #16a34a;
        font-weight: bold;
        flex-shrink: 0;
    }
    .pass-cat {
        color: #9ca3af;
        font-size: 8px;
        margin-left: auto;
        flex-shrink: 0;
    }

    /* ── Technology ── */
    .tech-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
    }
    .tech-group {
        margin-bottom: 6px;
        display: flex;
        align-items: flex-start;
    }
    .tech-type-label {
        font-size: 9px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        width: 95px;
        flex-shrink: 0;
        padding-top: 2px;
    }
    .tech-badges { flex: 1; }
    .tech-badge {
        display: inline-block;
        background: #e0e7ff;
        color: #3730a3;
        font-size: 9px;
        padding: 2px 8px;
        border-radius: 10px;
        margin-right: 4px;
        margin-bottom: 3px;
    }

    /* ── Category detail ── */
    .cat-section { margin-bottom: 16px; page-break-inside: avoid; }
    .cat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f1f5f9;
        padding: 8px 12px;
        border-radius: 6px;
        margin-bottom: 6px;
    }
    .cat-header-name { font-weight: bold; font-size: 11px; color: #1e1b4b; }
    .cat-header-score { font-weight: bold; font-size: 11px; }

    /* ── Report footer ── */
    .report-footer {
        margin-top: 30px;
        padding: 20px 0;
        border-top: 2px solid #e2e8f0;
        text-align: center;
        font-size: 9px;
        color: #9ca3af;
        line-height: 1.8;
    }
    .report-footer strong { color: #6b7280; }

    .page-break { page-break-after: always; }
    .no-break   { page-break-inside: avoid; }
</style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════════
     DATA PREPARATION
     ══════════════════════════════════════════════════════════ --}}
@php
    $allChecks = collect($scan->results)
        ->filter(fn($c) => isset($c['score']) && $c['score'] !== null)
        ->flatMap(fn($c) => collect($c['checks'] ?? [])->map(fn($ch) => array_merge($ch, ['_cat' => $c['category']])));
    $failures  = $allChecks->where('status', 'fail');
    $warnings  = $allChecks->where('status', 'warn');
    $passes    = $allChecks->where('status', 'pass');
    $totalChecks = $allChecks->count();

    $gradeClass = $scan->score >= 90 ? 'a'
        : ($scan->score >= 75 ? 'b'
        : ($scan->score >= 60 ? 'c'
        : ($scan->score >= 40 ? 'd' : 'f')));

    $gradeColors = [
        'a' => ['ring' => '#10b981', 'bg' => '#ecfdf5'],
        'b' => ['ring' => '#22c55e', 'bg' => '#f0fdf4'],
        'c' => ['ring' => '#eab308', 'bg' => '#fefce8'],
        'd' => ['ring' => '#f97316', 'bg' => '#fff7ed'],
        'f' => ['ring' => '#ef4444', 'bg' => '#fef2f2'],
    ];
    $ringColor = $gradeColors[$gradeClass]['ring'];

    $weightedKeys = ['ssl', 'headers', 'dns', 'performance', 'content', 'exposed_files'];
    $weightedCats = collect($weightedKeys)
        ->filter(fn($k) => isset($scan->results[$k]) && $scan->results[$k]['score'] !== null)
        ->map(fn($k) => $scan->results[$k]);

    // Score ring SVG calculations
    $radius = 80;
    $circumference = 2 * M_PI * $radius;
    $scoreOffset = $circumference - ($circumference * $scan->score / 100);

    // Donut chart for pass/warn/fail
    $total = max($totalChecks, 1);
    $passPercent = $passes->count() / $total * 100;
    $warnPercent = $warnings->count() / $total * 100;
    $failPercent = $failures->count() / $total * 100;

    // Radar chart calculations
    $radarCats = $weightedCats->values();
    $radarCount = $radarCats->count();
    $radarR = 70; // radius of the chart
@endphp

{{-- ══ RUNNING FOOTER ══ --}}
<div class="page-footer">
    <span class="footer-left">WebCheckApp Security Report &mdash; {{ $scan->host }}</span>
    <span class="footer-right">Page <span class="pagenum"></span> of <span class="pagetotal"></span></span>
</div>

{{-- ══════════════════════════════════════════════════════════
     PAGE 1: COVER
     ══════════════════════════════════════════════════════════ --}}
<div class="cover">
    <div class="cover-decoration"></div>
    <div class="cover-decoration-2"></div>

    <div class="cover-brand">WebCheck<span>App</span></div>
    <div class="cover-tagline">Security Report</div>

    {{-- Large score ring --}}
    <div class="cover-score-ring">
        <svg width="200" height="200" viewBox="0 0 200 200">
            {{-- Background circle --}}
            <circle cx="100" cy="100" r="{{ $radius }}" fill="none"
                    stroke="rgba(255,255,255,0.1)" stroke-width="12"/>
            {{-- Score arc --}}
            <circle cx="100" cy="100" r="{{ $radius }}" fill="none"
                    stroke="{{ $ringColor }}" stroke-width="12"
                    stroke-dasharray="{{ $circumference }}"
                    stroke-dashoffset="{{ $scoreOffset }}"
                    stroke-linecap="round"
                    transform="rotate(-90 100 100)"/>
            {{-- Score text --}}
            <text x="100" y="90" text-anchor="middle" fill="#ffffff"
                  font-size="42" font-weight="bold" font-family="DejaVu Sans">{{ $scan->score }}</text>
            <text x="100" y="108" text-anchor="middle" fill="rgba(255,255,255,0.5)"
                  font-size="12" font-family="DejaVu Sans">out of 100</text>
            {{-- Grade --}}
            <text x="100" y="135" text-anchor="middle" fill="{{ $ringColor }}"
                  font-size="22" font-weight="bold" font-family="DejaVu Sans">Grade {{ $scan->grade }}</text>
        </svg>
    </div>

    <div class="cover-host">{{ $scan->host }}</div>
    <div class="cover-tier">{{ $scan->tierLabel() }}</div>

    <div class="cover-bottom">
        <div class="cover-meta">
            Scanned: {{ $scan->completed_at->format('d M Y, H:i') }} UTC<br>
            Report generated: {{ now()->format('d M Y, H:i') }} UTC<br>
            Categories: {{ count($scan->results ?? []) }} | Checks: {{ $totalChecks }}
        </div>
        <div class="cover-confidential">Confidential</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     PAGE 2: SCORE OVERVIEW WITH CHARTS
     ══════════════════════════════════════════════════════════ --}}
<div class="page">

    <div class="section-title" style="margin-top:0;">
        <span class="section-icon" style="background:#4338ca;">&#9733;</span>
        Score Overview
    </div>

    <div class="overview-grid">

        {{-- LEFT: Donut chart for pass/warn/fail --}}
        <div class="overview-left">
            <div class="stat-card" style="text-align:center;">
                <div class="stat-card-title">Check Results Distribution</div>
                @php
                    $donutR = 55;
                    $donutC = 2 * M_PI * $donutR;
                    // Calculate offsets for each segment
                    $passLen = $donutC * $passPercent / 100;
                    $warnLen = $donutC * $warnPercent / 100;
                    $failLen = $donutC * $failPercent / 100;
                    $passStart = 0;
                    $warnStart = $passLen;
                    $failStart = $passLen + $warnLen;
                @endphp
                <svg width="160" height="160" viewBox="0 0 160 160" style="margin: 6px auto;">
                    {{-- Background --}}
                    <circle cx="80" cy="80" r="{{ $donutR }}" fill="none" stroke="#f3f4f6" stroke-width="20"/>

                    {{-- Pass segment --}}
                    @if($passes->count() > 0)
                    <circle cx="80" cy="80" r="{{ $donutR }}" fill="none"
                            stroke="#22c55e" stroke-width="20"
                            stroke-dasharray="{{ $passLen }} {{ $donutC - $passLen }}"
                            stroke-dashoffset="0"
                            transform="rotate(-90 80 80)"/>
                    @endif

                    {{-- Warn segment --}}
                    @if($warnings->count() > 0)
                    <circle cx="80" cy="80" r="{{ $donutR }}" fill="none"
                            stroke="#eab308" stroke-width="20"
                            stroke-dasharray="{{ $warnLen }} {{ $donutC - $warnLen }}"
                            stroke-dashoffset="{{ -$passLen }}"
                            transform="rotate(-90 80 80)"/>
                    @endif

                    {{-- Fail segment --}}
                    @if($failures->count() > 0)
                    <circle cx="80" cy="80" r="{{ $donutR }}" fill="none"
                            stroke="#ef4444" stroke-width="20"
                            stroke-dasharray="{{ $failLen }} {{ $donutC - $failLen }}"
                            stroke-dashoffset="{{ -($passLen + $warnLen) }}"
                            transform="rotate(-90 80 80)"/>
                    @endif

                    {{-- Center text --}}
                    <text x="80" y="76" text-anchor="middle" fill="#1e1b4b"
                          font-size="20" font-weight="bold" font-family="DejaVu Sans">{{ $totalChecks }}</text>
                    <text x="80" y="92" text-anchor="middle" fill="#6b7280"
                          font-size="9" font-family="DejaVu Sans">checks</text>
                </svg>

                <div style="display: flex; justify-content: center; gap: 14px; margin-top: 6px;">
                    <span class="stat-pill pill-pass">{{ $passes->count() }} passed</span>
                    <span class="stat-pill pill-warn">{{ $warnings->count() }} warnings</span>
                    <span class="stat-pill pill-fail">{{ $failures->count() }} critical</span>
                </div>
            </div>
        </div>

        {{-- RIGHT: Radar chart --}}
        <div class="overview-right">
            <div class="stat-card" style="text-align:center;">
                <div class="stat-card-title">Category Performance</div>
                @if($radarCount >= 3)
                @php
                    $cx = 90; $cy = 80;
                    // Calculate polygon points for background grid
                    function radarPoint($cx, $cy, $r, $index, $total, $score = 100) {
                        $angle = (2 * M_PI * $index / $total) - (M_PI / 2);
                        $x = $cx + ($r * $score / 100) * cos($angle);
                        $y = $cy + ($r * $score / 100) * sin($angle);
                        return "$x,$y";
                    }

                    // Grid polygons at 25%, 50%, 75%, 100%
                    $gridLevels = [25, 50, 75, 100];
                    $gridPolygons = [];
                    foreach ($gridLevels as $level) {
                        $points = [];
                        for ($i = 0; $i < $radarCount; $i++) {
                            $points[] = radarPoint($cx, $cy, $radarR, $i, $radarCount, $level);
                        }
                        $gridPolygons[$level] = implode(' ', $points);
                    }

                    // Data polygon
                    $dataPoints = [];
                    foreach ($radarCats as $i => $cat) {
                        $dataPoints[] = radarPoint($cx, $cy, $radarR, $i, $radarCount, $cat['score']);
                    }
                    $dataPolygon = implode(' ', $dataPoints);

                    // Axis lines & labels
                    $axes = [];
                    foreach ($radarCats as $i => $cat) {
                        $endPoint = radarPoint($cx, $cy, $radarR, $i, $radarCount, 100);
                        $labelPoint = radarPoint($cx, $cy, $radarR + 16, $i, $radarCount, 100);
                        $parts = explode(',', $labelPoint);
                        $axes[] = [
                            'end' => $endPoint,
                            'lx' => (float)$parts[0],
                            'ly' => (float)$parts[1],
                            'name' => $cat['category'],
                            'score' => $cat['score'],
                        ];
                    }
                @endphp
                <svg width="180" height="180" viewBox="0 0 180 180" style="margin: 0 auto;">
                    {{-- Grid polygons --}}
                    @foreach($gridPolygons as $level => $points)
                    <polygon points="{{ $points }}" fill="none"
                             stroke="#e5e7eb" stroke-width="{{ $level === 100 ? 1 : 0.5 }}"/>
                    @endforeach

                    {{-- Axis lines --}}
                    @foreach($axes as $axis)
                    <line x1="{{ $cx }}" y1="{{ $cy }}"
                          x2="{{ explode(',', $axis['end'])[0] }}"
                          y2="{{ explode(',', $axis['end'])[1] }}"
                          stroke="#e5e7eb" stroke-width="0.5"/>
                    @endforeach

                    {{-- Data polygon --}}
                    <polygon points="{{ $dataPolygon }}"
                             fill="rgba(99,102,241,0.2)" stroke="#6366f1" stroke-width="2"/>

                    {{-- Data points --}}
                    @foreach($radarCats as $i => $cat)
                    @php $pt = explode(',', radarPoint($cx, $cy, $radarR, $i, $radarCount, $cat['score'])); @endphp
                    <circle cx="{{ $pt[0] }}" cy="{{ $pt[1] }}" r="3"
                            fill="#6366f1" stroke="#ffffff" stroke-width="1"/>
                    @endforeach

                    {{-- Labels --}}
                    @foreach($axes as $axis)
                    @php
                        $anchor = 'middle';
                        if ($axis['lx'] < $cx - 10) $anchor = 'end';
                        elseif ($axis['lx'] > $cx + 10) $anchor = 'start';
                    @endphp
                    <text x="{{ $axis['lx'] }}" y="{{ $axis['ly'] }}"
                          text-anchor="{{ $anchor }}" fill="#374151"
                          font-size="7" font-family="DejaVu Sans">{{ $axis['name'] }}</text>
                    @endforeach
                </svg>
                @endif
            </div>
        </div>
    </div>

    {{-- Category score bars --}}
    <div class="stat-card">
        <div class="stat-card-title">Category Scores</div>
        @foreach($weightedCats as $cat)
        @php
            $s = $cat['score'];
            $bc = $s >= 75 ? 'bar-green' : ($s >= 50 ? 'bar-yellow' : ($s >= 25 ? 'bar-orange' : 'bar-red'));
            $sc = $s >= 75 ? 'score-green' : ($s >= 50 ? 'score-yellow' : ($s >= 25 ? 'score-orange' : 'score-red'));
        @endphp
        <div class="cat-bar-row">
            <div class="cat-bar-label">{{ $cat['category'] }}</div>
            <div class="cat-bar-track">
                <div class="cat-bar-fill {{ $bc }}" style="width: {{ $s }}%;"></div>
            </div>
            <div class="cat-bar-score {{ $sc }}">{{ $s }}/100</div>
        </div>
        @endforeach
    </div>

    {{-- ══ EXECUTIVE SUMMARY ══ --}}
    @php
        $catCount = collect($scan->results)->filter(fn($c) => isset($c['score']) && $c['score'] !== null)->count();
        $strongAreas = collect($scan->results)
            ->filter(fn($c) => isset($c['score']) && $c['score'] >= 80)
            ->pluck('category')->take(4);
        $weakAreas = collect($scan->results)
            ->filter(fn($c) => isset($c['score']) && $c['score'] !== null && $c['score'] < 60)
            ->sortBy('score')->pluck('category')->take(4);
        $mediumAreas = collect($scan->results)
            ->filter(fn($c) => isset($c['score']) && $c['score'] >= 60 && $c['score'] < 80)
            ->pluck('category')->take(4);
    @endphp

    <div class="section-title">
        <span class="section-icon" style="background:#6366f1;">&#9998;</span>
        Executive Summary
    </div>

    <div class="exec-summary">
        <p>
            We performed a comprehensive security analysis of <strong>{{ $scan->host }}</strong> across {{ $catCount }} categories.
            The website received an overall score of <strong>{{ $scan->score }}/100</strong> (grade <strong>{{ $scan->grade }}</strong>),
            with {{ $failures->count() }} critical {{ $failures->count() === 1 ? 'issue' : 'issues' }},
            {{ $warnings->count() }} {{ $warnings->count() === 1 ? 'warning' : 'warnings' }},
            and {{ $passes->count() }} passed {{ $passes->count() === 1 ? 'check' : 'checks' }}.
        </p>

        @if($scan->score >= 85)
        <p>
            <strong>Overall assessment:</strong> {{ $scan->host }} demonstrates a strong security posture. The website follows most security best practices and is well-configured. Minor improvements are possible but no urgent issues were found.
        </p>
        @elseif($scan->score >= 65)
        <p>
            <strong>Overall assessment:</strong> {{ $scan->host }} has a reasonable security foundation but there is room for improvement. Several issues were identified that could expose the website or its users to unnecessary risk. We recommend addressing the critical issues first, followed by the warnings.
        </p>
        @elseif($scan->score >= 40)
        <p>
            <strong>Overall assessment:</strong> {{ $scan->host }} has significant security gaps that should be addressed as soon as possible. The current configuration leaves the website vulnerable to common attacks. We strongly recommend reviewing the critical issues listed below.
        </p>
        @else
        <p>
            <strong>Overall assessment:</strong> {{ $scan->host }} has serious security deficiencies across multiple areas. The website is at high risk of exploitation. Immediate action is required to protect the website and its users.
        </p>
        @endif

        @if($strongAreas->isNotEmpty())
        <div class="strength-list">
            @foreach($strongAreas as $area)
            <div class="strength-item strength-good">&#10003; {{ $area }}</div>
            @endforeach
        </div>
        @endif

        @if($mediumAreas->isNotEmpty())
        <div class="strength-list">
            @foreach($mediumAreas as $area)
            <div class="strength-item strength-med">&#9888; {{ $area }}</div>
            @endforeach
        </div>
        @endif

        @if($weakAreas->isNotEmpty())
        <div class="strength-list">
            @foreach($weakAreas as $area)
            <div class="strength-item strength-bad">&#10007; {{ $area }}</div>
            @endforeach
        </div>
        @endif
    </div>

</div>

<div class="page-break"></div>

{{-- ══════════════════════════════════════════════════════════
     PAGE 3+: DETAILED FINDINGS
     ══════════════════════════════════════════════════════════ --}}
<div class="page">

    {{-- ══ OWASP TOP 10 ══ --}}
    @if(isset($scan->results['owasp']) && !empty($scan->results['owasp']['checks']))
    <div class="section-title" style="margin-top:0;">
        <span class="section-icon" style="background:#7c3aed;">&#9888;</span>
        OWASP Top 10 Analysis
        <span style="margin-left: auto; font-size: 11px; color: #7c3aed;">Score: {{ $scan->results['owasp']['score'] ?? 0 }}/100</span>
    </div>
    <div style="font-size: 10px; color: #4b5563; margin-bottom: 12px; line-height: 1.6;">
        The OWASP Top 10 is the globally recognized standard for web application security risks.
        Below is how {{ $scan->host }} scores against each of the ten categories.
    </div>

    @foreach($scan->results['owasp']['checks'] as $check)
    @php
        $riskClass = match($check['risk'] ?? 'Medium') {
            'Critical' => 'risk-critical',
            'High'     => 'risk-high',
            'Medium'   => 'risk-medium',
            'Low'      => 'risk-low',
            default    => 'risk-medium',
        };
        $itemClass = match($check['status']) {
            'pass' => 'owasp-pass',
            'warn' => 'owasp-warn',
            default => 'owasp-fail',
        };
        $icon = match($check['status']) {
            'pass' => '&#10003;',
            'warn' => '&#9888;',
            default => '&#10007;',
        };
    @endphp
    <div class="owasp-item {{ $itemClass }} no-break">
        <div class="owasp-header">
            <span class="owasp-label">{!! $icon !!} {{ $check['label'] }}</span>
            <span class="owasp-risk {{ $riskClass }}">{{ $check['risk'] ?? '' }} Risk</span>
        </div>
        <div class="owasp-desc">{{ $check['description'] }}</div>
        @if(!empty($check['recommendation']))
        <div class="owasp-fix">&#128161; {{ $check['recommendation'] }}</div>
        @endif
    </div>
    @endforeach
    @endif

    {{-- ══ CRITICAL ISSUES ══ --}}
    @if($failures->count() > 0)
    <div class="section-title">
        <span class="section-icon" style="background:#dc2626;">&#10007;</span>
        Critical Issues ({{ $failures->count() }})
    </div>
    <div style="font-size: 10px; color: #4b5563; margin-bottom: 10px;">
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
        <span class="section-icon" style="background:#f59e0b;">&#9888;</span>
        Warnings ({{ $warnings->count() }})
    </div>
    <div style="font-size: 10px; color: #4b5563; margin-bottom: 10px;">
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
        <span class="section-icon" style="background:#16a34a;">&#10003;</span>
        Passed Checks ({{ $passes->count() }})
    </div>
    <div style="font-size: 10px; color: #4b5563; margin-bottom: 10px;">
        These checks were all successfully validated. Keep up the good work.
    </div>
    <div class="pass-grid">
        @php $half = ceil($passes->count() / 2); @endphp
        <div class="pass-col">
            @foreach($passes->take($half) as $check)
            <div class="pass-item">
                <span class="pass-tick">&#10003;</span>
                <span>{{ $check['label'] }}</span>
                <span class="pass-cat">{{ $check['_cat'] }}</span>
            </div>
            @endforeach
        </div>
        <div class="pass-col">
            @foreach($passes->slice($half) as $check)
            <div class="pass-item">
                <span class="pass-tick">&#10003;</span>
                <span>{{ $check['label'] }}</span>
                <span class="pass-cat">{{ $check['_cat'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="page-break"></div>

    {{-- ══ TECHNOLOGY STACK ══ --}}
    @if(!empty($scan->results['technology']['technologies']))
    <div class="section-title" style="margin-top:0;">
        <span class="section-icon" style="background:#0891b2;">&#9881;</span>
        Detected Technologies
    </div>
    <div style="font-size: 10px; color: #4b5563; margin-bottom: 10px;">
        The following technologies were detected on {{ $scan->host }}. Knowing your stack helps identify potential vulnerabilities.
    </div>
    <div class="tech-panel">
        @php $byType = collect($scan->results['technology']['technologies'])->groupBy('type'); @endphp
        @foreach($byType as $type => $items)
        <div class="tech-group">
            <span class="tech-type-label">{{ $type }}</span>
            <div class="tech-badges">
                @foreach($items as $item)
                <span class="tech-badge">{{ $item['name'] }}</span>
                @endforeach
            </div>
        </div>
        @endforeach
        @foreach($scan->results['technology']['checks'] ?? [] as $check)
        <div style="margin-top:6px; font-size:9.5px; color:#374151;">
            <span style="color:{{ $check['status'] === 'pass' ? '#16a34a' : '#d97706' }};">
                {{ $check['status'] === 'pass' ? '&#10003;' : '&#9888;' }}
            </span>
            <strong>{{ $check['label'] }}</strong> &mdash; {{ $check['description'] }}
        </div>
        @endforeach
    </div>
    @endif

    {{-- ══ PER-CATEGORY DETAIL ══ --}}
    <div class="section-title">
        <span class="section-icon" style="background:#1e1b4b;">&#9776;</span>
        Category Details
    </div>

    @php
        $detailCategories = collect($scan->results)
            ->filter(fn($cat, $key) => isset($cat['score']) && $cat['score'] !== null && !in_array($key, ['technology', 'owasp']))
            ->sortBy('score');
    @endphp

    @foreach($detailCategories as $key => $cat)
    @php
        $s = $cat['score'];
        $scoreClass = $s >= 75 ? 'score-green' : ($s >= 50 ? 'score-yellow' : ($s >= 25 ? 'score-orange' : 'score-red'));
        $nonPassing = collect($cat['checks'] ?? [])->whereIn('status', ['fail', 'warn']);
    @endphp
    <div class="cat-section">
        <div class="cat-header">
            <span class="cat-header-name">{{ $cat['category'] }}</span>
            <span class="cat-header-score {{ $scoreClass }}">{{ $s }}/100</span>
        </div>
        @if($nonPassing->isEmpty())
        <div style="padding: 6px 12px; font-size: 10px; color: #16a34a;">&#10003; All checks passed</div>
        @else
        @foreach($nonPassing as $check)
        @php $itemClass = $check['status'] === 'warn' ? 'issue-warn' : 'issue-fail'; @endphp
        <div class="issue-item {{ $itemClass }}">
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

</div>

{{-- ══ REPORT FOOTER ══ --}}
<div class="page" style="padding-top: 0;">
    <div class="report-footer">
        <strong>WebCheckApp</strong> &mdash; Website Security Scanner<br>
        Report generated on {{ now()->format('d M Y, H:i') }} UTC &mdash; {{ $scan->tierLabel() }}<br><br>
        Scan results are for informational purposes only and do not constitute professional security advice.
        Results are based on automated checks of publicly accessible information only.<br><br>
        <strong>Need a professional security audit?</strong><br>
        Visit budgetpixels.nl for manual penetration tests, code reviews, and compliance checks.
    </div>
</div>

</body>
</html>
