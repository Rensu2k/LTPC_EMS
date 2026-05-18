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
namespace App\Observers;

use App\Models\PaymentSummary;
use App\Models\TraineeEnrollment;
use App\Models\Assessment;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the payment_summaries cache table in sync with source data.
 *
 * Registered against TraineeEnrollment and Assessment models so that
 * every create/update/delete atomically adjusts the cached totals.
 */
class PaymentSummaryObserver
{

    /**
     * Handle the TraineeEnrollment "created" event.
     */
    public function enrollmentCreated(TraineeEnrollment $enrollment): void
    {
        $fee = (float) ($enrollment->enrollment_fee ?? 0);

        if ($enrollment->payment_status === 'paid') {
            PaymentSummary::incrementMetric('enrollment_paid_count');
            if ($fee > 0) {
                PaymentSummary::incrementMetric('enrollment_paid_sum', $fee);
            }
        } else {
            if ($fee > 0) {
                PaymentSummary::incrementMetric('enrollment_unpaid_count');
                PaymentSummary::incrementMetric('enrollment_unpaid_sum', $fee);
            }
        }
    }

    /**
     * Handle the TraineeEnrollment "updated" event.
     */
    public function enrollmentUpdated(TraineeEnrollment $enrollment): void
    {
        if (!$enrollment->wasChanged('payment_status')) {
            return;
        }

        $fee = (float) ($enrollment->enrollment_fee ?? 0);
        $oldStatus = $enrollment->getOriginal('payment_status');
        $newStatus = $enrollment->payment_status;

        if ($oldStatus !== 'paid' && $newStatus === 'paid') {
            PaymentSummary::incrementMetric('enrollment_paid_count');
            if ($fee > 0) {
                PaymentSummary::incrementMetric('enrollment_paid_sum', $fee);
                PaymentSummary::decrementMetric('enrollment_unpaid_count');
                PaymentSummary::decrementMetric('enrollment_unpaid_sum', $fee);
            }
        }

        if ($oldStatus === 'paid' && $newStatus !== 'paid') {
            PaymentSummary::decrementMetric('enrollment_paid_count');
            if ($fee > 0) {
                PaymentSummary::decrementMetric('enrollment_paid_sum', $fee);
                PaymentSummary::incrementMetric('enrollment_unpaid_count');
                PaymentSummary::incrementMetric('enrollment_unpaid_sum', $fee);
            }
        }
    }

    /**
     * Handle the TraineeEnrollment "deleted" event.
     */
    public function enrollmentDeleted(TraineeEnrollment $enrollment): void
    {
        $fee = (float) ($enrollment->enrollment_fee ?? 0);

        if ($enrollment->payment_status === 'paid') {
            PaymentSummary::decrementMetric('enrollment_paid_count');
            if ($fee > 0) {
                PaymentSummary::decrementMetric('enrollment_paid_sum', $fee);
            }
        } else {
            if ($fee > 0) {
                PaymentSummary::decrementMetric('enrollment_unpaid_count');
                PaymentSummary::decrementMetric('enrollment_unpaid_sum', $fee);
            }
        }
    }


    /**
     * Handle the Assessment "created" event.
     */
    public function assessmentCreated(Assessment $assessment): void
    {
        $fee = (float) ($assessment->assessment_fee ?? 0);

        if ($assessment->payment_status === 'paid') {
            PaymentSummary::incrementMetric('assessment_paid_count');
            if ($fee > 0) {
                PaymentSummary::incrementMetric('assessment_paid_sum', $fee);
            }
        } else {
            if ($fee > 0) {
                PaymentSummary::incrementMetric('assessment_unpaid_count');
                PaymentSummary::incrementMetric('assessment_unpaid_sum', $fee);
            }
        }
    }

    /**
     * Handle the Assessment "updated" event.
     */
    public function assessmentUpdated(Assessment $assessment): void
    {
        if (!$assessment->wasChanged('payment_status')) {
            return;
        }

        $fee = (float) ($assessment->assessment_fee ?? 0);
        $oldStatus = $assessment->getOriginal('payment_status');
        $newStatus = $assessment->payment_status;

        if ($oldStatus !== 'paid' && $newStatus === 'paid') {
            PaymentSummary::incrementMetric('assessment_paid_count');
            if ($fee > 0) {
                PaymentSummary::incrementMetric('assessment_paid_sum', $fee);
                PaymentSummary::decrementMetric('assessment_unpaid_count');
                PaymentSummary::decrementMetric('assessment_unpaid_sum', $fee);
            }
        }

        if ($oldStatus === 'paid' && $newStatus !== 'paid') {
            PaymentSummary::decrementMetric('assessment_paid_count');
            if ($fee > 0) {
                PaymentSummary::decrementMetric('assessment_paid_sum', $fee);
                PaymentSummary::incrementMetric('assessment_unpaid_count');
                PaymentSummary::incrementMetric('assessment_unpaid_sum', $fee);
            }
        }
    }

    /**
     * Handle the Assessment "deleted" event.
     */
    public function assessmentDeleted(Assessment $assessment): void
    {
        $fee = (float) ($assessment->assessment_fee ?? 0);

        if ($assessment->payment_status === 'paid') {
            PaymentSummary::decrementMetric('assessment_paid_count');
            if ($fee > 0) {
                PaymentSummary::decrementMetric('assessment_paid_sum', $fee);
            }
        } else {
            if ($fee > 0) {
                PaymentSummary::decrementMetric('assessment_unpaid_count');
                PaymentSummary::decrementMetric('assessment_unpaid_sum', $fee);
            }
        }
    }
}
