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
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('trainees') || !Schema::hasTable('programs') || !Schema::hasTable('trainee_enrollments')) {
            return;
        }

        $existingMigration = DB::table('trainee_enrollments')
            ->where('notes', 'Migrated from legacy trainee system')
            ->first();
            
        if ($existingMigration) {
            return; // Already migrated
        }

        $trainees = DB::table('trainees')->get();
        
        foreach ($trainees as $trainee) {
            $program = DB::table('programs')
                ->where('name', $trainee->program_qualification)
                ->first();
            
            if ($program) {
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
        DB::table('trainee_enrollments')
            ->where('notes', 'Migrated from legacy trainee system')
            ->delete();
    }
}; 