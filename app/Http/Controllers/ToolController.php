<?php

namespace App\Http\Controllers;

class ToolController extends Controller
{
    private const TOOLS = [
        'ssl-checker' => [
            'title'       => 'Free SSL Certificate Checker',
            'h1'          => 'SSL Certificate Checker',
            'description' => 'Check if your website has a valid SSL certificate, HTTPS redirect, HSTS header, and TLS configuration. Free instant results.',
            'intro'       => 'SSL (Secure Sockets Layer) encrypts the connection between your website and its visitors. Without a valid SSL certificate, browsers show a "Not Secure" warning that drives visitors away. Our SSL checker verifies your certificate validity, HTTPS redirect, HSTS configuration, and TLS protocol version — all in seconds.',
            'checks'      => ['Valid SSL certificate', 'HTTPS redirect configured', 'HSTS header present', 'TLS 1.2+ only', 'No weak cipher suites', 'Certificate expiry date'],
            'faq' => [
                ['q' => 'What does the SSL checker test?', 'a' => 'It verifies your SSL certificate is valid and not expired, checks if HTTP requests redirect to HTTPS, validates HSTS headers, and ensures deprecated TLS versions (1.0, 1.1) are disabled.'],
                ['q' => 'My SSL certificate is valid but I still get warnings?', 'a' => 'A valid certificate alone is not enough. You also need an HTTP to HTTPS redirect, a Strict-Transport-Security (HSTS) header, and modern TLS configuration. Our scanner checks all of these.'],
                ['q' => 'How do I get a free SSL certificate?', 'a' => 'Use Let\'s Encrypt (letsencrypt.org) for free, auto-renewing certificates. Most hosting providers (including Forge, cPanel, Plesk) offer one-click SSL installation.'],
            ],
        ],
        'security-headers-check' => [
            'title'       => 'Security Headers Checker — Test Your HTTP Headers',
            'h1'          => 'Security Headers Checker',
            'description' => 'Analyze your website\'s HTTP security headers. Check for Content-Security-Policy, X-Frame-Options, HSTS, and more. Free online tool.',
            'intro'       => 'HTTP security headers tell browsers how to handle your website content. Missing headers can leave your site vulnerable to XSS attacks, clickjacking, MIME sniffing, and other browser-based exploits. Our scanner checks all critical security headers and provides specific fix recommendations for your server.',
            'checks'      => ['Content-Security-Policy (CSP)', 'X-Frame-Options', 'X-Content-Type-Options', 'Referrer-Policy', 'Permissions-Policy', 'CORS configuration'],
            'faq' => [
                ['q' => 'What are security headers?', 'a' => 'Security headers are HTTP response headers that instruct browsers on how to handle your site\'s content. They prevent common attacks like cross-site scripting (XSS), clickjacking, and MIME type sniffing.'],
                ['q' => 'Which security headers are most important?', 'a' => 'Content-Security-Policy (CSP), Strict-Transport-Security (HSTS), X-Frame-Options, and X-Content-Type-Options are the most critical. Together they prevent the majority of browser-based attacks.'],
                ['q' => 'How do I add security headers?', 'a' => 'Add them in your web server configuration. For Nginx: add_header X-Content-Type-Options "nosniff" always; For Apache: Header always set X-Content-Type-Options "nosniff". Our scan report includes server-specific instructions for each missing header.'],
            ],
        ],
        'dns-security-check' => [
            'title'       => 'DNS & Email Security Check — SPF, DMARC, DKIM',
            'h1'          => 'DNS & Email Security Check',
            'description' => 'Verify your domain\'s DNS security records. Check SPF, DMARC, DKIM, CAA, and DNSSEC configuration. Protect against email spoofing.',
            'intro'       => 'Your DNS records are the first line of defense against email spoofing and phishing. Without proper SPF, DMARC, and DKIM records, attackers can send emails that appear to come from your domain. Our scanner checks all email authentication records and DNS security configurations.',
            'checks'      => ['SPF record', 'DMARC policy', 'DKIM validation', 'CAA records', 'MTA-STS', 'DNSSEC'],
            'faq' => [
                ['q' => 'What is SPF and why do I need it?', 'a' => 'SPF (Sender Policy Framework) is a DNS record that specifies which mail servers are allowed to send email on behalf of your domain. Without it, anyone can forge emails from your domain for phishing attacks.'],
                ['q' => 'What is the difference between SPF and DMARC?', 'a' => 'SPF verifies the sending server, while DMARC tells receiving servers what to do when SPF or DKIM checks fail (reject, quarantine, or allow). You need both for complete email authentication.'],
                ['q' => 'How do I set up DMARC?', 'a' => 'Add a TXT record at _dmarc.yourdomain.com with value "v=DMARC1; p=reject; rua=mailto:dmarc@yourdomain.com". Start with p=none for monitoring, then progress to p=quarantine and finally p=reject.'],
            ],
        ],
        'malware-scanner' => [
            'title'       => 'Website Malware Scanner — Check Blacklists & Reputation',
            'h1'          => 'Website Malware Scanner',
            'description' => 'Scan your website against major malware databases, blacklists, and antivirus engines. Check URLhaus, Spamhaus, VirusTotal, and more.',
            'intro'       => 'A website infected with malware can harm your visitors, damage your reputation, and get your domain blacklisted by search engines. Our malware scanner checks your site against multiple threat intelligence databases including URLhaus, Spamhaus, VirusTotal, Cloudflare security DNS, and PhishTank.',
            'checks'      => ['URLhaus database', 'Spamhaus blocklist', 'VirusTotal multi-AV', 'Cloudflare security DNS', 'PhishTank phishing check', 'Domain reputation'],
            'faq' => [
                ['q' => 'How does the malware scanner work?', 'a' => 'We check your domain and IP address against multiple threat intelligence feeds and databases used by security professionals worldwide. If any of them flag your site, we report it immediately.'],
                ['q' => 'My site is flagged as malware — what do I do?', 'a' => 'First, scan your server for malicious files. Check for unauthorized file modifications, suspicious PHP files, and injected JavaScript. Update all software (CMS, plugins, themes). Then request removal from the specific blacklist.'],
                ['q' => 'Can WebCheckApp remove malware from my site?', 'a' => 'WebCheckApp detects malware and blacklist status but does not remove malware. For professional malware removal and security remediation, visit our partner BudgetPixels.nl.'],
            ],
        ],
        'owasp-scanner' => [
            'title'       => 'OWASP Top 10 Scanner — Check Web Application Security',
            'h1'          => 'OWASP Top 10 Scanner',
            'description' => 'Scan your website against the OWASP Top 10 security risks. Get a detailed report covering all ten vulnerability categories with fix recommendations.',
            'intro'       => 'The OWASP Top 10 is the global standard for web application security. It identifies the ten most critical security risks that affect web applications, from broken access control to server-side request forgery. Our scanner maps your website\'s security posture against all ten OWASP categories and provides actionable recommendations.',
            'checks'      => ['A01: Broken Access Control', 'A02: Cryptographic Failures', 'A03: Injection', 'A04: Insecure Design', 'A05: Security Misconfiguration', 'A06: Vulnerable Components', 'A07: Authentication Failures', 'A08: Data Integrity', 'A09: Logging Failures', 'A10: SSRF'],
            'faq' => [
                ['q' => 'What is the OWASP Top 10?', 'a' => 'The OWASP (Open Web Application Security Project) Top 10 is a regularly updated list of the ten most critical web application security risks. It is used worldwide by developers, security professionals, and compliance auditors.'],
                ['q' => 'Is this a real penetration test?', 'a' => 'No. Our OWASP scanner performs non-intrusive checks from the outside — it does not attempt to exploit vulnerabilities. For manual penetration testing, visit our partner BudgetPixels.nl.'],
                ['q' => 'Which scan tier includes OWASP?', 'a' => 'OWASP Top 10 analysis is included in the Pro Scan (€9.99) and Deep Scan (€29.99). The free Quick Scan does not include OWASP analysis.'],
            ],
        ],
    ];

    public function show(string $tool)
    {
        $data = self::TOOLS[$tool] ?? null;

        if (! $data) {
            abort(404);
        }

        return view('tools.show', [
            'tool'  => $tool,
            'data'  => $data,
        ]);
    }
}
