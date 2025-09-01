<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timecode extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'line_number',
        'start',
        'end',
        'text',
    ];

    protected $casts = [
        'start' => 'float',
        'end' => 'float',
        'line_number' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
