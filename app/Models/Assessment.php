<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'result',
        'original_assessment_id',
        'attempt_number',
        'is_reassessment',
        'program_id',
        'trainee_id',
        'trainer_id',
        'assessor_name',
        'assessment_date',
        'applicant_type',
        'external_applicant_name',
        'external_applicant_email',
        'external_applicant_phone',
        'assessment_fee',
        'payment_status',
        'payment_method',
        'payment_reference',
        'payment_date',
        'payment_notes',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'payment_date' => 'datetime',
        'assessment_fee' => 'decimal:2',
        'is_reassessment' => 'boolean',
    ];

    /**
     * Get the program associated with the assessment
     */
    public function program()
    {
        return $this->belongsTo(\App\Models\Program::class, 'program_id', 'program_id');
    }

    /**
     * Get the trainee associated with the assessment
     */
    public function trainee()
    {
        return $this->belongsTo(Trainee::class);
    }

    /**
     * Get the trainer associated with the assessment
     */
    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    /**
     * Get the original assessment that this is a re-assessment of
     */
    public function originalAssessment()
    {
        return $this->belongsTo(Assessment::class, 'original_assessment_id');
    }

    /**
     * Get all re-assessments of this assessment
     */
    public function reassessments()
    {
        return $this->hasMany(Assessment::class, 'original_assessment_id');
    }

    /**
     * Check if the assessment is competent
     */
    public function isCompetent()
    {
        return $this->result === 'competent';
    }

    /**
     * Check if the assessment is not yet competent
     */
    public function isNotYetCompetent()
    {
        return $this->result === 'not_yet_competent';
    }

    /**
     * Check if the trainee was absent
     */
    public function isAbsent()
    {
        return $this->result === 'absent';
    }

    /**
     * Check if the assessment can be re-assessed (failed or absent assessments can be re-assessed)
     */
    public function canBeReassessed()
    {
        // Only allow re-assessment if the result is 'not_yet_competent' or 'absent'
        if (!($this->result === 'not_yet_competent' || $this->result === 'absent')) {
            return false;
        }

        // Check if this is a re-assessment - if so, use the original assessment ID
        $originalAssessmentId = $this->is_reassessment ? $this->original_assessment_id : $this->id;

        // Check if there's already a pending re-assessment for this original assessment
        $hasPendingReassessment = Assessment::where('original_assessment_id', $originalAssessmentId)
            ->where('status', 'pending')
            ->exists();

        return !$hasPendingReassessment;
    }

    /**
     * Check if re-enrollment is required
     * Note: Unlimited reassessments are now allowed, so this always returns false
     */
    public function requiresReenrollment()
    {
        // Unlimited reassessments allowed - re-enrollment never required
        return false;
    }

    /**
     * Check if the assessment is graded (completed status indicates graded)
     */
    public function isGraded()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the assessment is editable
     */
    public function isEditable()
    {
        return !$this->isGraded() || $this->status === 'pending';
    }

    /**
     * Check if the assessment can be deleted
     */
    public function isDeletable()
    {
        // Cannot delete if assessment is graded (completed)
        if ($this->isGraded()) {
            return false;
        }

        // Cannot delete if payment has been made
        if ($this->payment_status === 'paid') {
            return false;
        }

        return true;
    }

    /**
     * Get the result status for display
     */
    public function getResultStatusAttribute()
    {
        switch ($this->result) {
            case 'competent':
                return 'Competent';
            case 'not_yet_competent':
                return 'Not Yet Competent';
            case 'absent':
                return 'Absent';
            default:
                return 'Not Evaluated';
        }
    }

    /**
     * Get the result status color class
     */
    public function getResultColorAttribute()
    {
        switch ($this->result) {
            case 'competent':
                return 'bg-green-100 text-green-800';
            case 'not_yet_competent':
                return 'bg-red-100 text-red-800';
            case 'absent':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-blue-100 text-blue-800';
        }
    }

    /**
     * Check if this is the first attempt for a trainee/applicant
     */
    public function isFirstAttempt()
    {
        return !$this->is_reassessment && $this->attempt_number === 1;
    }

    /**
     * Check if scholar payment exemption should apply
     * Only applies to first attempt for enrolled trainees with scholarships
     */
    public function shouldApplyScholarExemption()
    {
        // Only for enrolled trainees
        if ($this->applicant_type !== 'enrolled_trainee' || !$this->trainee) {
            return false;
        }

        // Only if trainee has scholarship
        if (empty($this->trainee->scholarship_package)) {
            return false;
        }

        // Only for first attempt
        return $this->isFirstAttempt();
    }

    /**
     * Get the applicant name (either trainee name or external applicant name)
     */
    public function getApplicantNameAttribute()
    {
        if ($this->applicant_type === 'external_applicant') {
            return $this->external_applicant_name;
        }
        
        if ($this->trainee) {
            return $this->trainee->first_name . ' ' . $this->trainee->last_name;
        }
        
        return 'N/A';
    }

    /**
     * Check if payment is required for this assessment
     */
    public function getPaymentRequiredAttribute()
    {
        return $this->assessment_fee > 0;
    }

    /**
     * Check if payment is completed
     */
    public function getPaymentCompletedAttribute()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Scope to get assessments for completed trainees only
     */
    public function scopeForCompletedTrainees($query)
    {
        return $query->where(function ($q) {
            $q->where('applicant_type', 'external_applicant')
              ->orWhereHas('trainee', function ($traineeQuery) {
                  $traineeQuery->where('status', 'completed');
              });
        });
    }
}
