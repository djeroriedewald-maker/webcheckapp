<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Security score dropped — {{ $site->domain }}</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
  .wrapper { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
  .header { background: #1e1b4b; padding: 32px 40px; }
  .header h1 { color: #fff; margin: 0; font-size: 18px; font-weight: 700; }
  .header p  { color: #a5b4fc; margin: 4px 0 0; font-size: 14px; }
  .body { padding: 32px 40px; }
  .score-row { display: flex; align-items: center; gap: 24px; background: #f8fafc; border-radius: 10px; padding: 20px 24px; margin-bottom: 24px; }
  .score-box { text-align: center; flex: 1; }
  .score-box .num { font-size: 32px; font-weight: 900; }
  .score-box .lbl { font-size: 12px; color: #64748b; margin-top: 2px; }
  .arrow { font-size: 24px; color: #ef4444; }
  .was { color: #94a3b8; }
  .now { color: #ef4444; }
  p { color: #374151; font-size: 14px; line-height: 1.6; }
  .btn { display: inline-block; background: #4f46e5; color: #fff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-top: 8px; }
  .footer { padding: 20px 40px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8; }
  .footer a { color: #94a3b8; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Security score dropped</h1>
    <p>{{ $site->domain }}</p>
  </div>
  <div class="body">
    <div class="score-row">
      <div class="score-box">
        <div class="num was">{{ $previousScore }}</div>
        <div class="lbl">Previous score</div>
      </div>
      <div class="arrow">→</div>
      <div class="score-box">
        <div class="num now">{{ $currentScore }}</div>
        <div class="lbl">Current score</div>
      </div>
    </div>

    <p>
      The security score for <strong>{{ $site->domain }}</strong> dropped by
      <strong>{{ $previousScore - $currentScore }} points</strong>
      (grade: <strong>{{ $scan->grade }}</strong>).
      We recommend reviewing the full report to identify what changed.
    </p>

    <a href="{{ route('scan.show', $scan) }}" class="btn">View full security report →</a>
  </div>
  <div class="footer">
    You're receiving this because you monitor {{ $site->domain }} on
    <a href="{{ url('/') }}">WebCheckApp</a>.
    Sign in to your <a href="{{ route('dashboard') }}">dashboard</a> to manage alerts.
  </div>
</div>
</body>
</html>
