<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'video_path',
        'timecodes',
        'text_content',
        'json_path',
    ];
}
