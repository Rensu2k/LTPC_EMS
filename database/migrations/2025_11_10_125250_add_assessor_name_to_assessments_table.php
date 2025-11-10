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
        Schema::table('assessments', function (Blueprint $table) {
            // Add assessor_name field for manual input
            $table->string('assessor_name')->nullable()->after('trainer_id');
            
            // Make trainer_id nullable to allow manual assessor input
            $table->unsignedBigInteger('trainer_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            // Remove assessor_name field
            $table->dropColumn('assessor_name');
            
            // Make trainer_id required again (note: this might fail if there are null values)
            // You may need to handle existing null values before running down migration
            $table->unsignedBigInteger('trainer_id')->nullable(false)->change();
        });
    }
};
