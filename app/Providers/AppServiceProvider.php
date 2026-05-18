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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $observer = new PaymentSummaryObserver();

        TraineeEnrollment::created(fn ($e) => $observer->enrollmentCreated($e));
        TraineeEnrollment::updated(fn ($e) => $observer->enrollmentUpdated($e));
        TraineeEnrollment::deleted(fn ($e) => $observer->enrollmentDeleted($e));

        Assessment::created(fn ($a) => $observer->assessmentCreated($a));
        Assessment::updated(fn ($a) => $observer->assessmentUpdated($a));
        Assessment::deleted(fn ($a) => $observer->assessmentDeleted($a));

        if (config('app.force_https')) {
            URL::forceScheme('https');
            
            $appUrl = config('app.url');
            if ($appUrl && str_starts_with($appUrl, 'https://')) {
                URL::forceRootUrl($appUrl);
            }
            
            if (App::environment('local')) {
                if (request()->isSecure()) {
                    URL::forceScheme('https');
                }
            }
        }
    }
}
