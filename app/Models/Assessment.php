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
        'score',
        'max_score',
        'course_id',
        'trainee_id',
        'trainer_id',
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
        'score' => 'integer',
        'max_score' => 'integer',
        'assessment_fee' => 'decimal:2',
    ];

    /**
     * Get the course associated with the assessment
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
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
     * Get the percentage score
     */
    public function getPercentageAttribute()
    {
        if ($this->max_score > 0 && $this->score !== null) {
            return round(($this->score / $this->max_score) * 100, 2);
        }
        return null;
    }

    /**
     * Get the grade based on percentage
     */
    public function getGradeAttribute()
    {
        $percentage = $this->percentage;
        if ($percentage === null) return 'N/A';

        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
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
