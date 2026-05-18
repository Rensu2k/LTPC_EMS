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

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::defaultStringLength(191);
        Schema::create('trainees', function (Blueprint $table) {
            $table->id();
            
            $table->string('uli_number')->nullable();
            $table->date('entry_date')->nullable();
            
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('extension')->nullable();
            
            $table->string('street_number')->nullable();
            $table->string('barangay')->nullable();
            $table->string('district')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('region')->nullable();
            
            $table->string('email_facebook')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('nationality')->default('Filipino');
            
            $table->enum('sex', ['male', 'female'])->nullable();
            $table->enum('civil_status', ['single', 'married', 'separated', 'widowed', 'common_law'])->nullable();
            $table->enum('employment_status', ['wage_employed', 'underemployed', 'self_employed', 'unemployed'])->nullable();
            $table->enum('employment_type', ['none', 'casual', 'probationary', 'contractual', 'regular', 'job_order', 'permanent', 'temporary'])->nullable();
            
            $table->string('birth_month')->nullable();
            $table->integer('birth_day')->nullable();
            $table->integer('birth_year')->nullable();
            $table->integer('age')->nullable();
            
            $table->string('birthplace_city')->nullable();
            $table->string('birthplace_province')->nullable();
            $table->string('birthplace_region')->nullable();
            
            $table->json('education')->nullable();
            $table->string('parent_guardian_name')->nullable();
            $table->string('parent_guardian_address')->nullable();
            
            $table->json('classification')->nullable();
            $table->string('classification_others')->nullable();
            $table->json('disability_type')->nullable();
            $table->json('disability_causes')->nullable();
            
            $table->string('course_qualification');
            $table->string('scholarship_package')->nullable();
            
            $table->json('requirements')->nullable();
            
            $table->enum('status', ['active', 'completed', 'dropped', 'pending'])->default('pending');
            $table->enum('payment_status', ['paid', 'unpaid'])->default('unpaid');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainees');
    }
};
