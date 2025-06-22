<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    protected $fillable = [
        'full_name',
        'expertise',
        'email',
        'phone',
        'biography',
        'availability_schedule',
        'status'
    ];

    protected $casts = [
        'availability_schedule' => 'array',
        'expertise' => 'array',
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
     * Get the trainer's expertise as a formatted string
     */
    public function getExpertiseStringAttribute()
    {
        if (!$this->expertise || empty($this->expertise)) {
            return 'No expertise specified';
        }
        
        return implode(', ', $this->expertise);
    }

    /**
     * Check if trainer has specific expertise
     */
    public function hasExpertise($expertiseName)
    {
        if (!$this->expertise || empty($this->expertise)) {
            return false;
        }
        
        return in_array($expertiseName, $this->expertise);
    }

    /**
     * Add expertise to trainer
     */
    public function addExpertise($expertiseName)
    {
        $currentExpertise = $this->expertise ?? [];
        
        // Ensure it's an array
        if (!is_array($currentExpertise)) {
            $currentExpertise = [];
        }
        
        if (!in_array($expertiseName, $currentExpertise)) {
            $currentExpertise[] = $expertiseName;
            $this->expertise = $currentExpertise;
            $this->save();
        }
        
        return $this;
    }

    /**
     * Remove expertise from trainer
     */
    public function removeExpertise($expertiseName)
    {
        $currentExpertise = $this->expertise ?? [];
        
        // Ensure it's an array
        if (!is_array($currentExpertise)) {
            $currentExpertise = [];
        }
        
        $currentExpertise = array_filter($currentExpertise, fn($e) => $e !== $expertiseName);
        
        $this->expertise = array_values($currentExpertise);
        $this->save();
        
        return $this;
    }

    /**
     * Get programs assigned to this trainer
     */
    public function getAssignedProgramsAttribute()
    {
        return \App\Models\Program::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get count of active programs assigned to this trainer
     */
    public function getActiveProgramsCountAttribute()
    {
        return \App\Models\Program::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get total trainees for programs assigned to this trainer
     */
    public function getTotalTraineesCountAttribute()
    {
        $assignedPrograms = \App\Models\Program::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->pluck('name');

        return \App\Models\Trainee::whereIn('program_qualification', $assignedPrograms)
            ->count();
    }

    /**
     * Get active trainees for programs assigned to this trainer
     */
    public function getActiveTraineesCountAttribute()
    {
        $assignedPrograms = \App\Models\Program::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->pluck('name');

        return \App\Models\Trainee::whereIn('program_qualification', $assignedPrograms)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get completed trainees for programs assigned to this trainer
     */
    public function getCompletedTraineesCountAttribute()
    {
        $assignedPrograms = \App\Models\Program::whereJsonContains('assigned_trainers', $this->id)
            ->where('status', 'active')
            ->pluck('name');

        return \App\Models\Trainee::whereIn('program_qualification', $assignedPrograms)
            ->where('status', 'completed')
            ->count();
    }
}
