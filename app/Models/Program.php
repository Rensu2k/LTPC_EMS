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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Program extends Model
{
    protected $primaryKey = "program_id";
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = [
        "program_id",
        "name",
        "description",
        "duration",
        "prerequisites",
        "enrollment_fee",
        "assigned_trainers",
        "status",
        "max_enrollments",
        "current_batch",
        "start_date",
        "end_date",
    ];

    protected $casts = [
        "assigned_trainers" => "array",
        "start_date" => "date",
        "end_date" => "date",
    ];

    protected $attributes = [
        "max_enrollments" => 25,
        "current_batch" => 1,
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Program $program) {
            if (empty($program->program_id)) {
                $program->program_id =
                    "PROG-" .
                    strtoupper(
                        substr(
                            preg_replace("/[^A-Za-z0-9]/", "", $program->name),
                            0,
                            8,
                        ),
                    ) .
                    "-" .
                    time();
            }
        });
    }

    /**
     * Get trainers assigned to this program
     */
    /**
     * Get trainers assigned to this program.
     * Note: This is NOT an Eloquent relationship (returns a Collection, not a query builder).
     * It cannot be eager-loaded. Use Program::with() patterns for bulk loading.
     */
    public function getTrainers()
    {
        if (!$this->assigned_trainers) {
            return collect([]);
        }

        return \App\Models\Trainer::whereIn(
            "id",
            $this->assigned_trainers,
        )->get();
    }

    /**
     * Get all enrollments for this program
     */
    public function enrollments()
    {
        return $this->hasMany(
            TraineeEnrollment::class,
            "program_id",
            "program_id",
        );
    }

    /**
     * Get active enrollments for this program
     */
    public function activeEnrollments()
    {
        return $this->hasMany(
            TraineeEnrollment::class,
            "program_id",
            "program_id",
        )->where("status", "active");
    }

    /**
     * Get trainees enrolled in this program (through enrollment system)
     */
    public function trainees()
    {
        return $this->belongsToMany(
            Trainee::class,
            "trainee_enrollments",
            "program_id",
            "trainee_id",
        )
            ->withPivot([
                "batch",
                "enrollment_date",
                "completion_date",
                "status",
                "payment_status",
            ])
            ->withTimestamps();
    }

    /**
     * Get active trainees enrolled in this program (through enrollment system)
     */
    public function activeTrainees()
    {
        return $this->belongsToMany(
            Trainee::class,
            "trainee_enrollments",
            "program_id",
            "trainee_id",
        )
            ->wherePivot("status", "active")
            ->withPivot([
                "batch",
                "enrollment_date",
                "completion_date",
                "status",
                "payment_status",
            ])
            ->withTimestamps();
    }

    /**
     * Get legacy trainees (from old system - for backward compatibility)
     */
    public function legacyTrainees()
    {
        return \App\Models\Trainee::where(
            "program_qualification",
            $this->name,
        )->get();
    }

    /**
     * Get legacy active trainees (from old system - for backward compatibility)
     */
    public function legacyActiveTrainees()
    {
        return \App\Models\Trainee::where("program_qualification", $this->name)
            ->where("status", "active")
            ->get();
    }

    /**
     * Get trainees enrolled in this program by batch
     */
    public function traineesByBatch($batch = null)
    {
        $query = \App\Models\Trainee::where(
            "program_qualification",
            $this->name,
        );

        if ($batch !== null) {
            $query->where("batch", $batch);
        }

        return $query->get();
    }

    /**
     * Get enrollment count (only active trainees) - Updated for new system
     * Scalability: Uses DB-level count() instead of loading IDs into memory.
     */
    public function getEnrollmentCountAttribute()
    {
        $newSystemCount = $this->activeEnrollments()->count();

        // Count legacy active trainees excluding those already in new system
        // Uses NOT EXISTS for short-circuit evaluation at scale.
        $programId = $this->program_id;
        $legacyCount = DB::table('trainees')
            ->where('program_qualification', $this->name)
            ->where('status', 'active')
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM trainee_enrollments 
                WHERE trainee_enrollments.trainee_id = trainees.id 
                AND trainee_enrollments.program_id = ?
                AND trainee_enrollments.status = ?
            )', [$programId, 'active'])
            ->count();

        return $newSystemCount + $legacyCount;
    }

    /**
     * Get total enrollment count (all enrollments, all statuses) - Updated for new system
     * Scalability: Uses DB-level count() instead of loading IDs into memory.
     */
    public function getTotalEnrollmentCountAttribute()
    {
        $newSystemCount = $this->enrollments()->count();

        // Count legacy trainees excluding those already in new system
        $programId = $this->program_id;
        $legacyCount = DB::table('trainees')
            ->where('program_qualification', $this->name)
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM trainee_enrollments 
                WHERE trainee_enrollments.trainee_id = trainees.id 
                AND trainee_enrollments.program_id = ?
            )', [$programId])
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
        $lastBatch = \App\Models\Trainee::where(
            "program_qualification",
            $this->name,
        )->max("batch");

        return $lastBatch ? $lastBatch + 1 : 1;
    }

    /**
     * Get enrollment count for a specific batch (both new system and legacy trainees).
     *
     * Counts both 'active' AND 'pending' enrollments so that trainees awaiting
     * payment still reserve their batch slot. Without this, pending trainees
     * are invisible to the batch counter and the system keeps assigning new
     * trainees to an already-full batch.
     */
    public function getEnrollmentCountForBatch($batch)
    {
        // Count all non-terminal enrollments (active + pending) for this batch.
        // 'completed' and 'dropped' enrollments free their slot.
        // Scalability: Uses DB-level count() instead of loading IDs into memory.
        $newSystemCount = $this->enrollments()
            ->whereIn("status", ["active", "pending"])
            ->where("batch", $batch)
            ->count();

        // Count legacy trainees (active OR pending) for this batch,
        // excluding any trainee already in the enrollment system.
        // Uses NOT EXISTS correlated subquery for short-circuit evaluation.
        $programId = $this->program_id;
        $legacyCount = DB::table('trainees')
            ->where('program_qualification', $this->name)
            ->whereIn('status', ['active', 'pending'])
            ->where('batch', $batch)
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM trainee_enrollments 
                WHERE trainee_enrollments.trainee_id = trainees.id 
                AND trainee_enrollments.program_id = ?
                AND trainee_enrollments.status IN (?, ?)
            )', [$programId, 'active', 'pending'])
            ->count();

        // Log legacy hits to track migration progress — deprecate this path
        // only when evidence shows zero legacy hits over a sustained period.
        if ($legacyCount > 0) {
            Log::info("[LEGACY-BATCH-HIT] Program '{$this->name}' batch {$batch}: {$legacyCount} legacy trainees counted (not yet in enrollment system)");
        }

        return $newSystemCount + $legacyCount;
    }

    /**
     * Get current active batch enrollment count
     */
    public function getCurrentBatchEnrollmentCount()
    {
        return $this->getEnrollmentCountForBatch($this->current_batch);
    }

    /**
     * Check if current batch is full (25 trainees per batch limit)
     */
    public function isCurrentBatchFull()
    {
        return $this->getCurrentBatchEnrollmentCount() >= 25; // 25 trainees per batch
    }

    /**
     * Get the next available batch for new enrollment.
     *
     * Scalability fix: replaces N-loop (each calling 2 queries) with a single
     * GROUP BY aggregation. Uses NOT EXISTS for the legacy trainee check which
     * allows MariaDB to short-circuit (stop scanning as soon as one match is found).
     */
    public function getNextAvailableBatch()
    {
        // Aggregate ALL batch counts in a SINGLE query.
        $batchCounts = $this->enrollments()
            ->whereIn('status', ['active', 'pending'])
            ->select('batch', DB::raw('COUNT(*) as count'))
            ->groupBy('batch')
            ->pluck('count', 'batch');

        // Legacy trainee counts per batch: use NOT EXISTS correlated subquery.
        // NOT EXISTS short-circuits per-row (stops once 1 enrollment is found),
        // whereas LEFT JOIN + IS NULL must fully probe the hash/index.
        $programId = $this->program_id;
        $programName = $this->name;
        $legacyBatchCounts = DB::table('trainees')
            ->where('program_qualification', $programName)
            ->whereIn('status', ['active', 'pending'])
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM trainee_enrollments 
                WHERE trainee_enrollments.trainee_id = trainees.id 
                AND trainee_enrollments.program_id = ?
                AND trainee_enrollments.status IN (?, ?)
            )', [$programId, 'active', 'pending'])
            ->select('batch', DB::raw('COUNT(*) as count'))
            ->groupBy('batch')
            ->pluck('count', 'batch');

        // Log if any legacy trainees were found (migration progress tracking).
        $totalLegacy = $legacyBatchCounts->sum();
        if ($totalLegacy > 0) {
            Log::info("[LEGACY-BATCH-HIT] Program '{$this->name}': {$totalLegacy} total legacy trainees across " . $legacyBatchCounts->count() . " batches during getNextAvailableBatch()");
        }

        // Merge counts: combine new system + legacy for each batch.
        $mergedCounts = [];
        $allBatches = $batchCounts->keys()->merge($legacyBatchCounts->keys())->unique();
        foreach ($allBatches as $b) {
            $mergedCounts[$b] = ($batchCounts[$b] ?? 0) + ($legacyBatchCounts[$b] ?? 0);
        }

        // Find the first batch (starting from current_batch) with capacity.
        $batch = $this->current_batch;
        while (isset($mergedCounts[$batch]) && $mergedCounts[$batch] >= 25) {
            $batch++;
        }

        return $batch;
    }

    /**
     * Advance current_batch to the next non-full batch if the current one is full
     */
    public function advanceBatchIfFull()
    {
        if ($this->isCurrentBatchFull()) {
            $nextBatch = $this->getNextAvailableBatch();
            $this->update(["current_batch" => $nextBatch]);
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
            $query->where("batch", $batch);
        }
        return $query->count();
    }

    /**
     * Get available slots for enrollment in current batch
     */
    public function getAvailableSlotsAttribute()
    {
        return 25 - $this->getCurrentBatchEnrollmentCount();
    }

    /**
     * Get completed trainees count
     */
    public function getCompletedTraineesCountAttribute()
    {
        $newSystemCount = $this->enrollments()
            ->where("status", "completed")
            ->count();

        // Count legacy completed trainees excluding those already in new system
        $programId = $this->program_id;
        $legacyCount = DB::table('trainees')
            ->where('program_qualification', $this->name)
            ->where('status', 'completed')
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM trainee_enrollments 
                WHERE trainee_enrollments.trainee_id = trainees.id 
                AND trainee_enrollments.program_id = ?
            )', [$programId])
            ->count();

        return $newSystemCount + $legacyCount;
    }

    /**
     * Get dropped trainees count
     */
    public function getDroppedTraineesCountAttribute()
    {
        $newSystemCount = $this->enrollments()
            ->where("status", "dropped")
            ->count();

        // Count legacy dropped trainees excluding those already in new system
        $programId = $this->program_id;
        $legacyCount = DB::table('trainees')
            ->where('program_qualification', $this->name)
            ->where('status', 'dropped')
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM trainee_enrollments 
                WHERE trainee_enrollments.trainee_id = trainees.id 
                AND trainee_enrollments.program_id = ?
            )', [$programId])
            ->count();

        return $newSystemCount + $legacyCount;
    }

    /**
     * Get pending trainees count
     */
    public function getPendingTraineesCountAttribute()
    {
        $newSystemCount = $this->enrollments()
            ->where("status", "pending")
            ->count();

        // Count legacy pending trainees excluding those already in new system
        $programId = $this->program_id;
        $legacyCount = DB::table('trainees')
            ->where('program_qualification', $this->name)
            ->where('status', 'pending')
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM trainee_enrollments 
                WHERE trainee_enrollments.trainee_id = trainees.id 
                AND trainee_enrollments.program_id = ?
            )', [$programId])
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
        return round(($this->getCurrentBatchEnrollmentCount() / 25) * 100, 2);
    }

    /**
     * Get all unique batches for this program
     */
    public function getBatches()
    {
        // Get batches from the new enrollment system (preferred)
        $enrollmentBatches = $this->enrollments()
            ->distinct()
            ->pluck("batch");

        // Also get legacy batches from trainees table
        $legacyBatches = \App\Models\Trainee::where("program_qualification", $this->name)
            ->distinct()
            ->pluck("batch");

        return $enrollmentBatches->merge($legacyBatches)
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Get trainees count by status
     */
    public function getTraineesByStatus($status)
    {
        return \App\Models\Trainee::where("program_qualification", $this->name)
            ->where("status", $status)
            ->count();
    }

    /**
     * Scope to get active programs
     */
    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    /**
     * Scope to get active programs (removed slot limitation as programs can have unlimited trainees across batches)
     */
    public function scopeWithAvailableSlots($query)
    {
        return $query->where("status", "active");
    }
}
