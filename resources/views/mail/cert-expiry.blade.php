<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SSL certificate expiring soon — {{ $site->domain }}</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
  .wrapper { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
  .header { background: #7c2d12; padding: 32px 40px; }
  .header h1 { color: #fff; margin: 0; font-size: 18px; font-weight: 700; }
  .header p  { color: #fdba74; margin: 4px 0 0; font-size: 14px; }
  .body { padding: 32px 40px; }
  .alert-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 20px 24px; margin-bottom: 24px; text-align: center; }
  .alert-box .days { font-size: 48px; font-weight: 900; color: #ea580c; line-height: 1; }
  .alert-box .lbl { font-size: 14px; color: #9a3412; margin-top: 4px; }
  p { color: #374151; font-size: 14px; line-height: 1.6; }
  .btn { display: inline-block; background: #4f46e5; color: #fff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-top: 8px; }
  .footer { padding: 20px 40px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8; }
  .footer a { color: #94a3b8; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>SSL certificate expiring soon</h1>
    <p>{{ $site->domain }}</p>
  </div>
  <div class="body">
    <div class="alert-box">
      <div class="days">{{ $daysLeft }}</div>
      <div class="lbl">days until your certificate expires</div>
    </div>

    <p>
      The SSL certificate for <strong>{{ $site->domain }}</strong> will expire in
      <strong>{{ $daysLeft }} days</strong>. Once it expires, visitors will see a
      security warning in their browser and your site will be flagged as insecure.
    </p>

    <p>
      If you use Let's Encrypt the renewal usually happens automatically. For other
      providers, renew the certificate via your hosting control panel or registrar.
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
