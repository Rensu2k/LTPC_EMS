<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name',
        'description',
        'duration',
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
     * Get enrollment count
     */
    public function getEnrollmentCountAttribute()
    {
        return $this->trainees()->count();
    }
}
