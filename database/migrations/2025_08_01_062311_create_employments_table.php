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
        Schema::create('employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainee_id')->constrained('trainees')->onDelete('cascade');
            $table->string('company_name');
            $table->string('position_title');
            $table->text('job_description')->nullable();
            $table->date('employment_date');
            $table->enum('status', ['employed', 'not_yet_employed'])->default('not_yet_employed');
            $table->text('notes')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('salary_range')->nullable();
            $table->string('employment_type')->nullable(); // full-time, part-time, contract, etc.
            $table->boolean('is_auto_generated')->default(false); // to track if created automatically from assessment
            $table->foreignId('assessment_id')->nullable()->constrained('assessments')->onDelete('set null'); // link to the assessment that triggered this
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employments');
    }
};
