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
        Schema::table('trainee_enrollments', function (Blueprint $table) {
            $table->date('date_started')->nullable()->after('enrollment_date');
            $table->date('date_ended')->nullable()->after('completion_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainee_enrollments', function (Blueprint $table) {
            $table->dropColumn(['date_started', 'date_ended']);
        });
    }
};
