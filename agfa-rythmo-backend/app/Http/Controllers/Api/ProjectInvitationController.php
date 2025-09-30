<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\ProjectInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProjectInvitationController extends Controller
{
    /**
     * Lister les invitations en attente d'un projet (pour le propriétaire)
     */
    public function projectInvitations(Project $project)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur peut administrer le projet
        if (!$project->canAdmin($user)) {
            return response()->json(['message' => 'Droits insuffisants'], 403);
        }

        $invitations = ProjectInvitation::where('project_id', $project->id)
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->with(['invitedUser'])
            ->get()
            ->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'invited_user' => [
                        'id' => $invitation->invitedUser->id,
                        'name' => $invitation->invitedUser->name,
                        'email' => $invitation->invitedUser->email,
                    ],
                    'permission' => $invitation->permission,
                    'created_at' => $invitation->created_at->toISOString(),
                    'expires_at' => $invitation->expires_at?->toISOString(),
                ];
            });

        return response()->json($invitations);
    }

    /**
     * Lister les invitations en attente pour l'utilisateur connecté
     */
    public function index()
    {
        $user = Auth::user();

        $invitations = ProjectInvitation::where('invited_user_id', $user->id)
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->with(['project.owner', 'invitedBy'])
            ->get()
            ->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'project' => [
                        'id' => $invitation->project->id,
                        'name' => $invitation->project->name,
                        'description' => $invitation->project->description,
                    ],
                    'invited_by' => [
                        'id' => $invitation->invitedBy->id,
                        'name' => $invitation->invitedBy->name,
                        'email' => $invitation->invitedBy->email,
                    ],
                    'permission' => $invitation->permission,
                    'message' => $invitation->message,
                    'created_at' => $invitation->created_at->toISOString(),
                    'expires_at' => $invitation->expires_at?->toISOString(),
                ];
            });

        return response()->json($invitations);
    }

    /**
     * Créer une nouvelle invitation
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'user_email' => 'required|email|exists:users,email',
            'permission' => 'required|in:edit,admin',
            'message' => 'nullable|string|max:500',
        ]);

        $project = Project::findOrFail($request->project_id);
        $invitedUser = User::where('email', $request->user_email)->firstOrFail();
        $invitedBy = Auth::user();

        // Vérifier que l'utilisateur peut administrer le projet
        if (!$project->canAdmin($invitedBy)) {
            return response()->json(['message' => 'Droits insuffisants pour inviter des collaborateurs'], 403);
        }

        // Vérifier que l'utilisateur invité n'est pas le propriétaire
        if ($project->user_id === $invitedUser->id) {
            throw ValidationException::withMessages([
                'user_email' => ['Le propriétaire du projet ne peut pas être invité comme collaborateur.'],
            ]);
        }

        // Vérifier que l'utilisateur n'est pas déjà collaborateur actif
        // Note: Nous permettons d'inviter à nouveau un ancien collaborateur
        if ($project->collaborators()->where('user_id', $invitedUser->id)->exists()) {
            throw ValidationException::withMessages([
                'user_email' => ['Cet utilisateur est déjà collaborateur sur ce projet.'],
            ]);
        }

        // Vérifier qu'il n'y a pas déjà une invitation en attente
        if (ProjectInvitation::where('project_id', $project->id)
            ->where('invited_user_id', $invitedUser->id)
            ->where('status', 'pending')
            ->exists()) {
            throw ValidationException::withMessages([
                'user_email' => ['Une invitation est déjà en attente pour cet utilisateur.'],
            ]);
        }

        // Créer l'invitation
        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by' => $invitedBy->id,
            'permission' => $request->permission,
            'message' => $request->message,
            'status' => 'pending',
            'expires_at' => now()->addDays(7), // Expire dans 7 jours
        ]);

        return response()->json([
            'message' => "Une invitation a été envoyée à {$invitedUser->name}",
            'invitation' => [
                'id' => $invitation->id,
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'invited_user' => [
                    'id' => $invitedUser->id,
                    'name' => $invitedUser->name,
                    'email' => $invitedUser->email,
                ],
                'permission' => $invitation->permission,
                'created_at' => $invitation->created_at,
            ]
        ]);
    }

    /**
     * Accepter une invitation
     */
    public function accept(ProjectInvitation $invitation)
    {
        $user = Auth::user();

        // Vérifier que l'invitation appartient à l'utilisateur connecté
        if ($invitation->invited_user_id !== $user->id) {
            return response()->json(['message' => 'Cette invitation ne vous appartient pas'], 403);
        }

        // Vérifier que l'invitation est en attente
        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Cette invitation n\'est plus valide'], 400);
        }

        // Vérifier que l'invitation n'est pas expirée
        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return response()->json(['message' => 'Cette invitation a expiré'], 400);
        }

        // Vérifier si l'utilisateur est déjà collaborateur
        $existingCollaborator = $invitation->project->collaborators()->where('user_id', $user->id)->first();

        if ($existingCollaborator) {
            // L'utilisateur est déjà collaborateur, mettre à jour ses permissions
            $invitation->project->collaborators()->updateExistingPivot($user->id, [
                'permission' => $invitation->permission,
                'updated_at' => now()
            ]);
        } else {
            // Nouveau collaborateur, l'ajouter
            $invitation->project->collaborators()->attach($user->id, [
                'permission' => $invitation->permission,
            ]);
        }

        // Marquer l'invitation comme acceptée
        $invitation->update(['status' => 'accepted']);

        return response()->json([
            'message' => "Vous avez rejoint le projet \"{$invitation->project->name}\"",
            'project' => [
                'id' => $invitation->project->id,
                'name' => $invitation->project->name,
                'permission' => $invitation->permission,
            ]
        ]);
    }

    /**
     * Refuser une invitation
     */
    public function decline(ProjectInvitation $invitation)
    {
        $user = Auth::user();

        // Vérifier que l'invitation appartient à l'utilisateur connecté
        if ($invitation->invited_user_id !== $user->id) {
            return response()->json(['message' => 'Cette invitation ne vous appartient pas'], 403);
        }

        // Vérifier que l'invitation est en attente
        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Cette invitation n\'est plus valide'], 400);
        }

        // Marquer l'invitation comme refusée
        $invitation->update(['status' => 'declined']);

        return response()->json([
            'message' => "Vous avez refusé l'invitation pour le projet \"{$invitation->project->name}\""
        ]);
    }

    /**
     * Annuler une invitation (pour le propriétaire du projet)
     */
    public function destroy(ProjectInvitation $invitation)
    {
        $user = Auth::user();
        $project = $invitation->project;

        // Vérifier que l'utilisateur peut administrer le projet
        if (!$project->canAdmin($user)) {
            return response()->json(['message' => 'Droits insuffisants'], 403);
        }

        $invitation->delete();

        return response()->json([
            'message' => 'Invitation annulée avec succès'
        ]);
    }
}
