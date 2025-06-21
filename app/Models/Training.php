<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $fillable = [
        'course_id',
        'trainer_id',
        'start_date',
        'end_date',
        'location',
        'description',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function results()
    {
        return $this->hasMany(TrainingResult::class);
    }
}
