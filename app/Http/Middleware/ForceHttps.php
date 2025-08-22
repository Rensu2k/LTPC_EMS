<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS for all URLs when FORCE_HTTPS is enabled
        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)) {
            URL::forceScheme('https');
            
            // Force root URL to ensure all generated URLs use HTTPS
            if (env('APP_URL') && str_starts_with(env('APP_URL'), 'https://')) {
                URL::forceRootUrl(env('APP_URL'));
            }
        }

        return $next($request);
    }
}
