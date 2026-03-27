<?php

namespace App\Support;

/**
 * Detailed explanations for every scanner check.
 * Shown when the user expands a check item on the report page.
 */
class CheckKnowledge
{
    /**
     * Returns ['what' => string, 'why' => string, 'how' => string] or null.
     */
    public static function get(string $checkId): ?array
    {
        return self::library()[$checkId] ?? null;
    }

    private static function library(): array
    {
        return [

            // ── SSL & HTTPS ────────────────────────────────────────────────

            'ssl_available' => [
                'what' => 'HTTPS (HyperText Transfer Protocol Secure) encrypts all communication between the visitor\'s browser and your server using TLS (Transport Layer Security). Without it, data is sent in plain text.',
                'why'  => 'Without HTTPS, anyone on the same network (coffee shop Wi-Fi, corporate proxy) can read or modify the data being transferred — including passwords, form submissions and personal information. Google also ranks HTTPS sites higher and Chrome marks HTTP sites as "Not Secure".',
                'how'  => 'Install a TLS certificate on your web server. Free certificates are available via Let\'s Encrypt (certbot.eff.org). Most hosting panels (cPanel, Plesk, Forge) have one-click SSL installation. After installing, configure your server to redirect all HTTP traffic to HTTPS.',
            ],

            'ssl_valid' => [
                'what' => 'An SSL/TLS certificate has an expiry date. Once expired, browsers show a full-page warning to visitors and refuse to connect without clicking through a security warning.',
                'why'  => 'An expired certificate breaks trust immediately — visitors see a red warning screen and most will leave. Search engines may also de-index or lower the ranking of sites with certificate errors.',
                'how'  => 'Renew your certificate before it expires. If you use Let\'s Encrypt, set up auto-renewal with certbot (sudo certbot renew --dry-run to test). Most hosting providers send expiry warnings by email. Set a calendar reminder at 30 and 7 days before expiry.',
            ],

            'ssl_redirect' => [
                'what' => 'An HTTP to HTTPS redirect automatically sends visitors who type http:// (or click an old link) to the secure https:// version of your site.',
                'why'  => 'If HTTP is not redirected, some visitors may unknowingly browse your site without encryption. It also causes duplicate content issues for SEO since the same page exists on both http:// and https://.',
                'how'  => "Add a 301 redirect in your server config:\n\nNginx: return 301 https://\$host\$request_uri;\nApache: Redirect permanent / https://yourdomain.com/\n\nOr in .htaccess:\nRewriteEngine On\nRewriteCond %{HTTPS} off\nRewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]",
            ],

            'ssl_hsts' => [
                'what' => 'HTTP Strict Transport Security (HSTS) is a response header that tells browsers to only ever connect to your site over HTTPS — even if the user types http:// or clicks an http:// link. The browser enforces this locally for the duration of max-age.',
                'why'  => 'Even with an HTTP redirect in place, the very first request could go over HTTP before being redirected. A network attacker could intercept that first request (SSL stripping attack). HSTS prevents this by making the browser upgrade to HTTPS before making any request.',
                'how'  => "Add this header to your HTTPS responses:\nStrict-Transport-Security: max-age=31536000; includeSubDomains\n\nNginx: add_header Strict-Transport-Security \"max-age=31536000; includeSubDomains\" always;\nApache: Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains\"\n\nOnly add HSTS after you are certain your entire site works over HTTPS, including all subdomains if you use includeSubDomains.",
            ],

            'ssl_tls_version' => [
                'what' => 'TLS (Transport Layer Security) is the encryption protocol used for HTTPS. TLS 1.0 and 1.1 are old versions of this protocol that have known vulnerabilities. TLS 1.2 (2008) and TLS 1.3 (2018) are the secure modern versions.',
                'why'  => 'TLS 1.0 is vulnerable to attacks like POODLE and BEAST. TLS 1.1 has similar weaknesses. All major browsers removed support for them in 2020. PCI DSS (credit card industry standard) has required disabling TLS 1.0 since 2018. Accepting these old versions is a compliance failure and an unnecessary risk.',
                'how'  => "Disable TLS 1.0 and 1.1 in your web server config:\n\nNginx (nginx.conf):\nssl_protocols TLSv1.2 TLSv1.3;\n\nApache (ssl.conf or httpd.conf):\nSSLProtocol all -SSLv3 -TLSv1 -TLSv1.1\n\nAfter changing, restart your web server and verify at: ssllabs.com/ssltest",
            ],

            // ── Security Headers ───────────────────────────────────────────

            'header_server' => [
                'what' => 'The Server HTTP header is sent by your web server and typically reveals which software and version is running, e.g. "Apache/2.4.29 (Ubuntu)".',
                'why'  => 'Exposing the exact server version helps attackers quickly identify known vulnerabilities for that specific version. This is called "information disclosure" and is considered a low-risk but easily preventable issue.',
                'how'  => "Nginx: In nginx.conf, set: server_tokens off;\nApache: In httpd.conf or apache2.conf, set:\n  ServerTokens Prod\n  ServerSignature Off\nLiteSpeed: In WebAdmin > Server > General, set Server Signature to Hide.",
            ],

            'header_csp' => [
                'what' => 'Content Security Policy (CSP) is a browser security feature that lets you control which resources (scripts, styles, images, fonts) a page is allowed to load, and from which origins.',
                'why'  => 'CSP is one of the most effective defences against Cross-Site Scripting (XSS) attacks. Without CSP, an attacker who injects malicious JavaScript into your page can load resources from anywhere, steal session cookies, or redirect users.',
                'how'  => "Add a Content-Security-Policy header. Start with a report-only policy to detect issues without breaking anything:\nContent-Security-Policy-Report-Only: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';\n\nOnce tested, switch to enforcing:\nContent-Security-Policy: default-src 'self'; ...\n\nCSP policies can be complex for sites with third-party scripts. Use https://csp-evaluator.withgoogle.com/ to evaluate your policy.",
            ],

            'header_xframe' => [
                'what' => 'X-Frame-Options controls whether your website can be embedded in an <iframe>, <frame>, or <object> on another website.',
                'why'  => 'Without this header, attackers can embed your site invisibly in an iframe on a malicious page and trick users into clicking buttons or links without knowing it (clickjacking). This can be used to perform actions on behalf of a logged-in user.',
                'how'  => "Add one of these response headers:\nX-Frame-Options: DENY — prevents all framing\nX-Frame-Options: SAMEORIGIN — allows framing only from the same domain\n\nNginx: add_header X-Frame-Options \"SAMEORIGIN\" always;\nApache: Header always set X-Frame-Options \"SAMEORIGIN\"\n\nModern alternative: use CSP with frame-ancestors directive:\nContent-Security-Policy: frame-ancestors 'self';",
            ],

            'header_xcontent' => [
                'what' => 'X-Content-Type-Options with the value "nosniff" tells browsers not to guess (sniff) the content type of a response, but to strictly use the Content-Type header the server sends.',
                'why'  => 'Without this header, a browser might interpret an uploaded text file as JavaScript if it contains script-like content — a technique attackers can exploit to run malicious code even when file uploads are allowed.',
                'how'  => "Add this header to all responses:\nX-Content-Type-Options: nosniff\n\nNginx: add_header X-Content-Type-Options \"nosniff\" always;\nApache: Header always set X-Content-Type-Options \"nosniff\"\nLaravel: add to middleware or in .htaccess.",
            ],

            'header_referrer' => [
                'what' => 'The Referrer-Policy header controls how much information about the originating page is included in the Referer header when a user navigates away from your site or when resources are loaded.',
                'why'  => 'Without a Referrer-Policy, the full URL of the current page (which may include session tokens, user IDs, or sensitive paths) is sent to external sites in the Referer header. This can leak private information to third-party analytics, CDN providers, or ad networks.',
                'how'  => "Recommended value:\nReferrer-Policy: strict-origin-when-cross-origin\n(sends origin only for cross-origin requests, full URL for same-origin)\n\nNginx: add_header Referrer-Policy \"strict-origin-when-cross-origin\" always;\nApache: Header always set Referrer-Policy \"strict-origin-when-cross-origin\"\n\nAlternatives: no-referrer (most private), same-origin (no cross-origin referrer).",
            ],

            'header_permissions' => [
                'what' => 'Permissions-Policy (formerly Feature-Policy) lets you control which browser features and APIs your site is allowed to use, and whether third-party content embedded in iframes can access them.',
                'why'  => 'Without this header, embedded third-party scripts or iframes could theoretically request access to the camera, microphone, geolocation, payment APIs, and more. Restricting these features reduces your attack surface.',
                'how'  => "Example header that disables features not needed for most sites:\nPermissions-Policy: camera=(), microphone=(), geolocation=(), payment=()\n\nNginx: add_header Permissions-Policy \"camera=(), microphone=(), geolocation=()\" always;\nApache: Header always set Permissions-Policy \"camera=(), microphone=(), geolocation=()\"\n\nOnly disable features you genuinely don't use. Adding this header is a low-effort, high-value improvement.",
            ],

            'header_cors' => [
                'what' => 'CORS (Cross-Origin Resource Sharing) controls which external websites are allowed to make requests to your server and read the response. The Access-Control-Allow-Origin header tells the browser which origins are permitted.',
                'why'  => 'Setting Access-Control-Allow-Origin: * means any website can make requests to your server and read responses. If your site handles any authenticated data or sensitive information, this can allow malicious sites to read that data on behalf of a logged-in user.',
                'how'  => "Replace the wildcard with specific trusted origins:\nAccess-Control-Allow-Origin: https://yourapp.com\n\nNginx:\nadd_header Access-Control-Allow-Origin \"https://yourapp.com\" always;\n\nApache:\nHeader always set Access-Control-Allow-Origin \"https://yourapp.com\"\n\nIf you need to allow multiple origins, check the request Origin header and echo it back only if it matches an allow-list — a wildcard cannot be combined with credentials.",
            ],

            // ── DNS & Email Security ───────────────────────────────────────

            'dns_spf' => [
                'what' => 'Sender Policy Framework (SPF) is a DNS TXT record that specifies which mail servers are authorised to send email on behalf of your domain.',
                'why'  => 'Without SPF, anyone can send emails that appear to come from your domain (email spoofing). This is used in phishing attacks to impersonate your business. SPF tells receiving mail servers which IPs are legitimate senders.',
                'how'  => "Add a TXT record to your domain\'s DNS:\nHost: @ (apex domain)\nValue: v=spf1 include:_spf.yourmailprovider.com ~all\n\nExamples:\nGoogle Workspace: v=spf1 include:_spf.google.com ~all\nMicrosoft 365: v=spf1 include:spf.protection.outlook.com ~all\nMailchimp: v=spf1 include:servers.mcsv.net ~all\n\nUse ~all (softfail) to start, upgrade to -all (hard fail) once you're confident all sending sources are listed. Never use +all.",
            ],

            'dns_dmarc' => [
                'what' => 'DMARC (Domain-based Message Authentication, Reporting & Conformance) builds on SPF and DKIM to give domain owners control over what happens to emails that fail authentication checks.',
                'why'  => 'SPF alone is not enough — DMARC adds a policy layer that tells receiving servers what to do with suspicious emails (monitor, quarantine, or reject). It also provides reporting so you can see who is sending email as your domain.',
                'how'  => "Add a TXT record to your DNS:\nHost: _dmarc (e.g. _dmarc.yourdomain.com)\nValue: v=DMARC1; p=quarantine; rua=mailto:dmarc@yourdomain.com\n\nStart with p=none to receive reports without affecting mail delivery:\nv=DMARC1; p=none; rua=mailto:dmarc@yourdomain.com\n\nAfter analysing reports for a few weeks, upgrade to:\np=quarantine → suspicious mail goes to spam\np=reject → suspicious mail is blocked entirely\n\nFree DMARC report analysis: dmarcian.com, postmarkapp.com/dmarc.",
            ],

            'dns_caa' => [
                'what' => 'CAA (Certification Authority Authorization) is a DNS record that specifies which Certificate Authorities (CAs) are allowed to issue SSL/TLS certificates for your domain.',
                'why'  => 'Without CAA records, any of the hundreds of trusted CAs worldwide can issue a certificate for your domain. A compromised or rogue CA could issue a fraudulent certificate for your domain, enabling MITM attacks. CAA limits this risk to your chosen CA(s).',
                'how'  => "Add CAA records to your DNS. Example for Let\'s Encrypt only:\n0 issue \"letsencrypt.org\"\n\nFor multiple CAs (e.g. Let\'s Encrypt + DigiCert):\n0 issue \"letsencrypt.org\"\n0 issue \"digicert.com\"\n\nTo also allow wildcard certificates:\n0 issuewild \"letsencrypt.org\"\n\nFor email notifications on unauthorized issuance attempts:\n0 iodef \"mailto:security@yourdomain.com\"\n\nCheck current CAA records at: sslmate.com/caa",
            ],

            'dns_dnssec' => [
                'what' => 'DNSSEC (DNS Security Extensions) adds cryptographic signatures to DNS records, allowing resolvers to verify that DNS responses are authentic and have not been tampered with.',
                'why'  => 'Without DNSSEC, DNS responses can be forged (DNS cache poisoning / BGP hijacking), redirecting your visitors to a fake server without them knowing. DNSSEC ensures the DNS record they receive is the one you published.',
                'how'  => "DNSSEC must be enabled at both your DNS registrar and your DNS hosting provider:\n1. Enable DNSSEC at your domain registrar (Namecheap, GoDaddy, TransIP, etc.)\n2. Enable DNSSEC signing at your DNS host (Cloudflare enables this automatically)\n3. The registrar publishes DS records pointing to your zone\'s key\n\nIf you use Cloudflare: enable DNSSEC with one click in the DNS tab.\nNote: DNSSEC is difficult to set up incorrectly — misconfiguration can take your domain offline. Follow your registrar\'s guide carefully.",
            ],

            'dns_dkim' => [
                'what' => 'DKIM (DomainKeys Identified Mail) adds a cryptographic signature to every outgoing email. The signature is created with a private key on your mail server and verified by recipients using a public key published in DNS.',
                'why'  => 'DKIM proves that an email actually came from your mail server and was not modified in transit. Without DKIM, anyone can send emails that appear to be from your domain (spoofing), and DMARC alignment checks will fail even if SPF passes.',
                'how'  => "DKIM is configured in your email provider, not directly in DNS. Here is the process:\n\n1. Generate a DKIM key pair in your email provider:\n   - Google Workspace: Admin console → Apps → Gmail → Authenticate email\n   - Microsoft 365: Admin center → Settings → Domains → DKIM\n   - Mailchimp/SendGrid/Mailjet: Each has a DKIM setup page in their dashboard\n\n2. Copy the TXT record they provide and add it to your DNS:\n   Name: selector._domainkey.yourdomain.com\n   Value: v=DKIM1; k=rsa; p=MIGf...\n\n3. Activate DKIM signing in your provider after publishing the DNS record.\n\nThe selector name (e.g. 'google', 'selector1') comes from your email provider.",
            ],

            'dns_mta_sts' => [
                'what' => 'MTA-STS (Mail Transfer Agent Strict Transport Security) is a standard that forces other mail servers to use encrypted TLS connections when delivering email to your domain. Without it, a network attacker could silently strip TLS from email in transit.',
                'why'  => 'Email is delivered between servers using SMTP. By default, SMTP tries TLS but falls back to plaintext if TLS is not available — a downgrade attack. MTA-STS prevents this fallback, ensuring all email delivered to your domain is encrypted in transit.',
                'how'  => "Implementing MTA-STS requires two things:\n\n1. A DNS TXT record at _mta-sts.yourdomain.com:\n   v=STSv1; id=20240101001\n\n2. A policy file hosted at:\n   https://mta-sts.yourdomain.com/.well-known/mta-sts.txt\n\nPolicy file content:\n   version: STSv1\n   mode: enforce\n   mx: mail.yourdomain.com\n   max_age: 86400\n\nStart with mode: testing to see reports before enforcing. Use mta-sts.io for a guided setup.",
            ],

            'dns_bimi' => [
                'what' => 'BIMI (Brand Indicators for Message Identification) is an email standard that allows your brand logo to appear next to emails you send in supporting email clients like Gmail, Apple Mail, and Yahoo Mail.',
                'why'  => 'BIMI increases email trust and brand recognition. Emails with your logo visible are more likely to be opened and less likely to be confused with phishing. BIMI requires strong DMARC enforcement, which also improves overall email security.',
                'how'  => "BIMI requires DMARC with p=quarantine or p=reject first.\n\nThen:\n1. Prepare an SVG logo in BIMI format (square, specific SVG profile)\n2. Host it at a public HTTPS URL\n3. Add a DNS TXT record at default._bimi.yourdomain.com:\n   v=BIMI1; l=https://yourdomain.com/logo.svg\n\nFor Gmail's verified checkmark, you also need a VMC (Verified Mark Certificate) from DigiCert or Entrust.\n\nUse bimigroup.org for implementation guides and validators.",
            ],

            // ── Performance ────────────────────────────────────────────────

            'perf_ttfb' => [
                'what' => 'Time To First Byte (TTFB) is the time between the browser sending a request and receiving the first byte of the response from the server. It reflects server processing time, not download speed.',
                'why'  => 'A slow TTFB means the server takes too long to process each request — caused by slow database queries, no caching, or underpowered hosting. Google uses TTFB as a signal in Core Web Vitals. Pages with high TTFB feel slow even on fast connections.',
                'how'  => "Common fixes depending on the cause:\n\n1. Enable server-side caching\n   - WordPress: WP Super Cache, W3 Total Cache\n   - Laravel: Response caching, OPcache\n   - Nginx: FastCGI cache\n\n2. Add a CDN (Content Delivery Network)\n   - Cloudflare (free tier available)\n   - Serves cached responses from edge servers close to the visitor\n\n3. Optimise slow database queries\n   - Enable query logging and identify N+1 problems\n   - Add database indexes\n\n4. Upgrade hosting\n   - Shared hosting often has high TTFB under load\n   - Consider a VPS or managed hosting like Laravel Forge + DigitalOcean\n\nNote: our measurement is taken from our server. Geographic distance adds latency — use a CDN to reduce this globally.",
            ],

            'perf_compression' => [
                'what' => 'Response compression (gzip or Brotli) reduces the size of HTML, CSS, JavaScript and other text-based responses before sending them over the network.',
                'why'  => 'Compression typically reduces text file sizes by 60–80%. A 200 KB JavaScript file becomes ~50 KB. This directly reduces page load time, especially on slower connections, and reduces bandwidth costs.',
                'how'  => "Nginx:\ngzip on;\ngzip_types text/plain text/css application/javascript application/json;\ngzip_min_length 1000;\n\nFor Brotli (better compression, requires ngx_brotli module):\nbrotli on;\nbrotli_types text/plain text/css application/javascript;\n\nApache (.htaccess):\nAddOutputFilterByType DEFLATE text/html text/css application/javascript\n\nCloudflare: enables compression automatically — no server config needed.",
            ],

            'perf_robots' => [
                'what' => 'robots.txt is a plain text file at the root of your website that tells search engine crawlers which pages they are and aren\'t allowed to index.',
                'why'  => 'Without a robots.txt, crawlers may index admin panels, staging areas, duplicate content, or other pages that should not appear in search results. A well-configured robots.txt also prevents crawl budget waste on unimportant pages.',
                'how'  => "Create a file at https://yourdomain.com/robots.txt with at minimum:\nUser-agent: *\nDisallow:\nSitemap: https://yourdomain.com/sitemap.xml\n\nTo block specific paths:\nUser-agent: *\nDisallow: /admin/\nDisallow: /private/\nAllow: /\n\nWordPress: generated automatically. Check Settings > Reading.\nLaravel: create public/robots.txt manually.",
            ],

            'perf_sitemap' => [
                'what' => 'An XML sitemap is a file that lists all the important URLs on your website, helping search engines discover and index your pages more efficiently.',
                'why'  => 'Search engines may miss pages that are not linked from anywhere (orphan pages) or pages deep in your site structure. A sitemap ensures they are found and indexed. It also allows you to signal content priority and update frequency.',
                'how'  => "Create an XML sitemap at https://yourdomain.com/sitemap.xml\n\nWordPress: install Yoast SEO or use the built-in sitemap at /wp-sitemap.xml\nLaravel: use spatie/laravel-sitemap package\nStatic sites: generate with a sitemap generator tool\n\nAfter creating your sitemap, submit it to:\n- Google Search Console: search.google.com/search-console\n- Bing Webmaster Tools: bing.com/webmasters\n\nAlso reference it in your robots.txt:\nSitemap: https://yourdomain.com/sitemap.xml",
            ],

            // ── Content & CMS ──────────────────────────────────────────────

            'content_mixed' => [
                'what' => 'Mixed content occurs when an HTTPS page loads resources (images, scripts, stylesheets) over HTTP. The page itself is served securely, but some of its resources are not.',
                'why'  => 'Mixed active content (scripts, stylesheets) is blocked by modern browsers entirely, breaking the page. Mixed passive content (images) triggers a "Not Secure" warning. Even one HTTP resource means the page is not fully secure — the HTTP resource can be intercepted and modified.',
                'how'  => "Find all HTTP resource URLs in your HTML source and update them to HTTPS. Look for:\n- <script src=\"http://...\">\n- <link href=\"http://...\">\n- <img src=\"http://...\">\n- background-image: url('http://...')\n\nWordPress: use the Better Search Replace plugin to update URLs in the database from http:// to https://.\n\nIf you can\'t change the resource URL, consider hosting the resource yourself over HTTPS.",
            ],

            'content_admin' => [
                'what' => 'Common CMS admin panel paths like /wp-admin or /administrator are publicly accessible without any IP restriction.',
                'why'  => 'A publicly accessible admin panel is a target for brute-force attacks and credential stuffing. Attackers continuously scan the web for these paths and run automated login attempts. If credentials are weak or reused, this is how sites get compromised.',
                'how'  => "Option 1: IP restriction (most secure)\nNginx:\nlocation /wp-admin {\n  allow your.ip.address;\n  deny all;\n}\n\nOption 2: Two-factor authentication\nWordPress: install WP 2FA or Google Authenticator plugin\n\nOption 3: Move the admin URL (WordPress only)\nInstall WPS Hide Login plugin to change /wp-admin to a custom path\n\nOption 4: HTTP Basic Auth as extra layer\nAdd a password prompt before the admin panel is shown",
            ],

            'content_wp' => [
                'what' => 'The WordPress version number is visible in the HTML source — either in the generator meta tag (<meta name="generator" content="WordPress 6.2">) or in script/style URLs as ?ver=6.2.',
                'why'  => 'Knowing the exact WordPress version allows attackers to look up known CVEs (Common Vulnerabilities and Exposures) for that version and target known exploits. Version disclosure is an information leak that makes targeted attacks easier.',
                'how'  => "Remove the generator meta tag by adding to functions.php:\nremove_action('wp_head', 'wp_generator');\n\nRemove ?ver= query strings from URLs:\nfunction remove_version_strings(\$src) {\n  if (strpos(\$src, '?ver=') !== false) {\n    \$src = remove_query_arg('ver', \$src);\n  }\n  return \$src;\n}\nadd_filter('style_loader_src', 'remove_version_strings');\nadd_filter('script_loader_src', 'remove_version_strings');\n\nAlternatively use a security plugin like Wordfence or iThemes Security which does this automatically.",
            ],

            'content_dirlisting' => [
                'what' => 'Directory listing is a web server feature that automatically generates a page showing all files in a folder when no index file (index.html, index.php) is present.',
                'why'  => 'An open directory listing exposes the file structure of your server to anyone. Attackers can browse your uploads, backup files, configuration files, and other sensitive data. This is a serious information disclosure vulnerability.',
                'how'  => "Apache: Add to .htaccess or httpd.conf:\nOptions -Indexes\n\nNginx: In your server block:\nautoindex off;\n\nVerify by visiting a directory path directly in a browser. You should get a 403 Forbidden, a redirect, or your custom 404 — never an auto-generated file list.",
            ],

            'content_xmlrpc' => [
                'what' => 'WordPress XML-RPC (/xmlrpc.php) is a legacy remote API that predates the REST API. It allows external applications to interact with WordPress over HTTP using XML-encoded requests.',
                'why'  => 'XML-RPC is widely abused in two ways: (1) credential brute-forcing — attackers use the multicall method to test thousands of username/password combinations in a single request, bypassing rate limits; (2) DDoS amplification — your site can be weaponised to send floods of requests to third-party sites. Modern WordPress sites have no reason to keep XML-RPC enabled.',
                'how'  => "Block access in .htaccess:\n<Files xmlrpc.php>\n  Order Deny,Allow\n  Deny from all\n</Files>\n\nOr in Nginx:\nlocation = /xmlrpc.php {\n    deny all;\n    return 404;\n}\n\nAlternatively: install the \"Disable XML-RPC\" plugin, or use Wordfence which blocks brute-force attempts automatically.",
            ],

            'content_wp_users' => [
                'what' => 'The WordPress REST API exposes a /wp-json/wp/v2/users endpoint that by default lists all registered user accounts, including their usernames and display names.',
                'why'  => 'Knowing valid usernames makes brute-force login attacks dramatically easier — an attacker no longer needs to guess both the username and password. They can enumerate all users in seconds and then focus password attacks on those known accounts.',
                'how'  => "Add to your theme's functions.php:\n\nadd_filter('rest_endpoints', function(\$endpoints) {\n    if (isset(\$endpoints['/wp/v2/users'])) {\n        unset(\$endpoints['/wp/v2/users']);\n    }\n    if (isset(\$endpoints['/wp/v2/users/(?P<id>[\\d]+)'])) {\n        unset(\$endpoints['/wp/v2/users/(?P<id>[\\d]+)']);\n    }\n    return \$endpoints;\n});\n\nOr use a security plugin like Wordfence or iThemes Security that includes this option.",
            ],

            'content_sri' => [
                'what' => 'Subresource Integrity (SRI) is a browser security feature that lets you specify a cryptographic hash for external scripts and stylesheets. The browser refuses to execute the resource if its content does not match the hash.',
                'why'  => 'If a CDN you rely on is compromised (a real and recurring attack vector), an attacker can replace your JavaScript library with malicious code that steals user data, injects cryptomining scripts, or performs other attacks. SRI prevents this by making the browser verify the file has not been altered.',
                'how'  => "Add integrity= and crossorigin= attributes to your external resources:\n\n<script\n  src=\"https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js\"\n  integrity=\"sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=\"\n  crossorigin=\"anonymous\"\n></script>\n\nGenerate hashes for any URL at: https://www.srihash.org/\n\nFor build tools, use webpack-subresource-integrity or vite-plugin-sri to add hashes automatically during builds.",
            ],

            // ── Technology ─────────────────────────────────────────────────

            // ── Security Headers: Cookies ───────────────────────────────

            'header_cookies' => [
                'what' => 'HTTP cookies can carry security flags: HttpOnly (prevents JavaScript from reading the cookie, blocking XSS-based session theft), Secure (transmits the cookie only over HTTPS, never plain HTTP), and SameSite (controls cross-site submission, blocking CSRF attacks).',
                'why'  => 'Without HttpOnly, malicious scripts injected via XSS can steal session cookies. Without Secure, cookies can leak over HTTP redirects or mixed-content requests. Without SameSite, cookies are sent with cross-site requests, enabling CSRF attacks that make users perform actions without their knowledge.',
                'how'  => "Add all three flags when setting cookies:\nSet-Cookie: session=abc123; HttpOnly; Secure; SameSite=Lax\n\nPHP: session_set_cookie_params([\n  'httponly' => true,\n  'secure'   => true,\n  'samesite' => 'Lax',\n]);\n\nLaravel: in config/session.php set:\n'http_only' => true,\n'secure'    => true,\n'same_site' => 'lax',\n\nUse SameSite=Lax for most sites. Use SameSite=Strict if cross-site links to your site don't need to carry the session.",
            ],

            // ── Exposed Files ────────────────────────────────────────────

            'exposed_env' => [
                'what' => 'The .env file is a configuration file used by Laravel, Node.js, and many other frameworks to store environment-specific settings such as database credentials, API keys, secret tokens, and application configuration.',
                'why'  => 'Exposing .env gives attackers your database password, secret keys, and API credentials in a single file. This allows immediate database access, session forgery, and abuse of third-party services billed to you. It is one of the most critical vulnerabilities a web server can have.',
                'how'  => "Nginx — add to your server block:\nlocation ~ /\\.env {\n    deny all;\n    return 404;\n}\n\nApache — add to .htaccess:\n<Files \".env\">\n    Order allow,deny\n    Deny from all\n</Files>\n\nAlso rotate all credentials immediately: database password, API keys, APP_KEY, etc. Assume they are already compromised.",
            ],

            'exposed_git' => [
                'what' => 'The .git directory is the repository created by Git to track version history, branches, commits, and file contents. When exposed via a web server, attackers can reconstruct the entire source code by downloading the repository files.',
                'why'  => 'A publicly accessible .git directory gives attackers your complete source code including every past commit — even if you deleted sensitive files, they remain in the commit history. Attackers can find hardcoded credentials, API keys, business logic, and vulnerability patterns in the code.',
                'how'  => "Nginx — block access to .git:\nlocation ~ /\\.git {\n    deny all;\n    return 404;\n}\n\nApache — add to .htaccess:\nRedirectMatch 404 /\\.git\n\nAlternatively, deploy from a build artifact rather than cloning directly to the web root. The .git directory should never exist in a production web root.",
            ],

            'exposed_phpinfo' => [
                'what' => 'phpinfo() is a built-in PHP function that outputs a detailed page showing the PHP version, configuration directives, loaded extensions, environment variables, server paths, and build information.',
                'why'  => 'The phpinfo output gives attackers a detailed map of your server: exact PHP version (for CVE targeting), enabled extensions, file paths, and environment variables (which may include credentials). This is an information disclosure vulnerability that makes all other attacks easier to tailor.',
                'how'  => "Delete phpinfo.php (and any similar files like info.php, test.php, i.php) from your web root immediately:\nrm /var/www/html/phpinfo.php\n\nSearch for any others:\nfind /var/www -name 'phpinfo.php' -o -name 'info.php'\n\nNever create diagnostic files on production servers. Use staging environments for diagnostics.",
            ],

            'exposed_backup' => [
                'what' => 'SQL backup files (backup.sql, dump.sql, database.sql, etc.) are plain-text exports of database content produced by tools like mysqldump. When accessible via HTTP, the entire database can be downloaded.',
                'why'  => 'A publicly downloadable database backup gives attackers all user data, emails, password hashes (or worse, plaintext passwords), order records, and any other data your application stores. This is a direct GDPR/privacy law violation and gives attackers everything needed to impersonate or contact your users.',
                'how'  => "Move backups outside the web root:\nmv /var/www/html/backup.sql /var/backups/\n\nSearch for other SQL files:\nfind /var/www -name '*.sql'\n\nStore backups in a non-public location or use encrypted cloud storage (S3 with private ACL). Never store backup files in any publicly accessible directory.",
            ],

            'exposed_wpconfig' => [
                'what' => 'wp-config.php.bak is a backup copy of the WordPress configuration file. WordPress itself protects wp-config.php but backup files with .bak, .old, or .orig extensions are served as plain text by most web servers.',
                'why'  => 'This file contains the MySQL database credentials (DB_NAME, DB_USER, DB_PASSWORD, DB_HOST), authentication secret keys, and the database table prefix. With these credentials an attacker can access your entire WordPress database directly.',
                'how'  => "Delete the backup file immediately:\nrm /var/www/html/wp-config.php.bak\n\nSearch for other wp-config variants:\nfind /var/www -name 'wp-config*'\n\nTo protect against accidental future exposure, add to .htaccess:\n<Files \"wp-config.php\">\n    Order deny,allow\n    Deny from all\n</Files>",
            ],

            'exposed_htpasswd' => [
                'what' => '.htpasswd is the file used by Apache to store usernames and hashed passwords for HTTP Basic Authentication. It normally sits above the web root or is protected by Apache configuration.',
                'why'  => 'Even though passwords are hashed, exposing this file gives attackers a list of valid usernames and hashes to crack offline. Using tools like Hashcat, weak passwords (under 10 characters) can be cracked in minutes on modern hardware.',
                'how'  => "Apache normally protects .htpasswd files automatically via a built-in rule. If yours is accessible, your server config may have overridden this protection.\n\nCheck your VirtualHost config and .htaccess for anything that might be serving the file.\n\nAdd an explicit deny:\n<Files \".htpasswd\">\n    Order allow,deny\n    Deny from all\n</Files>\n\nBest practice: store .htpasswd above the web root entirely, not inside it.",
            ],

            'exposed_webconfig' => [
                'what' => 'web.config is the IIS (Internet Information Services) configuration file, equivalent to Apache\'s .htaccess. It controls URL routing, authentication, custom errors, and application settings.',
                'why'  => 'Exposed web.config files frequently contain database connection strings (including passwords), application secrets, custom error paths that reveal server internals, and authentication configurations. This is sensitive infrastructure information.',
                'how'  => "IIS should not serve web.config by default, but misconfigurations can expose it. Add a URL rewrite rule to block direct access:\n\n<rule name=\"Block web.config\">\n  <match url=\"web\\.config\" />\n  <action type=\"CustomResponse\" statusCode=\"404\" />\n</rule>\n\nVerify the IIS request filtering module is active and blocks config files.",
            ],

            'exposed_gitconfig' => [
                'what' => '.git/config is the Git configuration file for your repository. It contains the remote repository URL, branch tracking settings, and sometimes embedded credentials.',
                'why'  => 'Exposing .git/config lets attackers discover your private Git repository URL (GitHub, GitLab, Bitbucket). If credentials are embedded in the remote URL (e.g. https://username:token@github.com/...), they are directly exposed. The repository URL itself enables cloning your entire codebase.',
                'how'  => "Block all .git access at the web server level:\n\nNginx:\nlocation ~ /\\.git {\n    deny all;\n    return 404;\n}\n\nApache (.htaccess):\nRedirectMatch 404 /\\.git\n\nOr: never deploy .git directories to production servers. Use a CI/CD pipeline that only copies compiled/built files to the server, not the full git repository.",
            ],

            'exposed_composerlock' => [
                'what' => 'composer.lock records the exact version of every PHP dependency installed in your project. It\'s created by Composer when you run `composer install`.',
                'why'  => 'Exposing composer.lock gives attackers a precise inventory of every library in your application, including its exact version number. They can cross-reference this against CVE databases (cve.mitre.org, packagist advisories) to find unpatched vulnerabilities in your specific versions and craft targeted exploits.',
                'how'  => "Store composer.json and composer.lock above the web root:\n\nFor Laravel: these files should be in the project root, with only the public/ subdirectory as the web root. Most Forge/Vapor deployments do this correctly by default.\n\nIf your web root is the project root, block access:\nNginx: location ~ /composer\\.(json|lock) { deny all; }\nApache: <FilesMatch \"composer\\.(json|lock)\"> Deny from all </FilesMatch>\n\nThen run: composer audit\nTo check for known vulnerabilities in your current dependencies.",
            ],

            'exposed_serverstatus' => [
                'what' => 'Apache\'s mod_status provides a /server-status page that shows real-time server statistics: active requests, client IP addresses, URLs being requested, server version, and performance metrics.',
                'why'  => 'Exposing server-status leaks live visitor data (IPs, pages they\'re visiting), your exact Apache version, loaded modules, and server performance data. Attackers can use this to identify high-value endpoints, confirm server software, and monitor traffic patterns.',
                'how'  => "Restrict server-status to localhost only:\n\n<Location /server-status>\n    Require local\n</Location>\n\nOr disable mod_status entirely if you don't need it:\nsudo a2dismod status\nsudo systemctl restart apache2\n\nIf you need remote monitoring access, restrict it to specific trusted IPs:\nRequire ip 192.168.1.0/24",
            ],

            // ── Technology ─────────────────────────────────────────────────

            'tech_http2' => [
                'what' => 'HTTP/2 is the second major version of the HTTP protocol. It introduces multiplexing (multiple requests over a single connection), header compression, and server push — all without changing how websites work.',
                'why'  => 'HTTP/1.1 can only process one request at a time per connection, so browsers open 6 parallel connections per domain. HTTP/2 processes many requests simultaneously over one connection, significantly reducing page load time especially for pages with many resources.',
                'how'  => "Nginx (1.9.5+): add http2 to the listen directive:\nlisten 443 ssl http2;\n\nApache (2.4.17+): enable the module and set protocol:\na2enmod http2\nProtocols h2 http/1.1\n\nCloudflare: HTTP/2 is enabled automatically for all sites — no server config needed.\n\nNote: HTTP/2 requires HTTPS. Enable SSL first, then enable HTTP/2.",
            ],

            // ── Open Ports ─────────────────────────────────────────────────

            'port_21' => [
                'what' => 'Port 21 is used by FTP (File Transfer Protocol), which lets you upload and download files to your server. FTP was designed in the early internet era before encryption existed.',
                'why'  => 'FTP sends your username, password, and all transferred files in complete plaintext over the network. Anyone intercepting the connection — on the same network or via a man-in-the-middle attack — can read your credentials and every file you transfer.',
                'how'  => "Disable FTP and switch to SFTP (SSH File Transfer Protocol), which uses the same SSH encryption as terminal access:\n\nFor FileZilla: connect using protocol SFTP and your SSH credentials.\n\nTo disable pure FTP (Ubuntu):\nsudo systemctl stop vsftpd\nsudo systemctl disable vsftpd\n\nIf FTP is absolutely required, use FTPS (FTP over TLS) instead of plain FTP.",
            ],

            'port_22' => [
                'what' => 'Port 22 is used by SSH (Secure Shell), the standard encrypted protocol for remote server access. It lets administrators log in to the server and run commands remotely.',
                'why'  => 'SSH itself is secure, but an open SSH port is a constant target for brute-force attacks — bots continuously try thousands of username/password combinations. If password authentication is enabled, a weak password can lead to full server compromise.',
                'how'  => "Disable password authentication and use SSH keys only:\n\nEdit /etc/ssh/sshd_config:\nPasswordAuthentication no\nPubkeyAuthentication yes\n\nThen restart SSH:\nsudo systemctl restart sshd\n\nOptional: move SSH to a non-standard port (e.g. 2222) to reduce bot noise:\nPort 2222\n\nOptional: use fail2ban to automatically block IPs with too many failed attempts:\nsudo apt install fail2ban",
            ],

            'port_23' => [
                'what' => 'Port 23 is used by Telnet, a very old remote access protocol from the 1960s. Like FTP, it was designed before encryption existed.',
                'why'  => 'Telnet transmits everything — including your login credentials and every command you run — in complete plaintext. Anyone intercepting the connection sees exactly what you type. There is no situation where Telnet is preferable over SSH on a modern server.',
                'how'  => "Disable and remove Telnet:\nsudo systemctl stop telnet\nsudo systemctl disable telnet\nsudo apt remove telnetd  # Ubuntu/Debian\n\nIf port 23 is still open after removing Telnet, check what process is using it:\nsudo ss -tlnp | grep :23\n\nUse SSH for all remote access. SSH provides the same functionality with full encryption.",
            ],

            'port_3306' => [
                'what' => 'Port 3306 is the default port for MySQL (and MariaDB), the database server that stores your website\'s content, user accounts, orders, and all other data.',
                'why'  => 'Exposing the MySQL port to the internet allows attackers to directly attempt to log in to your database using brute force or stolen credentials. If they succeed, they have full access to all your data without needing to compromise the website itself.',
                'how'  => "Block the port with a firewall (UFW on Ubuntu):\nsudo ufw deny 3306/tcp\n\nOr restrict to only your app server IP:\nsudo ufw allow from YOUR_APP_IP to any port 3306\n\nAlso bind MySQL to localhost in /etc/mysql/mysql.conf.d/mysqld.cnf:\nbind-address = 127.0.0.1\n\nThen restart MySQL:\nsudo systemctl restart mysql\n\nFor remote DB management, use an SSH tunnel instead:\nssh -L 3306:127.0.0.1:3306 user@yourserver",
            ],

            'port_5432' => [
                'what' => 'Port 5432 is the default port for PostgreSQL, an advanced open-source relational database. Like MySQL, it stores all application data.',
                'why'  => 'A publicly reachable PostgreSQL port exposes the database directly to brute-force attacks. PostgreSQL also has a history of being exploited when authentication is misconfigured (e.g. trust authentication).',
                'how'  => "Block with UFW:\nsudo ufw deny 5432/tcp\n\nBind PostgreSQL to localhost in /etc/postgresql/*/main/postgresql.conf:\nlisten_addresses = 'localhost'\n\nRestart PostgreSQL:\nsudo systemctl restart postgresql\n\nFor remote access, use an SSH tunnel:\nssh -L 5432:127.0.0.1:5432 user@yourserver",
            ],

            'port_6379' => [
                'what' => 'Port 6379 is the default port for Redis, an in-memory data store commonly used for caching, session storage, and queues. Redis has no authentication by default.',
                'why'  => 'An exposed Redis instance is one of the most dangerous vulnerabilities a server can have. Attackers can read all cached data (including user sessions), write arbitrary data, use Redis\'s replication feature to write SSH keys to the server and gain root access, or abuse it for DDoS amplification.',
                'how'  => "Block with UFW immediately:\nsudo ufw deny 6379/tcp\n\nBind Redis to localhost in /etc/redis/redis.conf:\nbind 127.0.0.1\n\nEnable a strong password:\nrequirepass YourStrongPasswordHere\n\nRestart Redis:\nsudo systemctl restart redis\n\nIf Redis must be reachable from another server, use an SSH tunnel or VPN — never expose it directly.",
            ],

            'port_27017' => [
                'what' => 'Port 27017 is the default port for MongoDB, a NoSQL document database. MongoDB stores data as JSON-like documents and is popular for modern web applications.',
                'why'  => 'Hundreds of thousands of MongoDB databases have been wiped by automated attacks — attackers delete all data and leave a ransom note demanding Bitcoin. This happened because many MongoDB installations were publicly accessible with no authentication enabled.',
                'how'  => "Block with UFW:\nsudo ufw deny 27017/tcp\n\nBind to localhost in /etc/mongod.conf:\nnet:\n  bindIp: 127.0.0.1\n\nEnable authentication:\nsecurity:\n  authorization: enabled\n\nRestart MongoDB:\nsudo systemctl restart mongod",
            ],

            'port_9200' => [
                'what' => 'Port 9200 is the default HTTP API port for Elasticsearch, a search and analytics engine. It provides a full REST API for querying and managing data.',
                'why'  => 'Elasticsearch has no authentication by default. An exposed port gives anyone full read/write access to all indexed data via simple HTTP requests. Exposed Elasticsearch has caused massive data breaches affecting billions of records (medical data, voter records, financial data).',
                'how'  => "Block with UFW:\nsudo ufw deny 9200/tcp\nsudo ufw deny 9300/tcp  # cluster port\n\nBind to localhost in elasticsearch.yml:\nnetwork.host: 127.0.0.1\n\nIf using Elastic Cloud or a paid licence, enable X-Pack security:\nxpack.security.enabled: true\n\nRestart Elasticsearch:\nsudo systemctl restart elasticsearch",
            ],

            'port_11211' => [
                'what' => 'Port 11211 is the default port for Memcached, an in-memory caching system used to speed up web applications by storing frequently accessed data.',
                'why'  => 'Memcached has no authentication. An exposed instance lets anyone read or manipulate your cache. It is also heavily abused for DDoS amplification attacks — attackers send small spoofed requests to Memcached which generates much larger responses, overwhelming the victim.',
                'how'  => "Block with UFW:\nsudo ufw deny 11211/tcp\n\nBind to localhost when starting Memcached (in /etc/memcached.conf):\n-l 127.0.0.1\n\nRestart Memcached:\nsudo systemctl restart memcached",
            ],

            'port_25' => [
                'what' => 'Port 25 is used by SMTP (Simple Mail Transfer Protocol), the standard protocol for sending email between mail servers. It is expected to be open on dedicated mail servers.',
                'why'  => 'If this server is not a mail server, an open SMTP port may indicate an unauthorised mail relay or spam-sending software. Open relays — SMTP servers that accept and forward email from anyone — are exploited by spammers to send bulk email through your server, leading to IP blacklisting.',
                'how'  => "If this server does not send email:\nsudo ufw deny 25/tcp\n\nIf this server runs a mail server (Postfix, Exim, Sendmail):\n1. Verify it is not configured as an open relay:\n   telnet localhost 25\n   EHLO test\n   MAIL FROM: test@external.com\n   RCPT TO: test@another-external.com\n   (should be rejected)\n\n2. Keep your MTA updated and monitor /var/log/mail.log for unusual sending patterns.",
            ],

            'port_2375' => [
                'what' => 'Port 2375 is the Docker daemon\'s unencrypted TCP API port. When enabled, it allows remote control of all Docker containers on the server without any authentication.',
                'why'  => 'Access to the Docker API without TLS is equivalent to root access to the entire server. An attacker can create a privileged container that mounts the host filesystem, read and modify any file on the server, install backdoors, exfiltrate all data, or pivot to other systems on the network. This is one of the most critical misconfigurations possible.',
                'how'  => "Close port 2375 immediately:\nsudo ufw deny 2375/tcp\n\nDo NOT expose the Docker daemon over TCP without mutual TLS. Use the Unix socket instead for local access:\n/var/run/docker.sock\n\nIf remote Docker API access is needed, use SSH tunneling:\nssh -L 2375:localhost:2375 user@server\n\nOr configure Docker with TLS client certificates (docker --tlsverify).\n\nCheck if it was intentionally opened:\nsudo systemctl cat docker | grep -i tcp",
            ],

            'port_8080' => [
                'what' => 'Port 8080 is a common alternative HTTP port, often used for development servers, admin panels, reverse proxies (Nginx/Apache behind an app server), or Java application servers like Tomcat.',
                'why'  => 'An open port 8080 may expose an admin interface, development build, or staging server that was not intended to be publicly accessible. Development environments often have weaker security settings, disabled authentication, or verbose error messages that reveal internal architecture.',
                'how'  => "Identify what is running on port 8080:\nsudo ss -tlnp | grep :8080\n\nIf it is a development server or admin panel, restrict access to trusted IPs:\nsudo ufw allow from YOUR_IP to any port 8080\nsudo ufw deny 8080/tcp\n\nIf it is a legitimate proxy or app server, ensure it has authentication enabled and is not exposing internal diagnostic pages.",
            ],

            'port_8443' => [
                'what' => 'Port 8443 is a common alternative HTTPS port. It is frequently used for admin panels, development environments, application servers, or services that cannot use the standard port 443.',
                'why'  => 'Like port 8080, an open 8443 may expose admin interfaces or staging environments. Even with HTTPS, a self-signed certificate or misconfigured service on this port can be a security concern if it provides access to sensitive functionality without proper authentication.',
                'how'  => "Identify what is running on port 8443:\nsudo ss -tlnp | grep :8443\n\nIf it is an admin panel or dev interface, restrict to trusted IPs:\nsudo ufw allow from YOUR_IP to any port 8443\nsudo ufw deny 8443/tcp\n\nEnsure any service on this port uses a valid SSL certificate and has proper authentication enabled.",
            ],

            // ── Malware & Virus Scan ────────────────────────────────────────

            'malware_urlhaus' => [
                'what' => 'URLhaus is a database maintained by abuse.ch that tracks URLs and domains used to distribute malware — exploit kits, ransomware droppers, banking trojans, and other malicious software. It is one of the most comprehensive active malware distribution blocklists.',
                'why'  => 'A listing in URLhaus means this domain has been observed actively distributing malware to visitors. This could mean your website has been hacked and is serving malicious files, or that your domain was registered specifically for malware distribution.',
                'how'  => "1. Scan your website files for malware:\n   - Use a hosting panel malware scanner (cPanel/Imunify360)\n   - Use Wordfence (WordPress) or a server-side scanner like ClamAV\n   - Check recently modified files: find /var/www -newer /tmp/ref -type f\n\n2. Check access logs for suspicious uploads or requests\n\n3. Change all passwords (FTP, hosting, CMS admin, database)\n\n4. Request removal from URLhaus:\n   Visit urlhaus.abuse.ch and submit a takedown request once your site is clean",
            ],

            'malware_opendns' => [
                'what' => 'Cloudflare\'s Security DNS (1.1.1.2) is a public DNS resolver that automatically blocks domains known to distribute malware, ransomware, and phishing content. When a DNS query returns NXDOMAIN (domain not found) from the security resolver but the domain resolves normally on regular DNS, the domain is being blocked.',
                'why'  => 'Being blocked by Cloudflare\'s security resolver means the domain has been identified as harmful by Cloudflare\'s threat intelligence. This actively protects millions of internet users from visiting the site, and indicates the domain has been reported or detected as malicious.',
                'how'  => "If your site is incorrectly blocked:\n1. Check if your site has been hacked and clean any malware\n2. Submit a false positive report to Cloudflare via their security portal\n3. Check other threat databases (VirusTotal, URLhaus) for listings\n\nIf the block is justified:\n1. Clean all malware from your server\n2. Change all credentials\n3. Request removal from Cloudflare's threat database",
            ],

            'malware_spamhauszen' => [
                'what' => 'Spamhaus ZEN is a combined IP blocklist maintained by The Spamhaus Project, one of the most authoritative anti-spam and anti-malware organizations. ZEN combines SBL (spam sources), XBL (compromised/infected machines), and CBL (botnet command & control).',
                'why'  => 'An IP listed in the SBL or XBL zones indicates the server has been identified as sending spam, hosting malware, or being infected by a botnet. This can cause legitimate emails from the server to be rejected by mail providers worldwide.',
                'how'  => "1. Check which Spamhaus list the IP is on:\n   Visit check.spamhaus.org and enter your IP\n\n2. If listed in SBL (spam source):\n   - Find and remove the software or account sending spam\n   - Check for compromised email accounts\n   - Submit a removal request at spamhaus.org\n\n3. If listed in XBL (compromised machine):\n   - Your server may have malware or be part of a botnet\n   - Run a full malware scan\n   - Check for unauthorized processes: ps aux\n   - Consider rebuilding the server if compromise is confirmed",
            ],

            'malware_gsb' => [
                'what' => 'Google Safe Browsing is Google\'s threat detection system that protects users of Chrome, Firefox, Safari, and other browsers from dangerous websites. When a site is flagged, browsers display a full-page warning before allowing access.',
                'why'  => 'A Google Safe Browsing flag is extremely serious — it actively blocks visitors with a red warning page. This can reduce your traffic by 95%+ overnight. It means Google\'s systems have identified your site as distributing malware, involved in phishing, or hosting unwanted software.',
                'how'  => "1. Check your status in Google Search Console:\n   security-issues section will show what was detected\n\n2. Clean your site:\n   - Remove all malicious code and files\n   - Update all software (CMS, plugins, themes)\n   - Change all passwords\n   - Check for backdoors: hidden PHP files, base64-encoded code\n\n3. Request a review:\n   In Search Console → Security Issues → Request Review\n   Google typically reviews within 1-3 days",
            ],

            // ── Privacy & GDPR ──────────────────────────────────────────────

            'privacy_cookie_consent' => [
                'what' => 'A cookie consent banner is a notice that informs visitors about cookie usage and asks for their consent before non-essential cookies (analytics, marketing, advertising) are set. Under GDPR (EU), PECR (UK), and similar laws, this consent must be freely given, specific, and informed.',
                'why'  => 'The GDPR (General Data Protection Regulation) requires explicit consent before setting non-essential cookies. Violations can result in fines of up to €20 million or 4% of global annual turnover. Beyond legal requirements, it builds user trust and demonstrates transparency.',
                'how'  => "Use a consent management platform (CMP):\n\nFree options:\n- CookieYes (cookieyes.com) — free tier available\n- Osano (osano.com) — free for small sites\n- Cookie Consent by Osano (open source)\n\nPremium/advanced:\n- Cookiebot\n- OneTrust\n- Usercentrics\n\nFor WordPress: install a GDPR consent plugin (e.g. Complianz, CookieYes plugin)\n\nEnsure your banner:\n- Does NOT pre-tick consent boxes\n- Makes 'Reject all' as easy as 'Accept all'\n- Lists exactly which cookies are used and why",
            ],

            'privacy_policy_link' => [
                'what' => 'A privacy policy is a legal document that explains what personal data you collect from users, why you collect it, how it is used, who it is shared with, and how users can request deletion or access to their data.',
                'why'  => 'A privacy policy is legally required in most jurisdictions: GDPR (EU/EEA), CCPA (California), LGPD (Brazil), PIPEDA (Canada), and more. Without one, you risk regulatory fines, loss of payment processor accounts (Stripe/PayPal require it), removal from ad platforms, and loss of user trust.',
                'how'  => "Create a privacy policy and link to it in your footer.\n\nFree generators:\n- TermsFeed (termsfeed.com)\n- Iubenda (iubenda.com) — free tier\n- GetTerms (getterms.io)\n\nYour policy must cover:\n1. What data you collect (name, email, IP, cookies, etc.)\n2. Why you collect it (legal basis under GDPR)\n3. Who you share it with (hosting, analytics, payment processors)\n4. How long you keep it\n5. User rights (access, deletion, portability)\n6. Contact information for a data protection officer or contact\n\nUpdate it whenever you add new services or change data practices.",
            ],

            'privacy_trackers' => [
                'what' => 'Tracking scripts are third-party JavaScript snippets embedded in your website that collect data about visitor behaviour — pages visited, time spent, clicks, demographics, purchases, and more. Common examples are Google Analytics, Meta Pixel (Facebook), and Hotjar.',
                'why'  => 'Under GDPR, tracking scripts that process personal data (IP addresses, device fingerprints, cookies) require a legal basis — usually explicit consent. Loading tracking scripts before consent is obtained is a GDPR violation. Data Protection Authorities across Europe have issued fines specifically for this.',
                'how'  => "Only load tracking scripts after the user has given consent:\n\n1. Use a tag manager (Google Tag Manager) that is controlled by your consent platform — the CMP fires the tag only after consent\n\n2. Or use a consent-aware loading approach:\nif (userHasConsented()) {\n  // load analytics script\n}\n\n3. Consider privacy-friendly analytics that do not require consent:\n- Plausible Analytics (EU-hosted, no cookies)\n- Fathom Analytics\n- Matomo (self-hosted, can be cookie-free)\n\n4. For Facebook Pixel specifically: only fire events after consent and enable 'Limited Data Use' mode for California users",
            ],

        ];
    }
}
