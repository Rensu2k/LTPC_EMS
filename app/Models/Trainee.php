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
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class Trainee extends Model
{
    protected $fillable = [
        'uli_number',
        'entry_date',
        'last_name',
        'first_name',
        'middle_name',
        'extension',
        'street_number',
        'barangay',
        'district',
        'city_municipality',
        'province',
        'region',
        'email_facebook',
        'contact_number',
        'nationality',
        'sex',
        'civil_status',
        'employment_status',
        'employment_type',
        'birth_month',
        'birth_day',
        'birth_year',
        'age',
        'birthplace_city',
        'birthplace_province',
        'birthplace_region',
        'education',
        'parent_guardian_name',
        'parent_guardian_address',
        'classification',
        'classification_others',
        'disability_type',
        'disability_causes',
        'program_qualification',
        'batch',
        'scholarship_package',
        'requirements',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'payment_date' => 'datetime',
        'education' => 'array',
        'classification' => 'array',
        'disability_type' => 'array',
        'disability_causes' => 'array',
        'requirements' => 'array',
    ];

    /**
     * Get all enrollments for this trainee
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(TraineeEnrollment::class);
    }

    /**
     * Get active enrollments for this trainee
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(TraineeEnrollment::class)->where('status', 'active');
    }

    /**
     * Get completed enrollments for this trainee
     */
    public function completedEnrollments(): HasMany
    {
        return $this->hasMany(TraineeEnrollment::class)->where('status', 'completed');
    }

    /**
     * Get current active enrollment (if any)
     */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(TraineeEnrollment::class)->where('status', 'active')->latest();
    }

    /**
     * Get all assessments for this trainee
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Get full name of trainee
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name . ' ' . $this->last_name;
        if ($this->middle_name) {
            $name = $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
        }
        if ($this->extension) {
            $name .= ' ' . $this->extension;
        }
        return $name;
    }

    /**
     * Check if trainee has completed a specific program
     */
    public function hasCompletedProgram($programId): bool
    {
        return $this->enrollments()
            ->where('program_id', $programId)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Check if trainee is currently enrolled in a program
     */
    public function isEnrolledInProgram($programId): bool
    {
        return $this->enrollments()
            ->where('program_id', $programId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get all programs this trainee has been enrolled in
     */
    public function programs()
    {
        return $this->belongsToMany(Program::class, 'trainee_enrollments', 'trainee_id', 'program_id')
            ->withPivot(['batch', 'enrollment_date', 'completion_date', 'status', 'payment_status'])
            ->withTimestamps();
    }

    /**
     * Enroll trainee in a new program
     */
    public function enrollInProgram($programId, $batchNumber = null, $enrollmentFee = null, $maintainScholarship = null): TraineeEnrollment
    {
        $program = Program::find($programId);
        if (!$program) {
            throw new \Exception("Program not found");
        }

        if ($this->isEnrolledInProgram($programId)) {
            throw new \Exception("Trainee is already enrolled in this program");
        }

        if ($this->hasCompletedProgram($programId)) {
            throw new \Exception("Trainee has already completed this program and cannot re-enroll");
        }

        if (!$batchNumber) {
            $batchNumber = $program->getNextAvailableBatch();
        }

        if ($enrollmentFee === null) {
            $enrollmentFee = $program->enrollment_fee ?? 0;
        }

        $paymentStatus = 'unpaid';
        $paymentMethod = null;
        $paymentReference = null;
        $paymentNotes = null;

        $shouldApplyScholarship = $this->scholarship_package && ($maintainScholarship !== false);

        if ($shouldApplyScholarship) {
            $paymentStatus = 'paid';
            $paymentMethod = 'scholarship_exemption';
            $paymentReference = 'SCHOLAR-' . strtoupper($this->scholarship_package) . '-' . time();
            $paymentNotes = "Payment exempted due to {$this->scholarship_package} scholarship package";
            $enrollmentFee = 0;
        }

        return TraineeEnrollment::create([
            'trainee_id' => $this->id,
            'program_id' => $programId,
            'batch' => $batchNumber,
            'enrollment_date' => now()->toDateString(),
            'date_started' => $program->start_date,
            'date_ended' => $program->end_date,
            'status' => $paymentStatus === 'paid' ? 'active' : 'pending', // Active if paid, pending if unpaid
            'payment_status' => $paymentStatus,
            'enrollment_fee' => $enrollmentFee,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'payment_date' => $paymentStatus === 'paid' ? now() : null,
            'payment_notes' => $paymentNotes
        ]);
    }

    /**
     * Automatically enroll trainee when conditions are met
     */
    public function handleAutoEnrollment(): void
    {
        if ($this->status !== 'active' || $this->payment_status !== 'paid') {
            return;
        }

        if (!$this->program_qualification) {
            return;
        }

        $program = Program::where('name', $this->program_qualification)->first();
        if (!$program) {
            return;
        }

        if ($this->isEnrolledInProgram($program->program_id)) {
            return;
        }

        try {
            $enrollment = $this->enrollInProgram($program->program_id, null, null); // Use default program fee
            
            $enrollment->update([
                'status' => $this->status, // Match trainee's status (should be 'active')
                'payment_status' => $this->payment_status, // Match trainee's payment status (should be 'paid')
                'payment_method' => $this->payment_method,
                'payment_reference' => $this->payment_reference,
                'payment_date' => $this->payment_status === 'paid' ? ($this->payment_date ?: now()) : null,
                'payment_notes' => $this->payment_notes
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to auto-enroll trainee {$this->id}: " . $e->getMessage());
        }
    }

    /**
     * Update payment status and handle auto-enrollment
     */
    public function updatePaymentStatus($paymentStatus, $paymentMethod = null, $paymentReference = null, $paymentNotes = null): bool
    {
        $updateData = ['payment_status' => $paymentStatus];

        if ($paymentStatus === 'paid') {
            $updateData['payment_method'] = $paymentMethod;
            $updateData['payment_reference'] = $paymentReference;
            $updateData['payment_notes'] = $paymentNotes;
        }

        $result = $this->forceFill($updateData)->save();
        
        if ($result) {
            $this->handleAutoEnrollment();
        }

        return $result;
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($trainee) {
            $trainee->handleAutoEnrollment();
        });

        static::updated(function ($trainee) {
            if ($trainee->wasChanged('status')) {
                $activeEnrollments = $trainee->enrollments()->where('status', 'active')->get();
                
                foreach ($activeEnrollments as $enrollment) {
                    $enrollmentData = ['status' => $trainee->status];
                    
                    if ($trainee->status === 'completed') {
                        $enrollmentData['completion_date'] = now()->toDateString();
                    }
                    
                    $enrollment->update($enrollmentData);
                }
            }
            
            if ($trainee->wasChanged(['status', 'payment_status'])) {
                $trainee->handleAutoEnrollment();
            }
        });
    }
}
