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
        'character_id',
        'show_character',
        'separator_positions',
    ];

    protected $casts = [
        'start' => 'float',
        'end' => 'float',
        'line_number' => 'integer',
        'show_character' => 'boolean',
        'separator_positions' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
