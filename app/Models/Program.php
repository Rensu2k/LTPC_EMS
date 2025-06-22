<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $primaryKey = 'program_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'program_id',
        'name',
        'description',
        'duration',
        'prerequisites',
        'enrollment_fee',
        'assigned_trainers',
        'status',
        'max_enrollments',
        'current_batch',
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
        'current_batch' => 1,
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Program $program) {
            if (empty($program->program_id)) {
                $program->program_id = 'PROG-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $program->name), 0, 8)) . '-' . time();
            }
        });
    }

    /**
     * Get trainers assigned to this program
     */
    public function trainers()
    {
        if (!$this->assigned_trainers) {
            return collect([]);
        }
        
        return \App\Models\Trainer::whereIn('id', $this->assigned_trainers)->get();
    }

    /**
     * Get all enrollments for this program
     */
    public function enrollments()
    {
        return $this->hasMany(TraineeEnrollment::class, 'program_id', 'program_id');
    }

    /**
     * Get active enrollments for this program
     */
    public function activeEnrollments()
    {
        return $this->hasMany(TraineeEnrollment::class, 'program_id', 'program_id')->where('status', 'active');
    }

    /**
     * Get trainees enrolled in this program (through enrollment system)
     */
    public function trainees()
    {
        return $this->belongsToMany(Trainee::class, 'trainee_enrollments', 'program_id', 'trainee_id')
            ->withPivot(['batch', 'enrollment_date', 'completion_date', 'status', 'payment_status'])
            ->withTimestamps();
    }

    /**
     * Get active trainees enrolled in this program (through enrollment system)
     */
    public function activeTrainees()
    {
        return $this->belongsToMany(Trainee::class, 'trainee_enrollments', 'program_id', 'trainee_id')
            ->wherePivot('status', 'active')
            ->withPivot(['batch', 'enrollment_date', 'completion_date', 'status', 'payment_status'])
            ->withTimestamps();
    }

    /**
     * Get legacy trainees (from old system - for backward compatibility)
     */
    public function legacyTrainees()
    {
        return \App\Models\Trainee::where('program_qualification', $this->name)->get();
    }

    /**
     * Get legacy active trainees (from old system - for backward compatibility)
     */
    public function legacyActiveTrainees()
    {
        return \App\Models\Trainee::where('program_qualification', $this->name)
            ->where('status', 'active')->get();
    }

    /**
     * Get trainees enrolled in this program by batch
     */
    public function traineesByBatch($batch = null)
    {
        $query = \App\Models\Trainee::where('program_qualification', $this->name);
        
        if ($batch !== null) {
            $query->where('batch', $batch);
        }
        
        return $query->get();
    }

    /**
     * Get enrollment count (only active trainees) - Updated for new system
     */
    public function getEnrollmentCountAttribute()
    {
        // Get trainee IDs that are already in the new enrollment system
        $enrolledTraineeIds = $this->activeEnrollments()->pluck('trainee_id')->toArray();
        
        // Count new enrollment system active enrollments
        $newSystemCount = $this->activeEnrollments()->count();
        
        // Count legacy active trainees excluding those already in new system
        $legacyCount = \App\Models\Trainee::where('program_qualification', $this->name)
            ->where('status', 'active')
            ->whereNotIn('id', $enrolledTraineeIds)
            ->count();
        
        return $newSystemCount + $legacyCount;
    }

    /**
     * Get total trainees count (all statuses)
     */
    public function getTotalTraineesCountAttribute()
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
        $lastBatch = \App\Models\Trainee::where('program_qualification', $this->name)
            ->max('batch');
        
        return $lastBatch ? $lastBatch + 1 : 1;
    }

    /**
     * Get current active batch enrollment count
     */
    public function getCurrentBatchEnrollmentCount()
    {
        // Get trainee IDs that are already in the new enrollment system for this batch
        $enrolledTraineeIds = $this->activeEnrollments()
            ->where('batch', $this->current_batch)
            ->pluck('trainee_id')
            ->toArray();
        
        // Count new enrollment system active enrollments for current batch
        $newSystemCount = $this->activeEnrollments()->where('batch', $this->current_batch)->count();
        
        // Count legacy active trainees for current batch excluding those already in new system
        $legacyCount = \App\Models\Trainee::where('program_qualification', $this->name)
            ->where('status', 'active')
            ->where('batch', $this->current_batch)
            ->whereNotIn('id', $enrolledTraineeIds)
            ->count();
        
        return $newSystemCount + $legacyCount;
    }

    /**
     * Check if current batch is full
     */
    public function isCurrentBatchFull()
    {
        return $this->getCurrentBatchEnrollmentCount() >= $this->max_enrollments;
    }

    /**
     * Get the next available batch for new enrollment
     */
    public function getNextAvailableBatch()
    {
        if ($this->isCurrentBatchFull()) {
            return $this->current_batch + 1;
        }
        return $this->current_batch;
    }

    /**
     * Advance to next batch if current is full
     */
    public function advanceBatchIfFull()
    {
        if ($this->isCurrentBatchFull()) {
            $this->increment('current_batch');
            return true;
        }
        return false;
    }

    /**
     * Get enrollment count for the new enrollment system
     */
    public function getNewSystemEnrollmentCount($batch = null)
    {
        $query = $this->activeEnrollments();
        if ($batch !== null) {
            $query->where('batch', $batch);
        }
        return $query->count();
    }

    /**
     * Get available slots for enrollment in current batch
     */
    public function getAvailableSlotsAttribute()
    {
        return $this->max_enrollments - $this->getCurrentBatchEnrollmentCount();
    }

    /**
     * Get completed trainees count
     */
    public function getCompletedTraineesCountAttribute()
    {
        // Get trainee IDs that are already in the new enrollment system
        $enrolledTraineeIds = $this->enrollments()->pluck('trainee_id')->toArray();
        
        // Count new enrollment system completed enrollments
        $newSystemCount = $this->enrollments()->where('status', 'completed')->count();
        
        // Count legacy completed trainees excluding those already in new system
        $legacyCount = \App\Models\Trainee::where('program_qualification', $this->name)
            ->where('status', 'completed')
            ->whereNotIn('id', $enrolledTraineeIds)
            ->count();
        
        return $newSystemCount + $legacyCount;
    }

    /**
     * Get dropped trainees count
     */
    public function getDroppedTraineesCountAttribute()
    {
        // Get trainee IDs that are already in the new enrollment system
        $enrolledTraineeIds = $this->enrollments()->pluck('trainee_id')->toArray();
        
        // Count new enrollment system dropped enrollments
        $newSystemCount = $this->enrollments()->where('status', 'dropped')->count();
        
        // Count legacy dropped trainees excluding those already in new system
        $legacyCount = \App\Models\Trainee::where('program_qualification', $this->name)
            ->where('status', 'dropped')
            ->whereNotIn('id', $enrolledTraineeIds)
            ->count();
        
        return $newSystemCount + $legacyCount;
    }

    /**
     * Get suspended trainees count
     */
    public function getSuspendedTraineesCountAttribute()
    {
        // Get trainee IDs that are already in the new enrollment system
        $enrolledTraineeIds = $this->enrollments()->pluck('trainee_id')->toArray();
        
        // Count new enrollment system suspended enrollments
        $newSystemCount = $this->enrollments()->where('status', 'suspended')->count();
        
        // Count legacy suspended trainees excluding those already in new system
        $legacyCount = \App\Models\Trainee::where('program_qualification', $this->name)
            ->where('status', 'suspended')
            ->whereNotIn('id', $enrolledTraineeIds)
            ->count();
        
        return $newSystemCount + $legacyCount;
    }

    /**
     * Check if current batch is full (program can have unlimited trainees across batches)
     */
    public function getIsFullAttribute()
    {
        return $this->isCurrentBatchFull();
    }

    /**
     * Get enrollment progress percentage for current batch
     */
    public function getEnrollmentProgressAttribute()
    {
        if ($this->max_enrollments == 0) {
            return 0;
        }
        
        return round(($this->getCurrentBatchEnrollmentCount() / $this->max_enrollments) * 100, 2);
    }

    /**
     * Get all unique batches for this program
     */
    public function getBatches()
    {
        return \App\Models\Trainee::where('program_qualification', $this->name)
            ->distinct()
            ->pluck('batch')
            ->sort()
            ->values();
    }

    /**
     * Get trainees count by status
     */
    public function getTraineesByStatus($status)
    {
        return \App\Models\Trainee::where('program_qualification', $this->name)
            ->where('status', $status)
            ->count();
    }

    /**
     * Scope to get active programs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get active programs (removed slot limitation as programs can have unlimited trainees across batches)
     */
    public function scopeWithAvailableSlots($query)
    {
        return $query->where('status', 'active');
    }
} 