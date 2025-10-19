<?php

namespace CourseRegistration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'national_id',
        'birth_certificate_number',
        'birth_date',
        'mobile',
        'phone',
        'course_key',
        'course_title',
        'price',
        'authority',
        'status',
        'ref_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];
}
