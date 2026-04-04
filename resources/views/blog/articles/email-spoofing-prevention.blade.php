@extends('blog.layout')

@section('article_content')
<p><strong>Email spoofing</strong> is when attackers send emails that appear to come from your domain. This is used in phishing attacks, business email compromise, and spam campaigns. The good news: it is entirely preventable with three DNS records. Use our <a href="{{ route('tool.show', 'dns-security-check') }}">DNS Security Checker</a> to verify your setup.</p>

<h2>SPF (Sender Policy Framework)</h2>
<p>SPF specifies which mail servers are allowed to send email on behalf of your domain. Add a TXT record to your DNS:</p>
<pre><code># Basic SPF record
v=spf1 include:_spf.google.com -all

# If you use multiple providers
v=spf1 include:_spf.google.com include:sendgrid.net -all</code></pre>
<p>The <code>-all</code> at the end is critical — it tells receiving servers to reject emails from unauthorized servers. Using <code>~all</code> (soft fail) provides less protection.</p>

<h2>DKIM (DomainKeys Identified Mail)</h2>
<p>DKIM adds a cryptographic signature to your outgoing emails, allowing recipients to verify the email was not altered in transit. Your email provider generates the keys — you add the public key as a DNS record.</p>
<pre><code># Example DKIM record (provided by your email service)
default._domainkey.yourdomain.com TXT "v=DKIM1; k=rsa; p=MIGfMA0G..."</code></pre>

<h2>DMARC (Domain-based Message Authentication)</h2>
<p>DMARC ties SPF and DKIM together and tells receiving servers what to do when authentication fails. Start with monitoring, then progress to enforcement:</p>
<pre><code># Step 1: Monitor only (start here)
_dmarc.yourdomain.com TXT "v=DMARC1; p=none; rua=mailto:dmarc@yourdomain.com"

# Step 2: Quarantine failures
_dmarc.yourdomain.com TXT "v=DMARC1; p=quarantine; rua=mailto:dmarc@yourdomain.com"

# Step 3: Reject failures (maximum protection)
_dmarc.yourdomain.com TXT "v=DMARC1; p=reject; rua=mailto:dmarc@yourdomain.com"</code></pre>

<h2>Implementation order</h2>
<ol>
<li>Set up <strong>SPF</strong> first — it is the simplest and most impactful</li>
<li>Configure <strong>DKIM</strong> through your email provider</li>
<li>Add <strong>DMARC</strong> with <code>p=none</code> to monitor for 2-4 weeks</li>
<li>Review DMARC reports, then tighten to <code>p=quarantine</code> and finally <code>p=reject</code></li>
</ol>

<h2>Verify your configuration</h2>
<p>Run a free <a href="{{ route('home') }}">security scan</a> on your domain to check your SPF, DKIM, and DMARC configuration. Our DNS scanner validates all email authentication records.</p>
@endsection
