@extends('blog.layout')

@section('article_content')
<p>An <strong>SSL/TLS certificate</strong> encrypts the connection between your website and its visitors. Without it, all data — including passwords, personal information, and payment details — is transmitted in plain text. Here is everything you need to know about SSL best practices.</p>

<h2>1. Always use HTTPS</h2>
<p>Every website should use HTTPS, not just e-commerce sites. Google uses HTTPS as a ranking signal, and browsers mark HTTP sites as "Not Secure." Use our <a href="{{ route('tool.show', 'ssl-checker') }}">SSL Checker</a> to verify your setup.</p>

<h2>2. Set up HTTP to HTTPS redirect</h2>
<p>Having a certificate is not enough — you must redirect all HTTP traffic to HTTPS. Without this, visitors who type your domain without "https://" will browse insecurely.</p>
<pre><code># Nginx
server {
    listen 80;
    return 301 https://$host$request_uri;
}

# Apache .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]</code></pre>

<h2>3. Enable HSTS (HTTP Strict Transport Security)</h2>
<p>HSTS tells browsers to always use HTTPS for your domain, preventing SSL stripping attacks. Add this header to your HTTPS responses:</p>
<pre><code>Strict-Transport-Security: max-age=31536000; includeSubDomains</code></pre>

<h2>4. Use TLS 1.2 or higher only</h2>
<p>TLS 1.0 and 1.1 have known vulnerabilities and are deprecated by all major browsers. Configure your server to only accept TLS 1.2 and 1.3.</p>

<h2>5. Disable weak cipher suites</h2>
<p>Remove support for RC4, 3DES, EXPORT, and NULL cipher suites. Use ECDHE key exchange with AES-GCM for the strongest security.</p>

<h2>6. Set up auto-renewal</h2>
<p>Let's Encrypt certificates expire every 90 days. Set up automatic renewal to avoid downtime:</p>
<pre><code>sudo certbot renew --dry-run  # Test renewal
sudo crontab -e
# Add: 0 3 * * * certbot renew --quiet</code></pre>

<h2>7. Monitor certificate expiry</h2>
<p>Create a free account on <a href="{{ route('register') }}">WebCheckApp</a> to monitor your SSL certificate and get alerted 30 days before expiry.</p>
@endsection
