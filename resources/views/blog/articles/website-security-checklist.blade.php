@extends('blog.layout')

@section('article_content')
<p>Website security is not a one-time task — it requires ongoing attention. This checklist covers everything you need to secure your website, organized by priority. <a href="{{ route('home') }}">Run a free scan</a> to see where you stand right now.</p>

<h2>Critical (fix immediately)</h2>
<ul>
<li><strong>Enable HTTPS</strong> — Install an SSL certificate and redirect all HTTP to HTTPS. <a href="{{ route('blog.show', 'ssl-certificate-best-practices') }}">SSL best practices →</a></li>
<li><strong>Remove exposed files</strong> — Check for publicly accessible .env, .git, phpinfo.php, and backup files</li>
<li><strong>Update everything</strong> — CMS, plugins, frameworks, and server software should be on the latest version</li>
<li><strong>Use strong passwords</strong> — Enforce minimum 12 characters, enable multi-factor authentication where possible</li>
</ul>

<h2>High priority</h2>
<ul>
<li><strong>Add security headers</strong> — CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy. <a href="{{ route('blog.show', 'security-headers-explained') }}">Security headers guide →</a></li>
<li><strong>Set up email authentication</strong> — SPF, DKIM, and DMARC records. <a href="{{ route('blog.show', 'email-spoofing-prevention') }}">Email spoofing prevention →</a></li>
<li><strong>Configure HSTS</strong> — Force HTTPS at the browser level</li>
<li><strong>Close unnecessary ports</strong> — Database ports (3306, 5432), Redis (6379), and management ports should not be public</li>
</ul>

<h2>Medium priority</h2>
<ul>
<li><strong>Enable compression</strong> — Gzip or Brotli for faster load times</li>
<li><strong>Add robots.txt and sitemap.xml</strong> — Guide search engines and prevent indexing of sensitive paths</li>
<li><strong>Check for mixed content</strong> — All resources should load over HTTPS</li>
<li><strong>Review cookie settings</strong> — Session cookies need Secure, HttpOnly, and SameSite flags</li>
<li><strong>Add a security.txt file</strong> — Let security researchers know how to contact you (RFC 9116)</li>
</ul>

<h2>Nice to have</h2>
<ul>
<li><strong>Add Subresource Integrity (SRI)</strong> — Hash verification for external scripts</li>
<li><strong>Set up CAA records</strong> — Restrict which CAs can issue certificates for your domain</li>
<li><strong>Implement DNSSEC</strong> — Protect against DNS cache poisoning</li>
<li><strong>Privacy compliance</strong> — Cookie consent banner, privacy policy, GDPR compliance</li>
<li><strong>Accessibility basics</strong> — Alt texts, heading structure, viewport meta</li>
</ul>

<h2>Ongoing monitoring</h2>
<ul>
<li><strong>Regular scans</strong> — Run a security scan at least monthly</li>
<li><strong>SSL monitoring</strong> — Get alerted before certificates expire</li>
<li><strong>Score tracking</strong> — Monitor your security score over time via our <a href="{{ route('register') }}">free dashboard</a></li>
<li><strong>Dependency audits</strong> — Check for vulnerable packages regularly</li>
</ul>

<h2>Test your website now</h2>
<p>Our <a href="{{ route('tool.show', 'owasp-scanner') }}">OWASP Top 10 Scanner</a> checks your website against this entire checklist and more. The Pro Scan covers 20 security categories, and the Deep Scan adds penetration-style testing with 27 total scanners.</p>
@endsection
