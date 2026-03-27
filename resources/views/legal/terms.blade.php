@extends('layouts.app')

@section('title', 'Terms of Use — WebCheckApp')
@section('meta_description', 'Terms of use for WebCheckApp — free website security scanner.')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <div class="mb-10">
        <h1 class="text-3xl font-bold text-white mb-3">Terms of Use</h1>
        <p class="text-gray-400">Last updated: {{ date('F j, Y') }}</p>
    </div>

    <div class="prose prose-invert prose-gray max-w-none space-y-8 text-gray-300 leading-relaxed">

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Acceptance of terms</h2>
            <p>By using WebCheckApp you agree to these Terms of Use. If you do not agree, please do not use this service. WebCheckApp is operated by <a href="https://www.budgetpixels.nl" target="_blank" rel="noopener noreferrer" class="text-indigo-400 hover:text-indigo-300">BudgetPixels.nl</a>.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Permitted use</h2>
            <p>You may use WebCheckApp to scan:</p>
            <ul class="list-disc list-inside mt-3 space-y-2 text-gray-400">
                <li>Websites that you own or operate</li>
                <li>Websites for which you have explicit written permission from the owner to perform security testing</li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Prohibited use</h2>
            <p>You may <strong class="text-white">not</strong> use WebCheckApp to:</p>
            <ul class="list-disc list-inside mt-3 space-y-2 text-gray-400">
                <li>Scan websites you do not own and have not received explicit permission to test</li>
                <li>Perform automated bulk scanning or use the service via scripts or bots</li>
                <li>Attempt to overload, disrupt or attack the WebCheckApp service itself</li>
                <li>Use scan results to exploit vulnerabilities found on third-party websites</li>
                <li>Circumvent any rate limiting or access controls on this service</li>
                <li>Use the service for any unlawful purpose</li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">How the scanner works</h2>
            <p>When you submit a URL, our server makes HTTP requests to the website you specified. This is similar to a regular browser visit. Our scanner only accesses publicly available information — it does not attempt to exploit vulnerabilities, guess credentials or perform any intrusive testing.</p>
            <p class="mt-3">Nevertheless, by submitting a URL you confirm that you are authorised to have our server make requests to that website.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">No guarantee of service</h2>
            <p>WebCheckApp is provided free of charge on an <strong class="text-white">"as is"</strong> basis. We do not guarantee continuous availability, accuracy of results, or fitness for any particular purpose. We reserve the right to modify, suspend or discontinue the service at any time without notice.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Limitation of liability</h2>
            <p>To the maximum extent permitted by applicable law, BudgetPixels.nl is not liable for any direct, indirect, incidental, consequential or punitive damages arising from your use of WebCheckApp or reliance on its results.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Intellectual property</h2>
            <p>All content, code and design of WebCheckApp is the property of BudgetPixels.nl. You may not copy, reproduce or redistribute any part of this service without prior written permission.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Governing law</h2>
            <p>These terms are governed by the laws of the Netherlands. Any disputes shall be submitted to the competent court in the Netherlands.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Changes to these terms</h2>
            <p>We may update these terms at any time. Continued use of WebCheckApp after changes constitutes acceptance of the updated terms.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-white mb-3">Contact</h2>
            <p>Questions about these terms? Contact us via <a href="https://www.budgetpixels.nl" target="_blank" rel="noopener noreferrer" class="text-indigo-400 hover:text-indigo-300">budgetpixels.nl</a>.</p>
        </section>

    </div>
</div>
@endsection
