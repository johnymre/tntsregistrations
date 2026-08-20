<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = [
        'name', 'school_year', 'grade_level', 'strand',
        'adviser_name', 'room', 'capacity', 'enrolled_count',
    ];
}
