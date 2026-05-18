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
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pre-computed payment aggregation cache.
 *
 * This model wraps the `payment_summaries` table, which stores totals
 * like paid enrollment fees, unpaid assessment counts, etc. Values are
 * updated atomically by PaymentSummaryObserver on every payment event,
 * avoiding expensive full-table SUM/COUNT queries at read time.
 */
class PaymentSummary extends Model
{
    protected $fillable = ['metric_key', 'metric_value'];

    protected $casts = [
        'metric_value' => 'decimal:2',
    ];

    /**
     * Get the current value of a metric.
     */
    public static function getValue(string $key, float $default = 0): float
    {
        $row = static::where('metric_key', $key)->first();
        return $row ? (float) $row->metric_value : $default;
    }

    /**
     * Increment a metric by a given amount (atomically).
     */
    public static function incrementMetric(string $key, float $amount = 1): void
    {
        DB::table('payment_summaries')
            ->where('metric_key', $key)
            ->increment('metric_value', $amount, ['updated_at' => now()]);
    }

    /**
     * Decrement a metric by a given amount (atomically).
     */
    public static function decrementMetric(string $key, float $amount = 1): void
    {
        // Use a parameterized statement to prevent SQL injection.
        // GREATEST ensures the value never goes below zero.
        DB::statement(
            'UPDATE payment_summaries SET metric_value = GREATEST(metric_value - ?, 0), updated_at = ? WHERE metric_key = ?',
            [$amount, now(), $key]
        );
    }

    /**
     * Set a metric to an exact value.
     */
    public static function setValue(string $key, float $value): void
    {
        static::updateOrCreate(
            ['metric_key' => $key],
            ['metric_value' => $value]
        );
    }

    /**
     * Rebuild ALL metrics from source tables.
     *
     * This is the authoritative recalculation used for initial population
     * and periodic consistency checks. Runs in a transaction to prevent
     * partial updates.
     */
    public static function rebuild(): array
    {
        $results = [];

        DB::transaction(function () use (&$results) {
            $enrollmentPaidSum = (float) \App\Models\TraineeEnrollment::where('payment_status', 'paid')
                ->sum('enrollment_fee');
            $enrollmentPaidCount = \App\Models\TraineeEnrollment::where('payment_status', 'paid')
                ->count();
            $enrollmentUnpaidSum = (float) \App\Models\TraineeEnrollment::where('payment_status', '!=', 'paid')
                ->whereNotNull('enrollment_fee')
                ->where('enrollment_fee', '>', 0)
                ->sum('enrollment_fee');
            $enrollmentUnpaidCount = \App\Models\TraineeEnrollment::where('payment_status', '!=', 'paid')
                ->whereNotNull('enrollment_fee')
                ->where('enrollment_fee', '>', 0)
                ->count();

            $assessmentPaidSum = (float) \App\Models\Assessment::where('payment_status', 'paid')
                ->sum('assessment_fee');
            $assessmentPaidCount = \App\Models\Assessment::where('payment_status', 'paid')
                ->count();
            $assessmentUnpaidSum = (float) \App\Models\Assessment::where('payment_status', '!=', 'paid')
                ->whereNotNull('assessment_fee')
                ->where('assessment_fee', '>', 0)
                ->sum('assessment_fee');
            $assessmentUnpaidCount = \App\Models\Assessment::where('payment_status', '!=', 'paid')
                ->whereNotNull('assessment_fee')
                ->where('assessment_fee', '>', 0)
                ->count();

            $metrics = [
                'enrollment_paid_sum' => $enrollmentPaidSum,
                'enrollment_paid_count' => $enrollmentPaidCount,
                'enrollment_unpaid_sum' => $enrollmentUnpaidSum,
                'enrollment_unpaid_count' => $enrollmentUnpaidCount,
                'assessment_paid_sum' => $assessmentPaidSum,
                'assessment_paid_count' => $assessmentPaidCount,
                'assessment_unpaid_sum' => $assessmentUnpaidSum,
                'assessment_unpaid_count' => $assessmentUnpaidCount,
            ];

            foreach ($metrics as $key => $value) {
                static::setValue($key, $value);
                $results[$key] = $value;
            }

            Log::info('[PAYMENT-SUMMARY] Full rebuild completed', $results);
        });

        return $results;
    }
}
