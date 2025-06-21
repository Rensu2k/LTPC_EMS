<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingResult extends Model
{
    protected $fillable = [
        'training_id',
        'trainee_id',
        'completion_status',
        'date_completed',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function trainee()
    {
        return $this->belongsTo(Trainee::class);
    }
}
