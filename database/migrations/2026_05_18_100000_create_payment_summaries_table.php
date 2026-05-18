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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create a lightweight cache table for payment aggregation metrics.
 *
 * The Cashier dashboard performs SUM/COUNT queries across 2M+ enrollment
 * and assessment rows. These aggregations take 6-7 seconds each. This
 * table stores pre-computed totals, updated via model observers on every
 * payment event, reducing query time from ~7s to <1ms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key', 100)->unique();
            $table->decimal('metric_value', 15, 2)->default(0);
            $table->timestamps();
        });

        // Seed the initial metric keys so observers can increment/decrement.
        $metrics = [
            'enrollment_paid_sum',
            'enrollment_paid_count',
            'enrollment_unpaid_sum',
            'enrollment_unpaid_count',
            'assessment_paid_sum',
            'assessment_paid_count',
            'assessment_unpaid_sum',
            'assessment_unpaid_count',
        ];

        $now = now();
        foreach ($metrics as $key) {
            \Illuminate\Support\Facades\DB::table('payment_summaries')->insert([
                'metric_key' => $key,
                'metric_value' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_summaries');
    }
};
