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
 * Add an index on trainees.uli_number to support search queries.
 *
 * The ULI number search used LIKE '%keyword%' which prevents any index usage,
 * forcing a full scan of 1M+ rows (2.4 seconds). With this index, prefix
 * searches like LIKE 'ULI-5000%' can use index range scans (~200ms).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainees', function (Blueprint $table) {
            $table->index('uli_number', 'idx_trainees_uli_number');
        });
    }

    public function down(): void
    {
        Schema::table('trainees', function (Blueprint $table) {
            $table->dropIndex('idx_trainees_uli_number');
        });
    }
};
