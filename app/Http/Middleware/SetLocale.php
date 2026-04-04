<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check explicit session value first
        if (session()->has('locale') && in_array(session('locale'), ['nl', 'en'])) {
            App::setLocale(session('locale'));
            return $next($request);
        }

        // Auto-detect from browser
        $browserLang = substr($request->server('HTTP_ACCEPT_LANGUAGE', 'en'), 0, 2);
        $locale = $browserLang === 'nl' ? 'nl' : 'en';
        App::setLocale($locale);

        return $next($request);
    }
}
