<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::defaultStringLength(191);
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'enrollment_fee')) {
                $table->decimal('enrollment_fee', 10, 2)->nullable()->after('max_enrollments');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'enrollment_fee')) {
                $table->dropColumn('enrollment_fee');
            }
        });
    }
};
