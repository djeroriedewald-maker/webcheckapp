@extends('layouts.app')

@section('title', 'Privacy Policy — WebCheckApp')
@section('meta_description', 'WebCheckApp privacy policy — what data we collect and how we handle it.')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <div class="mb-10">
        <h1 class="text-3xl font-bold text-white mb-3">Privacy Policy</h1>
        <p class="text-gray-400">Last updated: {{ date('F j, Y') }}</p>
    </div>

    <div class="prose prose-invert prose-gray max-w-none space-y-8 text-gray-300 leading-relaxed">

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Who we are</h2>
            <p>WebCheckApp is a free website security scanner operated by <a href="https://www.budgetpixels.nl" target="_blank" rel="noopener noreferrer" class="text-indigo-400 hover:text-indigo-300">BudgetPixels.nl</a>. This policy explains what data we collect when you use WebCheckApp and how we handle it.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">What data we collect</h2>
            <p>When you submit a URL for scanning, we store the following data:</p>
            <ul class="list-disc list-inside mt-3 space-y-2 text-gray-400">
                <li><strong class="text-gray-300">The URL you submitted</strong> — to perform the scan and display the report</li>
                <li><strong class="text-gray-300">Your IP address</strong> — to prevent abuse and rate limiting</li>
                <li><strong class="text-gray-300">Scan results</strong> — the security report generated for the submitted URL</li>
                <li><strong class="text-gray-300">Timestamp</strong> — date and time the scan was performed</li>
            </ul>
            <p class="mt-3">We do <strong class="text-gray-300">not</strong> collect: names, email addresses, account information, payment data or any other personal information. We do not use tracking cookies or analytics.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Why we collect this data</h2>
            <ul class="list-disc list-inside mt-3 space-y-2 text-gray-400">
                <li>To perform the security scan and display the results to you</li>
                <li>To prevent automated abuse and excessive scanning</li>
                <li>To improve the reliability of our scanning service</li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Legal basis (GDPR)</h2>
            <p>We process data on the basis of <strong class="text-gray-300">legitimate interest</strong> (Article 6(1)(f) GDPR) — specifically to provide the requested scan service and to protect the tool from misuse. IP addresses are processed to prevent abuse and are not used for any other purpose.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Data retention</h2>
            <p>Scan results and associated IP addresses are stored for a maximum of <strong class="text-gray-300">30 days</strong>, after which they are automatically deleted. We do not sell or share this data with third parties.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Third-party requests</h2>
            <p>When you submit a URL, our scanner makes HTTP requests to the website you specified. This means the scanned website's server will receive requests from our server's IP address. We do not share your IP address with the scanned website.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Cookies</h2>
            <p>WebCheckApp uses only a functional session cookie required for the application to work (CSRF protection). This cookie contains no personal information, is not used for tracking, and expires when you close your browser.</p>
            <p class="mt-2">We do not use advertising cookies, analytics cookies, or any third-party tracking.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Your rights</h2>
            <p>Under GDPR you have the right to access, rectify or delete your data. Since we do not collect identifying personal information beyond IP addresses (which we cannot link back to you as an individual), we are generally unable to identify which scan records belong to you without additional context.</p>
            <p class="mt-2">For any privacy-related requests, contact us via <a href="https://www.budgetpixels.nl" target="_blank" rel="noopener noreferrer" class="text-indigo-400 hover:text-indigo-300">budgetpixels.nl</a>.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Changes to this policy</h2>
            <p>We may update this privacy policy from time to time. The date at the top of this page reflects when it was last updated. Continued use of WebCheckApp after changes constitutes acceptance of the updated policy.</p>
        </section>

    </div>
</div>
@endsection
