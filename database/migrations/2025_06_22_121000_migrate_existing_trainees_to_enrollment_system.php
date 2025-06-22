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
        // Only proceed if trainees table exists and has data
        if (!Schema::hasTable('trainees') || !Schema::hasTable('programs') || !Schema::hasTable('trainee_enrollments')) {
            return;
        }

        // Check if we've already migrated (avoid double migration)
        $existingMigration = DB::table('trainee_enrollments')
            ->where('notes', 'Migrated from legacy trainee system')
            ->first();
            
        if ($existingMigration) {
            return; // Already migrated
        }

        // Migrate existing trainees to the new enrollment system
        $trainees = DB::table('trainees')->get();
        
        foreach ($trainees as $trainee) {
            // Find the corresponding program
            $program = DB::table('programs')
                ->where('name', $trainee->program_qualification)
                ->first();
            
            if ($program) {
                // Determine payment status and method based on scholarship
                $paymentStatus = 'unpaid';
                $paymentMethod = null;
                $paymentReference = null;
                $paymentNotes = null;
                $enrollmentFee = $program->enrollment_fee ?? 0;
                $paymentDate = null;
                
                if (!empty($trainee->scholarship_package)) {
                    $paymentStatus = 'paid';
                    $paymentMethod = 'scholarship_exemption';
                    $paymentReference = 'LEGACY-SCHOLAR-' . strtoupper($trainee->scholarship_package) . '-' . time();
                    $paymentNotes = "Migrated from legacy system. Payment exempted due to {$trainee->scholarship_package} scholarship package";
                    $enrollmentFee = 0;
                    $paymentDate = $trainee->entry_date ?? now();
                } elseif ($trainee->payment_status === 'paid') {
                    $paymentStatus = 'paid';
                    $paymentMethod = 'legacy_payment';
                    $paymentReference = 'LEGACY-PAID-' . $trainee->id . '-' . time();
                    $paymentNotes = "Migrated from legacy system. Payment was already completed.";
                    $paymentDate = $trainee->entry_date ?? now();
                }
                
                // Create enrollment record
                DB::table('trainee_enrollments')->insert([
                    'trainee_id' => $trainee->id,
                    'program_id' => $program->program_id,
                    'batch' => $trainee->batch ?? 1,
                    'enrollment_date' => $trainee->entry_date ?? now()->toDateString(),
                    'completion_date' => $trainee->status === 'completed' ? now()->toDateString() : null,
                    'status' => $trainee->status,
                    'payment_status' => $paymentStatus,
                    'enrollment_fee' => $enrollmentFee,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                    'payment_date' => $paymentDate,
                    'payment_notes' => $paymentNotes,
                    'notes' => 'Migrated from legacy trainee system',
                    'created_at' => $trainee->created_at ?? now(),
                    'updated_at' => $trainee->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all enrollment records that were created during migration
        DB::table('trainee_enrollments')
            ->where('notes', 'Migrated from legacy trainee system')
            ->delete();
    }
}; 