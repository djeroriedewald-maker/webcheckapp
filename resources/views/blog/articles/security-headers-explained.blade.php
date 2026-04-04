@extends('blog.layout')

@section('article_content')
<p><strong>HTTP security headers</strong> are response headers that tell browsers how to handle your website's content. They are one of the easiest and most effective ways to protect your website against common attacks. Use our <a href="{{ route('tool.show', 'security-headers-check') }}">Security Headers Checker</a> to see which ones you are missing.</p>

<h2>Content-Security-Policy (CSP)</h2>
<p>The most powerful security header. CSP controls which resources (scripts, styles, images) the browser is allowed to load. A strict CSP prevents Cross-Site Scripting (XSS) attacks by blocking inline scripts and unauthorized external resources.</p>
<pre><code>Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'</code></pre>

<h2>X-Frame-Options</h2>
<p>Prevents your website from being embedded in an iframe on another site, which protects against clickjacking attacks.</p>
<pre><code>X-Frame-Options: DENY
# or
X-Frame-Options: SAMEORIGIN</code></pre>

<h2>X-Content-Type-Options</h2>
<p>Prevents browsers from MIME-sniffing the response content type. Without this, a browser might interpret a text file as JavaScript and execute it.</p>
<pre><code>X-Content-Type-Options: nosniff</code></pre>

<h2>Referrer-Policy</h2>
<p>Controls how much referrer information is sent when navigating away from your site. Prevents leaking sensitive URL parameters to third parties.</p>
<pre><code>Referrer-Policy: strict-origin-when-cross-origin</code></pre>

<h2>Permissions-Policy</h2>
<p>Controls which browser features (camera, microphone, geolocation) your site can use. Restricting unused features reduces attack surface.</p>
<pre><code>Permissions-Policy: camera=(), microphone=(), geolocation=()</code></pre>

<h2>Strict-Transport-Security (HSTS)</h2>
<p>Forces browsers to only connect via HTTPS. See our <a href="{{ route('blog.show', 'ssl-certificate-best-practices') }}">SSL best practices guide</a> for details.</p>

<h2>How to add security headers</h2>
<p>Add headers in your web server configuration:</p>
<pre><code># Nginx
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# Apache
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set Referrer-Policy "strict-origin-when-cross-origin"</code></pre>
@endsection
