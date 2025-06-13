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
            // Applicant type and external applicant info
            $table->enum('applicant_type', ['enrolled_trainee', 'external_applicant'])->default('enrolled_trainee')->after('trainer_id');
            $table->string('external_applicant_name')->nullable()->after('applicant_type');
            $table->string('external_applicant_email')->nullable()->after('external_applicant_name');
            $table->string('external_applicant_phone')->nullable()->after('external_applicant_email');
            
            // Payment information
            $table->decimal('assessment_fee', 10, 2)->default(0)->after('external_applicant_phone');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending')->after('assessment_fee');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->timestamp('payment_date')->nullable()->after('payment_reference');
            $table->text('payment_notes')->nullable()->after('payment_date');
            
            // Make trainee_id nullable since external applicants won't have trainee records
            $table->foreignId('trainee_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_type',
                'external_applicant_name',
                'external_applicant_email',
                'external_applicant_phone',
                'assessment_fee',
                'payment_status',
                'payment_method',
                'payment_reference',
                'payment_date',
                'payment_notes'
            ]);
            
            // Make trainee_id required again
            $table->foreignId('trainee_id')->nullable(false)->change();
        });
    }
};
