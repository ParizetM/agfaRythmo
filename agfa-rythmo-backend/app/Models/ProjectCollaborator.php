<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectCollaborator extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'permission'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation vers le projet
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relation vers l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = ['read', 'write', 'admin'];
        $userLevel = array_search($this->permission, $permissions);
        $requiredLevel = array_search($permission, $permissions);

        return $userLevel !== false && $requiredLevel !== false && $userLevel >= $requiredLevel;
    }
}
