<?php
/**
 * LTPC Enrollment Management System (LTPC_EMS)
 *
 * @copyright  2025-2026 Clarence Buenaflor & Jester Pastor
 * @author     Clarence Buenaflor <cbuenaflor2@ssct.edu.ph>
 * @author     Jester Pastor <pastorjester98@mail.com>
 * @license    Proprietary - All Rights Reserved
 *
 * Unauthorized copying, modification, or distribution of this
 * software is strictly prohibited without express written permission.
 */
namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use App\Models\TraineeEnrollment;
use App\Models\Assessment;
use App\Observers\PaymentSummaryObserver;

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

        // ── Payment summary cache observers ──────────────────────────
        // Keep the payment_summaries table in sync on every payment event.
        $observer = new PaymentSummaryObserver();

        TraineeEnrollment::created(fn ($e) => $observer->enrollmentCreated($e));
        TraineeEnrollment::updated(fn ($e) => $observer->enrollmentUpdated($e));
        TraineeEnrollment::deleted(fn ($e) => $observer->enrollmentDeleted($e));

        Assessment::created(fn ($a) => $observer->assessmentCreated($a));
        Assessment::updated(fn ($a) => $observer->assessmentUpdated($a));
        Assessment::deleted(fn ($a) => $observer->assessmentDeleted($a));

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
