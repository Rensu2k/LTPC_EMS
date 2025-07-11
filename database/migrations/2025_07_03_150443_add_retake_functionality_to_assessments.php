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
            // Add retake functionality fields
            $table->unsignedBigInteger('original_assessment_id')->nullable()->after('id');
            $table->integer('attempt_number')->default(1)->after('original_assessment_id');
            $table->boolean('is_retake')->default(false)->after('attempt_number');
            
            // Add foreign key constraint for original assessment
            $table->foreign('original_assessment_id')->references('id')->on('assessments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['original_assessment_id']);
            
            // Drop the retake functionality fields
            $table->dropColumn(['original_assessment_id', 'attempt_number', 'is_retake']);
        });
    }
};
