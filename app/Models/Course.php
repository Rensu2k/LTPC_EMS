<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $primaryKey = 'course_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'course_id',
        'name',
        'description',
        'duration',
        'prerequisites',
        'enrollment_fee',
        'assigned_trainers',
        'status',
        'max_enrollments',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'assigned_trainers' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $attributes = [
        'max_enrollments' => 25,
    ];

    /**
     * Get trainers assigned to this course
     */
    public function trainers()
    {
        if (!$this->assigned_trainers) {
            return collect([]);
        }
        
        return \App\Models\Trainer::whereIn('id', $this->assigned_trainers)->get();
    }

    /**
     * Get trainees enrolled in this course
     */
    public function trainees()
    {
        return \App\Models\Trainee::where('course_qualification', $this->name)->get();
    }

    /**
     * Get trainees enrolled in this course by batch
     */
    public function traineesByBatch($batch = null)
    {
        $query = \App\Models\Trainee::where('course_qualification', $this->name);
        
        if ($batch !== null) {
            $query->where('batch', $batch);
        }
        
        return $query->get();
    }

    /**
     * Get enrollment count
     */
    public function getEnrollmentCountAttribute()
    {
        return $this->trainees()->count();
    }

    /**
     * Get enrollment count for a specific batch
     */
    public function getEnrollmentCountByBatch($batch)
    {
        return $this->traineesByBatch($batch)->count();
    }

    /**
     * Get the next available batch for enrollment
     */
    public function getNextBatch()
    {
        $maxEnrollments = $this->max_enrollments ?: 25;
        $batch1Count = $this->getEnrollmentCountByBatch(1);
        
        // If batch 1 is not full, return batch 1
        if ($batch1Count < $maxEnrollments) {
            return 1;
        }
        
        // Otherwise, return batch 2
        return 2;
    }
}
