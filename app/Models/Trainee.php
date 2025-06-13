<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trainee extends Model
{
    protected $fillable = [
        'uli_number',
        'entry_date',
        'last_name',
        'first_name',
        'middle_name',
        'extension',
        'street_number',
        'barangay',
        'district',
        'city_municipality',
        'province',
        'region',
        'email_facebook',
        'contact_number',
        'nationality',
        'sex',
        'civil_status',
        'employment_status',
        'employment_type',
        'birth_month',
        'birth_day',
        'birth_year',
        'age',
        'birthplace_city',
        'birthplace_province',
        'birthplace_region',
        'education',
        'parent_guardian_name',
        'parent_guardian_address',
        'classification',
        'classification_others',
        'disability_type',
        'disability_causes',
        'course_qualification',
        'scholarship_package',
        'requirements',
        'status',
        'payment_status'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'education' => 'array',
        'classification' => 'array',
        'disability_type' => 'array',
        'disability_causes' => 'array',
        'requirements' => 'array',
    ];
}
