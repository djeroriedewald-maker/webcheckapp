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
                'how'  => "Remove the generator meta tag by adding to functions.php:\nremove_action('wp_head', 'wp_generator');\n\nRemove ?ver= query strings from URLs:\nfunction remove_version_strings($src) {\n  if (strpos($src, '?ver=') !== false) {\n    $src = remove_query_arg('ver', $src);\n  }\n  return $src;\n}\nadd_filter('style_loader_src', 'remove_version_strings');\nadd_filter('script_loader_src', 'remove_version_strings');\n\nAlternatively use a security plugin like Wordfence or iThemes Security which does this automatically.",
            ],

            'content_dirlisting' => [
                'what' => 'Directory listing is a web server feature that automatically generates a page showing all files in a folder when no index file (index.html, index.php) is present.',
                'why'  => 'An open directory listing exposes the file structure of your server to anyone. Attackers can browse your uploads, backup files, configuration files, and other sensitive data. This is a serious information disclosure vulnerability.',
                'how'  => "Apache: Add to .htaccess or httpd.conf:\nOptions -Indexes\n\nNginx: In your server block:\nautoindex off;\n\nVerify by visiting a directory path directly in a browser. You should get a 403 Forbidden, a redirect, or your custom 404 — never an auto-generated file list.",
            ],

            // ── Technology ─────────────────────────────────────────────────

            'tech_http2' => [
                'what' => 'HTTP/2 is the second major version of the HTTP protocol. It introduces multiplexing (multiple requests over a single connection), header compression, and server push — all without changing how websites work.',
                'why'  => 'HTTP/1.1 can only process one request at a time per connection, so browsers open 6 parallel connections per domain. HTTP/2 processes many requests simultaneously over one connection, significantly reducing page load time especially for pages with many resources.',
                'how'  => "Nginx (1.9.5+): add http2 to the listen directive:\nlisten 443 ssl http2;\n\nApache (2.4.17+): enable the module and set protocol:\na2enmod http2\nProtocols h2 http/1.1\n\nCloudflare: HTTP/2 is enabled automatically for all sites — no server config needed.\n\nNote: HTTP/2 requires HTTPS. Enable SSL first, then enable HTTP/2.",
            ],

        ];
    }
}
