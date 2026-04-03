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
        padding: 28px 36px;
    }
    .header-inner {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .brand { font-size: 20px; font-weight: bold; color: #ffffff; }
    .brand span { color: #818cf8; }
    .header-tagline { font-size: 9px; color: #a5b4fc; margin-top: 2px; }
    .header-host { font-size: 15px; font-weight: bold; color: #ffffff; margin-top: 12px; }
    .header-sub { font-size: 9px; color: #a5b4fc; margin-top: 2px; }
    .header-meta { text-align: right; font-size: 9px; color: #a5b4fc; line-height: 1.8; }

    /* ── Score banner ── */
    .score-banner {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding: 22px 36px;
        display: flex;
        align-items: center;
        gap: 28px;
    }
    .score-circle {
        width: 82px; height: 82px;
        border-radius: 50%;
        border: 6px solid #6366f1;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; flex-shrink: 0;
    }
    .score-num { font-size: 24px; font-weight: bold; color: #1e1b4b; line-height: 1; }
    .score-denom { font-size: 9px; color: #6b7280; }

    .grade-box { text-align: center; flex-shrink: 0; }
    .grade-letter { font-size: 52px; font-weight: 900; line-height: 1; }
    .grade-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
    .grade-a  { color: #10b981; }
    .grade-b  { color: #22c55e; }
    .grade-c  { color: #eab308; }
    .grade-d  { color: #f97316; }
    .grade-f  { color: #ef4444; }

    /* ── Summary stats ── */
    .summary-stats {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .summary-line { font-size: 10px; color: #374151; }
    .summary-line strong { color: #1e1b4b; }
    .stat-pill {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: bold;
        margin-right: 6px;
    }
    .pill-fail { background: #fee2e2; color: #b91c1c; }
    .pill-warn { background: #fef9c3; color: #92400e; }
    .pill-pass { background: #dcfce7; color: #166534; }

    /* ── Key scores table ── */
    .key-scores {
        flex-shrink: 0;
        width: 210px;
    }
    .key-scores table { width: 100%; border-collapse: collapse; }
    .key-scores td { padding: 2px 6px 2px 0; font-size: 9.5px; vertical-align: middle; }
    .ks-label { color: #374151; width: 110px; }
    .ks-bar-wrap { width: 100%; background: #e5e7eb; border-radius: 3px; height: 6px; }
    .ks-bar { height: 6px; border-radius: 3px; }
    .bar-green  { background: #22c55e; }
    .bar-yellow { background: #eab308; }
    .bar-red    { background: #ef4444; }
    .ks-score { width: 32px; text-align: right; font-weight: bold; font-size: 9px; }
    .score-green  { color: #16a34a; }
    .score-yellow { color: #ca8a04; }
    .score-red    { color: #dc2626; }

    /* ── Content ── */
    .content { padding: 26px 36px; }

    .section-title {
        font-size: 12px; font-weight: bold; color: #1e1b4b;
        margin-bottom: 10px; padding-bottom: 5px;
        border-bottom: 2px solid #e2e8f0;
        margin-top: 20px;
    }
    .section-title:first-child { margin-top: 0; }

    /* ── Issue items ── */
    .issue-item {
        padding: 8px 10px; margin-bottom: 5px;
        border-radius: 4px; border-left: 3px solid;
    }
    .issue-fail { background: #fef2f2; border-color: #ef4444; }
    .issue-warn { background: #fffbeb; border-color: #f59e0b; }
    .issue-pass { background: #f0fdf4; border-color: #22c55e; }

    .issue-label { font-weight: bold; font-size: 10px; margin-bottom: 2px; }
    .issue-desc  { font-size: 9.5px; color: #4b5563; }
    .issue-fix   { font-size: 9px; color: #4338ca; margin-top: 3px; }

    .issue-badge {
        display: inline-block; font-size: 8px;
        padding: 1px 5px; border-radius: 10px;
        margin-left: 5px; font-weight: normal; vertical-align: middle;
    }
    .badge-fail { background: #fee2e2; color: #b91c1c; }
    .badge-warn { background: #fef9c3; color: #92400e; }

    /* ── Passing checks table ── */
    .pass-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .pass-table td { padding: 4px 8px; font-size: 9.5px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    .pass-table tr:last-child td { border-bottom: none; }
    .pass-check { color: #1f2937; }
    .pass-cat   { color: #9ca3af; font-size: 9px; }
    .pass-tick  { color: #16a34a; font-weight: bold; width: 16px; }

    /* ── Category detail ── */
    .cat-section { margin-bottom: 16px; page-break-inside: avoid; }
    .cat-header {
        display: flex; justify-content: space-between; align-items: center;
        background: #f1f5f9; padding: 6px 10px;
        border-radius: 4px; margin-bottom: 5px;
    }
    .cat-header-name { font-weight: bold; font-size: 11px; color: #1e1b4b; }
    .cat-header-score { font-weight: bold; font-size: 11px; }

    /* ── Technology panel ── */
    .tech-panel {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 4px; padding: 10px 12px; margin-bottom: 6px;
    }
    .tech-group { margin-bottom: 5px; }
    .tech-type-label {
        font-size: 9px; color: #6b7280; text-transform: uppercase;
        letter-spacing: 0.5px; display: inline-block; width: 90px;
    }
    .tech-badge {
        display: inline-block; background: #e0e7ff; color: #3730a3;
        font-size: 9px; padding: 1px 7px; border-radius: 10px;
        margin-right: 4px; margin-bottom: 2px;
    }

    /* ── Footer ── */
    .footer {
        margin-top: 24px; padding: 14px 36px;
        background: #f8fafc; border-top: 1px solid #e2e8f0;
        font-size: 9px; color: #9ca3af; text-align: center;
    }

    .page-break { page-break-after: always; }
    .no-break   { page-break-inside: avoid; }
</style>
</head>
<body>

{{-- ══ HEADER ══ --}}
<div class="header">
    <div class="header-inner">
        <div>
            <div class="brand">WebCheck<span>App</span></div>
            <div class="header-tagline">Website Security Scanner</div>
            <div class="header-host">{{ $scan->host }}</div>
            <div class="header-sub">
                {{ $scan->tierLabel() }} Report
                @if(!$scan->isFree()) &mdash; {{ count($scan->results ?? []) }} categories scanned @endif
            </div>
        </div>
        <div class="header-meta">
            <div>Scanned: {{ $scan->completed_at->format('d M Y, H:i') }} UTC</div>
            <div>Generated: {{ now()->format('d M Y, H:i') }} UTC</div>
            <div style="margin-top:4px;">webcheckapp.com</div>
        </div>
    </div>
</div>

{{-- ══ SCORE BANNER ══ --}}
@php
    $allChecks = collect($scan->results)
        ->filter(fn($c) => isset($c['score']) && $c['score'] !== null)
        ->flatMap(fn($c) => collect($c['checks'] ?? [])->map(fn($ch) => array_merge($ch, ['_cat' => $c['category']])));
    $failures  = $allChecks->where('status', 'fail');
    $warnings  = $allChecks->where('status', 'warn');
    $passes    = $allChecks->where('status', 'pass');

    $gradeClass = $scan->score >= 90 ? 'grade-a'
        : ($scan->score >= 75 ? 'grade-b'
        : ($scan->score >= 60 ? 'grade-c'
        : ($scan->score >= 40 ? 'grade-d' : 'grade-f')));

    // Only the 6 weighted categories in the score bar
    $weightedKeys = ['ssl', 'headers', 'dns', 'performance', 'content', 'exposed_files'];
@endphp

<div class="score-banner">

    <div class="score-circle">
        <div class="score-num">{{ $scan->score }}</div>
        <div class="score-denom">/100</div>
    </div>

    <div class="grade-box">
        <div class="grade-letter {{ $gradeClass }}">{{ $scan->grade }}</div>
        <div class="grade-label">Overall Grade</div>
    </div>

    <div class="summary-stats">
        <div class="summary-line">
            <span class="stat-pill pill-fail">{{ $failures->count() }} critical</span>
            <span class="stat-pill pill-warn">{{ $warnings->count() }} warnings</span>
            <span class="stat-pill pill-pass">{{ $passes->count() }} passed</span>
        </div>
        <div class="summary-line" style="margin-top:4px; font-size:9px; color:#6b7280;">
            @if($failures->count() === 0)
                No critical issues found. Good job!
            @else
                {{ $failures->count() }} {{ $failures->count() === 1 ? 'issue requires' : 'issues require' }} immediate attention.
            @endif
        </div>
    </div>

    {{-- Key category score bars (weighted only) --}}
    <div class="key-scores">
        <table>
            @foreach($weightedKeys as $wk)
            @if(isset($scan->results[$wk]) && $scan->results[$wk]['score'] !== null)
            @php
                $s = $scan->results[$wk]['score'];
                $bc = $s >= 75 ? 'bar-green' : ($s >= 50 ? 'bar-yellow' : 'bar-red');
                $sc = $s >= 75 ? 'score-green' : ($s >= 50 ? 'score-yellow' : 'score-red');
            @endphp
            <tr>
                <td class="ks-label">{{ $scan->results[$wk]['category'] }}</td>
                <td><div class="ks-bar-wrap"><div class="ks-bar {{ $bc }}" style="width:{{ $s }}%;"></div></div></td>
                <td class="ks-score {{ $sc }}">{{ $s }}</td>
            </tr>
            @endif
            @endforeach
        </table>
    </div>

</div>

{{-- ══ MAIN CONTENT ══ --}}
<div class="content">

    {{-- ══ EXECUTIVE SUMMARY ══ --}}
    @php
        $catCount = collect($scan->results)->filter(fn($c) => isset($c['score']) && $c['score'] !== null)->count();
        $strongAreas = collect($scan->results)
            ->filter(fn($c) => isset($c['score']) && $c['score'] >= 80)
            ->pluck('category')
            ->take(4)
            ->implode(', ');
        $weakAreas = collect($scan->results)
            ->filter(fn($c) => isset($c['score']) && $c['score'] !== null && $c['score'] < 60)
            ->sortBy('score')
            ->pluck('category')
            ->take(4)
            ->implode(', ');
        $mediumAreas = collect($scan->results)
            ->filter(fn($c) => isset($c['score']) && $c['score'] >= 60 && $c['score'] < 80)
            ->pluck('category')
            ->take(4)
            ->implode(', ');
    @endphp
    <div class="section-title">Executive Summary</div>
    <div style="font-size: 10.5px; color: #374151; line-height: 1.7; margin-bottom: 16px;">
        <p style="margin-bottom: 8px;">
            We performed a comprehensive security analysis of <strong>{{ $scan->host }}</strong> across {{ $catCount }} categories.
            The website received an overall score of <strong>{{ $scan->score }}/100</strong> (grade <strong>{{ $scan->grade }}</strong>),
            with {{ $failures->count() }} critical {{ $failures->count() === 1 ? 'issue' : 'issues' }},
            {{ $warnings->count() }} {{ $warnings->count() === 1 ? 'warning' : 'warnings' }},
            and {{ $passes->count() }} passed {{ $passes->count() === 1 ? 'check' : 'checks' }}.
        </p>

        @if($scan->score >= 85)
        <p style="margin-bottom: 8px;">
            <strong>Overall assessment:</strong> {{ $scan->host }} demonstrates a strong security posture. The website follows most security best practices and is well-configured. Minor improvements are possible but no urgent issues were found.
        </p>
        @elseif($scan->score >= 65)
        <p style="margin-bottom: 8px;">
            <strong>Overall assessment:</strong> {{ $scan->host }} has a reasonable security foundation but there is room for improvement. Several issues were identified that could expose the website or its users to unnecessary risk. We recommend addressing the critical issues first, followed by the warnings.
        </p>
        @elseif($scan->score >= 40)
        <p style="margin-bottom: 8px;">
            <strong>Overall assessment:</strong> {{ $scan->host }} has significant security gaps that should be addressed as soon as possible. The current configuration leaves the website vulnerable to common attacks. We strongly recommend reviewing the critical issues listed below and implementing the recommended fixes.
        </p>
        @else
        <p style="margin-bottom: 8px;">
            <strong>Overall assessment:</strong> {{ $scan->host }} has serious security deficiencies across multiple areas. The website is at high risk of exploitation. Immediate action is required to protect the website and its users. We urge you to address the critical issues as a top priority.
        </p>
        @endif

        @if($strongAreas)
        <p style="margin-bottom: 4px;">
            <span style="color: #16a34a; font-weight: bold;">&#10003; Strong areas:</span> {{ $strongAreas }}.
        </p>
        @endif

        @if($mediumAreas)
        <p style="margin-bottom: 4px;">
            <span style="color: #ca8a04; font-weight: bold;">&#9888; Needs improvement:</span> {{ $mediumAreas }}.
        </p>
        @endif

        @if($weakAreas)
        <p style="margin-bottom: 4px;">
            <span style="color: #dc2626; font-weight: bold;">&#10007; Weak areas:</span> {{ $weakAreas }}.
        </p>
        @endif
    </div>

    {{-- ══ OWASP TOP 10 (first, most recognizable) ══ --}}
    @if(isset($scan->results['owasp']) && !empty($scan->results['owasp']['checks']))
    <div class="section-title" style="color: #7c3aed;">OWASP Top 10 Analysis (Score: {{ $scan->results['owasp']['score'] ?? 0 }}/100)</div>
    <div style="font-size: 10px; color: #4b5563; margin-bottom: 10px; line-height: 1.6;">
        The OWASP Top 10 is the globally recognized standard for web application security risks.
        Below is how {{ $scan->host }} scores against each of the ten categories.
    </div>
    <div class="cat-section">
        @foreach($scan->results['owasp']['checks'] as $check)
        @php
            $riskColor = match($check['risk'] ?? 'Medium') {
                'Critical' => '#dc2626',
                'High'     => '#ea580c',
                'Medium'   => '#ca8a04',
                'Low'      => '#16a34a',
                default    => '#6b7280',
            };
            $itemClass = match($check['status']) {
                'pass' => 'issue-pass',
                'warn' => 'issue-warn',
                default => 'issue-fail',
            };
        @endphp
        <div class="issue-item {{ $itemClass }}" style="margin-bottom: 8px;">
            <div class="issue-label" style="display: flex; justify-content: space-between;">
                <span>{{ $check['status'] === 'pass' ? '&#10003;' : ($check['status'] === 'warn' ? '&#9888;' : '&#10007;') }} {{ $check['label'] }}</span>
                <span style="color: {{ $riskColor }}; font-weight: bold; font-size: 9px;">{{ $check['risk'] ?? '' }} Risk</span>
            </div>
            <div class="issue-desc">{{ $check['description'] }}</div>
            @if(!empty($check['recommendation']))
            <div class="issue-fix">&#128161; {{ $check['recommendation'] }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- ══ CRITICAL ISSUES ══ --}}
    @if($failures->count() > 0)
    <div class="no-break">
        <div class="section-title">&#x26A0; Critical Issues ({{ $failures->count() }})</div>
        <div style="font-size: 10px; color: #4b5563; margin-bottom: 8px;">
            These issues pose an immediate security risk and should be addressed as a priority.
        </div>
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
    @endif

    {{-- ══ WARNINGS ══ --}}
    @if($warnings->count() > 0)
    <div class="no-break">
        <div class="section-title">Warnings ({{ $warnings->count() }})</div>
        <div style="font-size: 10px; color: #4b5563; margin-bottom: 8px;">
            These items are not immediately critical but should be reviewed to strengthen your security posture.
        </div>
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
    @endif

    {{-- ══ PASSED CHECKS ══ --}}
    @if($passes->count() > 0)
    <div class="no-break">
        <div class="section-title">&#10003; Passed Checks ({{ $passes->count() }})</div>
        <div style="font-size: 10px; color: #4b5563; margin-bottom: 8px;">
            These checks were all successfully validated. Keep up the good work.
        </div>
        <table class="pass-table">
            @foreach($passes as $check)
            <tr>
                <td class="pass-tick">&#10003;</td>
                <td class="pass-check">{{ $check['label'] }}</td>
                <td class="pass-cat">{{ $check['_cat'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <div class="page-break"></div>

    {{-- ══ TECHNOLOGY STACK ══ --}}
    @if(!empty($scan->results['technology']['technologies']))
    <div class="no-break">
        <div class="section-title">Detected Technologies</div>
        <div style="font-size: 10px; color: #4b5563; margin-bottom: 8px;">
            The following technologies were detected on {{ $scan->host }}. Knowing your stack helps identify potential vulnerabilities.
        </div>
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
            @foreach($scan->results['technology']['checks'] ?? [] as $check)
            <div style="margin-top:6px; font-size:9.5px; color:#374151;">
                <span style="color:{{ $check['status'] === 'pass' ? '#16a34a' : '#d97706' }};">
                    {{ $check['status'] === 'pass' ? '&#10003;' : '&#9888;' }}
                </span>
                <strong>{{ $check['label'] }}</strong> — {{ $check['description'] }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Per-category detail --}}
    <div class="section-title">Category Details</div>

    @php
        $detailCategories = collect($scan->results)
            ->filter(fn($cat, $key) => isset($cat['score']) && $cat['score'] !== null && !in_array($key, ['technology', 'owasp']))
            ->sortBy('score');
    @endphp

    @foreach($detailCategories as $key => $cat)
    @php
        $s = $cat['score'];
        $scoreClass = $s >= 75 ? 'score-green' : ($s >= 50 ? 'score-yellow' : 'score-red');
        $nonPassing = collect($cat['checks'] ?? [])->whereIn('status', ['fail', 'warn']);
    @endphp
    <div class="cat-section">
        <div class="cat-header">
            <span class="cat-header-name">{{ $cat['category'] }}</span>
            <span class="cat-header-score {{ $scoreClass }}">{{ $s }}/100</span>
        </div>
        @if($nonPassing->isEmpty())
        <div style="padding: 4px 10px; font-size: 9.5px; color: #16a34a;">&#10003; All checks passed</div>
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

{{-- ══ FOOTER ══ --}}
<div class="footer">
    Report generated by WebCheckApp (webcheckapp.com) &mdash; {{ $scan->tierLabel() }} &mdash; {{ $scan->completed_at->format('d M Y') }}<br>
    Scan results are for informational purposes only and do not constitute professional security advice.
    Results are based on automated checks of publicly accessible information only.<br>
    <br>
    <strong>Need a professional security audit?</strong> Visit budgetpixels.nl for manual penetration tests, code reviews, and compliance checks.
</div>

</body>
</html>
