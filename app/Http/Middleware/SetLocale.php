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
        // Check session first, then browser preference
        $locale = session('locale');

        if (! $locale) {
            $browserLang = substr($request->server('HTTP_ACCEPT_LANGUAGE', 'en'), 0, 2);
            $locale = in_array($browserLang, ['nl', 'en']) ? $browserLang : 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
