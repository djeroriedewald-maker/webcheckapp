<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate the CSP nonce BEFORE building the response so that Blade
        // templates can reference it via Vite::cspNonce(). The @vite directive
        // also automatically adds this nonce to its generated <script> tags.
        $nonce = Vite::useCspNonce();

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

        // Nonce-based Content Security Policy.
        // - No 'unsafe-inline': all inline <script> blocks must carry nonce="{{ Vite::cspNonce() }}"
        // - 'unsafe-eval' is required by Alpine.js v3, which uses new Function() to evaluate
        //   x-data/x-init expressions. Do NOT remove it without switching to @alpinejs/csp build.
        // - COOP/COEP are intentionally omitted: COOP breaks bfcache and the scan overlay (see b181a61 / 6a710fd).
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "font-src 'self'",
            "form-action 'self' https://checkout.stripe.com",
            "frame-ancestors 'none'",
            "base-uri 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // Make the XSRF-TOKEN cookie HttpOnly.
        // This app uses only HTML form CSRF (@csrf blade directive) — no JavaScript ever
        // reads the XSRF-TOKEN cookie — so HttpOnly is safe and removes the scanner warning.
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'XSRF-TOKEN') {
                $response->headers->removeCookie(
                    $cookie->getName(),
                    $cookie->getPath(),
                    $cookie->getDomain()
                );
                $response->headers->setCookie(new Cookie(
                    $cookie->getName(),
                    $cookie->getValue(),
                    $cookie->getExpiresTime(),
                    $cookie->getPath(),
                    $cookie->getDomain(),
                    $cookie->isSecure(),
                    true, // httpOnly
                    $cookie->isRaw(),
                    $cookie->getSameSite()
                ));
                break;
            }
        }

        return $response;
    }
}
