<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    protected $fillable = [
        'full_name',
        'program',
        'email',
        'phone',
        'biography',
        'availability_schedule',
        'status'
    ];

    protected $casts = [
        'availability_schedule' => 'array',
    ];

    /**
     * Get the trainer's name (alias for full_name to match frontend)
     */
    public function getNameAttribute()
    {
        return $this->full_name;
    }

    /**
     * Get the trainer's avatar initials
     */
    public function getAvatarAttribute()
    {
        return $this->full_name
            ? implode('', array_map(fn($n) => $n[0], explode(' ', $this->full_name)))
            : '';
    }

    /**
     * Get courses assigned to this trainer
     */
    public function getAssignedCoursesAttribute()
    {
        return \App\Models\Course::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get count of active courses assigned to this trainer
     */
    public function getActiveCoursesCountAttribute()
    {
        return \App\Models\Course::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get total trainees for courses assigned to this trainer
     */
    public function getTotalTraineesCountAttribute()
    {
        $assignedCourses = \App\Models\Course::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->pluck('name');

        return \App\Models\Trainee::whereIn('course_qualification', $assignedCourses)
            ->count();
    }

    /**
     * Get active trainees for courses assigned to this trainer
     */
    public function getActiveTraineesCountAttribute()
    {
        $assignedCourses = \App\Models\Course::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->pluck('name');

        return \App\Models\Trainee::whereIn('course_qualification', $assignedCourses)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get completed trainees for courses assigned to this trainer
     */
    public function getCompletedTraineesCountAttribute()
    {
        $assignedCourses = \App\Models\Course::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->pluck('name');

        return \App\Models\Trainee::whereIn('course_qualification', $assignedCourses)
            ->where('status', 'completed')
            ->count();
    }
}
