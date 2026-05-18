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
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('programs', 'max_enrollments')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->integer('max_enrollments')->default(25)->after('enrollment_fee');
            });
        } else {
            DB::table('programs')
                ->whereNull('max_enrollments')
                ->orWhere('max_enrollments', 0)
                ->update(['max_enrollments' => 25]);
        }

        if (!Schema::hasColumn('programs', 'current_batch')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->integer('current_batch')->default(1)->after('max_enrollments');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            if (Schema::hasColumn('programs', 'current_batch')) {
                $table->dropColumn('current_batch');
            }
        });
    }
};
