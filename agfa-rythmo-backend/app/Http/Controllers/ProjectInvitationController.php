<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Models\ProjectInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProjectInvitationController extends Controller
{
    /**
     * Obtenir les invitations en attente pour l'utilisateur connecté
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $invitations = $user->pendingInvitations()
            ->with(['project', 'invitedBy'])
            ->orderBy('created_at', 'desc')
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
     * Envoyer une invitation à un utilisateur
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'user_email' => 'required|email|exists:users,email',
            'permission' => 'required|in:view,edit,admin',
            'message' => 'nullable|string|max:500',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $user = Auth::user();

        // Vérifier les permissions de l'utilisateur sur le projet
        if (!$this->canManageCollaborators($user, $project)) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de gérer les collaborateurs de ce projet.'
            ], 403);
        }

        $invitedUser = User::where('email', $validated['user_email'])->first();

        // Vérifier si l'utilisateur est déjà collaborateur
        if ($project->collaborators()->where('user_id', $invitedUser->id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur est déjà collaborateur du projet.'
            ], 409);
        }

        // Vérifier s'il y a déjà une invitation en attente
        $existingInvitation = ProjectInvitation::where([
            'project_id' => $project->id,
            'invited_user_id' => $invitedUser->id,
            'status' => 'pending'
        ])->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        })->first();

        if ($existingInvitation) {
            return response()->json([
                'message' => 'Une invitation est déjà en attente pour cet utilisateur.'
            ], 409);
        }

        // Créer l'invitation
        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $user->id,
            'invited_user_id' => $invitedUser->id,
            'permission' => $validated['permission'],
            'status' => 'pending',
            'message' => $validated['message'] ?? null,
            'expires_at' => Carbon::now()->addDays(7), // Expire dans 7 jours
        ]);

        return response()->json([
            'message' => 'Invitation envoyée avec succès.',
            'invitation' => [
                'id' => $invitation->id,
                'invited_user' => [
                    'name' => $invitedUser->name,
                    'email' => $invitedUser->email,
                ],
                'permission' => $invitation->permission,
                'expires_at' => $invitation->expires_at->toISOString(),
            ]
        ], 201);
    }

    /**
     * Accepter une invitation
     */
    public function accept(ProjectInvitation $invitation): JsonResponse
    {
        $user = Auth::user();

        // Vérifier que l'invitation appartient à l'utilisateur connecté
        if ($invitation->invited_user_id !== $user->id) {
            return response()->json([
                'message' => 'Cette invitation ne vous appartient pas.'
            ], 403);
        }

        // Vérifier que l'invitation est toujours valide
        if (!$invitation->isPending()) {
            return response()->json([
                'message' => 'Cette invitation n\'est plus valide.'
            ], 410);
        }

        // Ajouter l'utilisateur comme collaborateur
        $invitation->project->collaborators()->attach($user->id, [
            'permission' => $invitation->permission
        ]);

        // Marquer l'invitation comme acceptée
        $invitation->update(['status' => 'accepted']);

        return response()->json([
            'message' => 'Invitation acceptée avec succès.',
            'project' => [
                'id' => $invitation->project->id,
                'name' => $invitation->project->name,
            ]
        ]);
    }

    /**
     * Refuser une invitation
     */
    public function decline(ProjectInvitation $invitation): JsonResponse
    {
        $user = Auth::user();

        // Vérifier que l'invitation appartient à l'utilisateur connecté
        if ($invitation->invited_user_id !== $user->id) {
            return response()->json([
                'message' => 'Cette invitation ne vous appartient pas.'
            ], 403);
        }

        // Vérifier que l'invitation est toujours valide
        if (!$invitation->isPending()) {
            return response()->json([
                'message' => 'Cette invitation n\'est plus valide.'
            ], 410);
        }

        // Marquer l'invitation comme refusée
        $invitation->update(['status' => 'declined']);

        return response()->json([
            'message' => 'Invitation refusée.'
        ]);
    }

    /**
     * Annuler une invitation envoyée
     */
    public function cancel(ProjectInvitation $invitation): JsonResponse
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur peut gérer ce projet
        if (!$this->canManageCollaborators($user, $invitation->project)) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de gérer les collaborateurs de ce projet.'
            ], 403);
        }

        // Vérifier que l'invitation est en attente
        if ($invitation->status !== 'pending') {
            return response()->json([
                'message' => 'Cette invitation ne peut plus être annulée.'
            ], 410);
        }

        $invitation->delete();

        return response()->json([
            'message' => 'Invitation annulée.'
        ]);
    }

    /**
     * Vérifier si un utilisateur peut gérer les collaborateurs d'un projet
     */
    private function canManageCollaborators(User $user, Project $project): bool
    {
        // Propriétaire du projet
        if ($project->user_id === $user->id) {
            return true;
        }

        // Collaborateur avec permission admin
        $collaboration = $project->collaborators()
            ->where('user_id', $user->id)
            ->first();

        return $collaboration && $collaboration->pivot->permission === 'admin';
    }
}
