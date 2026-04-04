@extends('blog.layout')

@section('article_content')
<p>The <strong>OWASP Top 10</strong> is the most widely recognized list of critical web application security risks. Published by the <a href="https://owasp.org" target="_blank" rel="noopener">Open Web Application Security Project</a>, it helps developers and organizations understand the most dangerous vulnerabilities affecting web applications today.</p>

<h2>The OWASP Top 10 (2021 Edition)</h2>

<h3>A01:2021 — Broken Access Control</h3>
<p>Access control ensures users can only access what they are authorized to. When broken, attackers can view other users' data, modify records, or perform admin functions. This moved to #1 from #5 in the previous edition.</p>

<h3>A02:2021 — Cryptographic Failures</h3>
<p>Previously known as "Sensitive Data Exposure." This covers failures in cryptography that lead to exposure of sensitive data — weak encryption, missing HTTPS, improper certificate validation, and plaintext storage of passwords.</p>

<h3>A03:2021 — Injection</h3>
<p>Injection attacks occur when untrusted data is sent to an interpreter. SQL injection, Cross-Site Scripting (XSS), and command injection are the most common. A strong Content-Security-Policy header helps mitigate XSS attacks.</p>

<h3>A04:2021 — Insecure Design</h3>
<p>A new category focusing on design flaws rather than implementation bugs. This includes missing rate limiting, lack of abuse prevention, and absence of threat modeling during development.</p>

<h3>A05:2021 — Security Misconfiguration</h3>
<p>The most commonly seen issue. Default credentials, unnecessary features enabled, overly permissive error handling, and missing security headers all fall under this category.</p>

<h3>A06:2021 — Vulnerable and Outdated Components</h3>
<p>Using libraries, frameworks, or dependencies with known vulnerabilities. This includes outdated jQuery versions, unpatched WordPress installations, and EOL PHP versions.</p>

<h3>A07:2021 — Identification and Authentication Failures</h3>
<p>Weak password policies, missing multi-factor authentication, exposed session tokens, and user enumeration vulnerabilities. Proper session management and cookie security flags are essential.</p>

<h3>A08:2021 — Software and Data Integrity Failures</h3>
<p>A new category covering issues like missing Subresource Integrity (SRI) hashes on external scripts, insecure deserialization, and CI/CD pipeline vulnerabilities.</p>

<h3>A09:2021 — Security Logging and Monitoring Failures</h3>
<p>Without proper logging and monitoring, breaches go undetected. Having a security.txt file (RFC 9116) helps security researchers report vulnerabilities to you responsibly.</p>

<h3>A10:2021 — Server-Side Request Forgery (SSRF)</h3>
<p>SSRF occurs when a web application fetches a remote resource without validating the user-supplied URL. Attackers can use this to access internal services, cloud metadata endpoints, or internal networks.</p>

<h2>How to test your website against OWASP Top 10</h2>
<p>Our <a href="{{ route('tool.show', 'owasp-scanner') }}">OWASP Top 10 Scanner</a> maps your website's security posture against all ten categories. Available in our Pro Scan (€9.99) and Deep Scan (€29.99), it provides a detailed report with risk levels and fix recommendations for each category.</p>

<h2>Why OWASP Top 10 matters for your business</h2>
<p>Many compliance frameworks (PCI DSS, SOC 2, ISO 27001) reference the OWASP Top 10. Understanding and addressing these risks is not just good security practice — it is often a business requirement.</p>
@endsection
