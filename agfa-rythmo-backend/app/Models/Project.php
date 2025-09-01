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
        'rythmo_lines_count',
    ];

    protected $casts = [
        'rythmo_lines_count' => 'integer',
    ];

    public function sceneChanges()
    {
        return $this->hasMany(SceneChange::class);
    }

    public function timecodes()
    {
        return $this->hasMany(Timecode::class)->orderBy('line_number')->orderBy('start');
    }

    public function timecodesForLine($lineNumber)
    {
        return $this->timecodes()->where('line_number', $lineNumber);
    }
}
