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
        'mobile',
        'course_key',
        'course_title',
        'price',
        'authority',
        'status',
        'ref_id',
    ];
}
