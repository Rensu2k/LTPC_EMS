<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'payment_id',
        'type',
        'trainee_name',
        'trainee_id_number',
        'trainee_uli_number',
        'fees',
        'original_fees',
        'total_amount',
        'date_generated',
        'time_generated',
        'status',
        'cancellation_reason',
        'enrollment_id',
        'assessment_id',
        'trainee_model_id',
        'program_id',
    ];

    protected $casts = [
        'fees' => 'array',
        'original_fees' => 'array',
        'total_amount' => 'decimal:2',
        'date_generated' => 'date',
        'time_generated' => 'datetime:H:i',
    ];

    // Relationships
    public function enrollment()
    {
        return $this->belongsTo(TraineeEnrollment::class, 'enrollment_id');
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function trainee()
    {
        return $this->belongsTo(Trainee::class, 'trainee_model_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }
}
