<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Vérifier si l'utilisateur est administrateur
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est un utilisateur standard
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Relation : projets créés par l'utilisateur
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    /**
     * Relation : projets où l'utilisateur est collaborateur
     */
    public function collaborativeProjects()
    {
        return $this->belongsToMany(Project::class, 'project_collaborators')
            ->withPivot(['permission', 'created_at'])
            ->withTimestamps();
    }

    /**
     * Obtenir tous les projets accessibles (créés + collaboratifs)
     */
    public function accessibleProjects()
    {
        return Project::where(function ($query) {
            $query->where('user_id', $this->id)
                  ->orWhereHas('collaborators', function ($q) {
                      $q->where('user_id', $this->id);
                  });
        });
    }

    /**
     * Relation : invitations envoyées par cet utilisateur
     */
    public function sentInvitations()
    {
        return $this->hasMany(ProjectInvitation::class, 'invited_by');
    }

    /**
     * Relation : invitations reçues par cet utilisateur
     */
    public function receivedInvitations()
    {
        return $this->hasMany(ProjectInvitation::class, 'invited_user_id');
    }

    /**
     * Obtenir les invitations en attente pour cet utilisateur
     */
    public function pendingInvitations()
    {
        return $this->receivedInvitations()
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            });
    }
}
