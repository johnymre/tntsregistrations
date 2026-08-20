<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoneId extends Model
{
    protected $table = 'done_ids';

    protected $fillable = ['student_id'];
}
