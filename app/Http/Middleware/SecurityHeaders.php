<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Clickjacking protection
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Limit referrer information sent cross-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser feature access
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Force HTTPS for 1 year (only send over HTTPS to avoid breaking HTTP)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // NOTE: Content-Security-Policy is intentionally omitted.
        // CSP with script-src breaks Alpine.js when Vite serves <script type="module">
        // because 'unsafe-inline' does not apply to module scripts in all browsers.
        // Removed in b181a61, do not re-add without a proper nonce-based CSP.

        // NOTE: Cross-Origin-Opener-Policy is intentionally omitted.
        // COOP: same-origin can interfere with bfcache and cause the scan loading
        // overlay to persist after browser back navigation.

        return $response;
    }
}
