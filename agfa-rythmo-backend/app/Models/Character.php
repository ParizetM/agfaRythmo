<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'color',
        'text_color',
    ];

    protected $casts = [
        'project_id' => 'integer',
    ];

    /**
     * Get the project that owns the character.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the timecodes for the character.
     */
    public function timecodes(): HasMany
    {
        return $this->hasMany(Timecode::class);
    }
}
