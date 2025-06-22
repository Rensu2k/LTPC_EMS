<?php

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
        // Add max_enrollments column if it doesn't exist
        if (!Schema::hasColumn('programs', 'max_enrollments')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->integer('max_enrollments')->default(25)->after('enrollment_fee');
            });
        } else {
            // Update existing programs to have a default max_enrollments of 25 if null
            DB::table('programs')
                ->whereNull('max_enrollments')
                ->orWhere('max_enrollments', 0)
                ->update(['max_enrollments' => 25]);
        }

        // Also add current_batch column to track the current batch number for each program
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
            // Don't drop max_enrollments as it might have been added in a previous migration
        });
    }
};
