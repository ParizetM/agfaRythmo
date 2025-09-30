<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\ProjectCollaborator;
use App\Models\ProjectInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProjectCollaboratorController extends Controller
{
    /**
     * Lister les collaborateurs d'un projet
     */
    public function index(Project $project)
    {
        // Vérifier que l'utilisateur peut voir les collaborateurs
        if (!$project->hasAccess(Auth::user())) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $collaborators = $project->collaborators()
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'permission' => $user->pivot->permission,
                    'joined_at' => $user->pivot->created_at,
                ];
            });

        return response()->json([
            'owner' => $project->owner,
            'collaborators' => $collaborators
        ]);
    }

    /**
     * Inviter un collaborateur sur un projet
     */
    public function store(Request $request, Project $project)
    {
        // Vérifier que l'utilisateur peut administrer le projet
        if (!$project->canAdmin(Auth::user())) {
            return response()->json(['message' => 'Droits insuffisants'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|in:edit,admin',
        ]);

        $user = User::find($request->user_id);

        // Vérifier que l'utilisateur n'est pas le propriétaire
        if ($project->user_id === $user->id) {
            throw ValidationException::withMessages([
                'user_id' => ['Le propriétaire du projet ne peut pas être ajouté comme collaborateur.'],
            ]);
        }

        // Vérifier que l'utilisateur n'est pas déjà collaborateur
        if ($project->collaborators()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['Cet utilisateur est déjà collaborateur sur ce projet.'],
            ]);
        }

        // Ajouter le collaborateur
        $project->collaborators()->attach($user->id, [
            'permission' => $request->permission,
        ]);

        return response()->json([
            'message' => "L'utilisateur {$user->name} a été ajouté comme collaborateur",
            'collaborator' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permission' => $request->permission,
                'joined_at' => now(),
            ]
        ]);
    }

    /**
     * Mettre à jour les permissions d'un collaborateur
     */
    public function update(Request $request, Project $project, User $user)
    {
        // Vérifier que l'utilisateur peut administrer le projet
        if (!$project->canAdmin(Auth::user())) {
            return response()->json(['message' => 'Droits insuffisants'], 403);
        }

        $request->validate([
            'permission' => 'required|in:edit,admin',
        ]);

        // Vérifier que l'utilisateur est bien collaborateur
        $collaborator = $project->collaborators()->where('user_id', $user->id)->first();
        if (!$collaborator) {
            return response()->json(['message' => 'Utilisateur non trouvé parmi les collaborateurs'], 404);
        }

        // Mettre à jour les permissions
        $project->collaborators()->updateExistingPivot($user->id, [
            'permission' => $request->permission,
        ]);

        return response()->json([
            'message' => "Permissions mises à jour pour {$user->name}",
            'collaborator' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permission' => $request->permission,
            ]
        ]);
    }

    /**
     * Retirer un collaborateur d'un projet
     */
    public function destroy(Project $project, User $user)
    {
        // Vérifier que l'utilisateur peut administrer le projet
        if (!$project->canAdmin(Auth::user())) {
            return response()->json(['message' => 'Droits insuffisants'], 403);
        }

        // Vérifier que l'utilisateur est bien collaborateur
        if (!$project->collaborators()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Utilisateur non trouvé parmi les collaborateurs'], 404);
        }

        // Retirer le collaborateur
        $project->collaborators()->detach($user->id);

        // Marquer les invitations acceptées comme "removed" pour permettre de futures réinvitations
        ProjectInvitation::where('project_id', $project->id)
            ->where('invited_user_id', $user->id)
            ->where('status', 'accepted')
            ->update(['status' => 'removed']);

        return response()->json([
            'message' => "L'utilisateur {$user->name} a été retiré du projet"
        ]);
    }

    /**
     * Quitter un projet (pour le collaborateur lui-même)
     */
    public function leave(Project $project)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est bien collaborateur
        if (!$project->collaborators()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Vous n\'êtes pas collaborateur sur ce projet'], 404);
        }

        // Quitter le projet
        $project->collaborators()->detach($user->id);

        // Marquer les invitations acceptées comme "left" pour permettre de futures réinvitations
        ProjectInvitation::where('project_id', $project->id)
            ->where('invited_user_id', $user->id)
            ->where('status', 'accepted')
            ->update(['status' => 'left']);

        return response()->json([
            'message' => 'Vous avez quitté le projet avec succès'
        ]);
    }

    /**
     * Rechercher des utilisateurs à inviter
     */
    public function searchUsers(Request $request, Project $project)
    {
        // Vérifier que l'utilisateur peut administrer le projet
        if (!$project->canAdmin(Auth::user())) {
            return response()->json(['message' => 'Droits insuffisants'], 403);
        }

        $search = $request->get('search', '');

        $users = User::where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
            })
            ->where('id', '!=', $project->user_id) // Exclure le propriétaire
            ->whereNotIn('id', $project->collaborators()->pluck('user_id')) // Exclure les collaborateurs existants
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}
