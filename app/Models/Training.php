<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $fillable = [
        'program_id',
        'trainer_id',
        'start_date',
        'end_date',
        'location',
        'description',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }
}
