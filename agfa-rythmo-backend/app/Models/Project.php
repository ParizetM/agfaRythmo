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
        'user_id',
        'project_settings',
    ];

    protected $casts = [
        'rythmo_lines_count' => 'integer',
        'project_settings' => 'array',
    ];

    public function sceneChanges()
    {
        return $this->hasMany(SceneChange::class);
    }

    public function timecodes()
    {
        return $this->hasMany(Timecode::class)->orderBy('line_number')->orderBy('start');
    }

    public function characters()
    {
        return $this->hasMany(Character::class);
    }

    public function timecodesForLine($lineNumber)
    {
        return $this->timecodes()->where('line_number', $lineNumber);
    }

    /**
     * Relation vers le propriétaire du projet
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation vers les collaborateurs du projet
     */
    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'project_collaborators')
            ->withPivot(['permission', 'created_at'])
            ->withTimestamps();
    }

    /**
     * Relation vers les invitations du projet
     */
    public function invitations()
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    /**
     * Vérifier si un utilisateur a accès au projet
     */
    public function hasAccess(User $user): bool
    {
        return $this->user_id === $user->id ||
               $this->collaborators()->where('user_id', $user->id)->exists() ||
               $user->isAdmin();
    }

    /**
     * Vérifier si un utilisateur peut modifier le projet
     */
    public function canModify(User $user): bool
    {
        if ($user->isAdmin() || $this->user_id === $user->id) {
            return true;
        }

        $collaborator = $this->collaborators()
            ->where('user_id', $user->id)
            ->wherePivotIn('permission', ['edit', 'admin'])
            ->first();

        return $collaborator !== null;
    }

    /**
     * Vérifier si un utilisateur peut administrer le projet (inviter/supprimer des collaborateurs)
     */
    public function canAdmin(User $user): bool
    {
        if ($user->isAdmin() || $this->user_id === $user->id) {
            return true;
        }

        $collaborator = $this->collaborators()
            ->where('user_id', $user->id)
            ->wherePivot('permission', 'admin')
            ->first();

        return $collaborator !== null;
    }
}
