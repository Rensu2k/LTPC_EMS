<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // When exposing the app via HTTPS (e.g., ngrok), Laravel may
        // think requests are HTTP and generate http:// asset URLs,
        // causing mixed-content errors. Allow forcing HTTPS via env.
        if (config('app.force_https')) {
            // Force HTTPS scheme for all URL generation
            URL::forceScheme('https');
            
            // Force root URL to ensure all generated URLs use HTTPS
            $appUrl = config('app.url');
            if ($appUrl && str_starts_with($appUrl, 'https://')) {
                URL::forceRootUrl($appUrl);
            }
            
            // Force HTTPS for all generated URLs
            if (App::environment('local')) {
                // Ensure the current request is treated as HTTPS
                if (request()->isSecure()) {
                    URL::forceScheme('https');
                }
            }
        }
    }
}
