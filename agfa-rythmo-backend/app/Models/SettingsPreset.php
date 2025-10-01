<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingsPreset extends Model
{
    protected $table = 'user_settings_presets';

    protected $fillable = [
        'user_id',
        'name',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Relation vers l'utilisateur propriétaire du preset
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si l'utilisateur peut créer un nouveau preset (max 5)
     */
    public static function canCreate(User $user): bool
    {
        return self::where('user_id', $user->id)->count() < 5;
    }
}
