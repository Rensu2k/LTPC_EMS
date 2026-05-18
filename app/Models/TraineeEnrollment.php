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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class TraineeEnrollment extends Model
{
    protected $fillable = [
        'trainee_id',
        'program_id', 
        'batch',
        'enrollment_date',
        'date_started',
        'completion_date',
        'date_ended',
        'status',
        'payment_status',
        'enrollment_fee',
        'payment_method',
        'payment_reference',
        'payment_date',
        'payment_notes',
        'notes'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'date_started' => 'date',
        'completion_date' => 'date',
        'date_ended' => 'date',
        'payment_date' => 'datetime',
        'enrollment_fee' => 'decimal:2',
    ];

    /**
     * Get the trainee that owns the enrollment
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class);
    }

    /**
     * Get the program that owns the enrollment
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    /**
     * Scope to get active enrollments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get completed enrollments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get paid enrollments
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Check if enrollment is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if enrollment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is completed
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Mark enrollment as completed
     */
    public function markAsCompleted($completionDate = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'completion_date' => $completionDate ?? now()->toDateString()
        ]);
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid($paymentMethod = null, $paymentReference = null, $paymentNotes = null): bool
    {
        $updateData = [
            'payment_status' => 'paid',
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'payment_date' => now(),
            'payment_notes' => $paymentNotes
        ];

        if ($this->status === 'pending') {
            $updateData['status'] = 'active';
        }

        return $this->update($updateData);
    }

    /**
     * Handle automatic status changes based on payment
     */
    public function handlePaymentStatusChange(): void
    {
        if ($this->payment_status === 'paid' && $this->status === 'pending') {
            $this->update(['status' => 'active']);
            Log::info("Enrollment {$this->id} activated due to payment completion");
        }
        
        if ($this->payment_status !== 'paid' && $this->status === 'active') {
            $this->update(['status' => 'pending']);
            Log::info("Enrollment {$this->id} set to pending due to payment issue");
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($enrollment) {
            if ($enrollment->wasChanged('payment_status')) {
                $enrollment->handlePaymentStatusChange();
            }
        });
    }
} 