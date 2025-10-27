<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    /**
     * Lister tous les utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::with(['projects']);

        // Recherche par nom ou email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // Filtrer par rôle
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($users);
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'user' => $user,
            'message' => 'Utilisateur créé avec succès'
        ], 201);
    }

    /**
     * Afficher les détails d'un utilisateur
     */
    public function show(User $user)
    {
        $user->load(['projects', 'collaborativeProjects']);

        return response()->json([
            'user' => $user,
            'stats' => [
                'owned_projects' => $user->projects()->count(),
                'collaborative_projects' => $user->collaborativeProjects()->count(),
            ]
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,user',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        return response()->json([
            'user' => $user,
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
    }

    /**
     * Changer le mot de passe d'un utilisateur
     */
    public function changePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Supprimer tous les tokens pour forcer une reconnexion
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Mot de passe modifié avec succès'
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression du dernier admin
        if ($user->isAdmin()) {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'user' => ['Impossible de supprimer le dernier administrateur.'],
                ]);
            }
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    /**
     * Voir tous les projets (vue admin)
     */
    public function allProjects(Request $request)
    {
        $query = Project::with(['owner', 'collaborators']);

        // Recherche par nom de projet
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%$search%");
        }

        // Filtrer par propriétaire
        if ($request->has('owner_id')) {
            $query->where('user_id', $request->owner_id);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(20);

        // Ajouter la taille de la vidéo pour chaque projet
        $projects->getCollection()->transform(function ($project) {
            $project->video_size = 0;
            if ($project->video_path) {
                // Le video_path contient "/storage/videos/filename.mp4"
                $filename = basename($project->video_path);
                
                // Les fichiers sont stockés via le disque 'local' qui pointe vers storage/app/private
                // Donc le chemin est: storage/app/private/public/videos/filename.mp4
                $videoPath = storage_path('app/private/public/videos/' . $filename);
                
                if (file_exists($videoPath)) {
                    $project->video_size = filesize($videoPath);
                }
            }
            return $project;
        });

        return response()->json($projects);
    }

    /**
     * Supprimer un projet (vue admin)
     */
    public function destroyProject(Project $project)
    {
        // Supprimer le fichier vidéo associé s'il existe
        if ($project->video_path) {
            $videoPath = storage_path('app/public/videos/' . basename($project->video_path));
            if (file_exists($videoPath)) {
                unlink($videoPath);
            }
        }

        // Supprimer le projet (les relations seront supprimées en cascade)
        $project->delete();

        return response()->json([
            'message' => 'Projet supprimé avec succès'
        ]);
    }

    /**
     * Obtenir les statistiques globales
     */
    public function stats()
    {
        // Calculer la taille totale des vidéos
        $totalVideoSize = 0;
        $videoCount = 0;
        $projects = Project::whereNotNull('video_path')->get();

        foreach ($projects as $project) {
            $filename = basename($project->video_path);
            // Les fichiers sont stockés via le disque 'local' qui pointe vers storage/app/private
            $videoPath = storage_path('app/private/public/videos/' . $filename);
            
            if (file_exists($videoPath)) {
                $totalVideoSize += filesize($videoPath);
                $videoCount++;
            }
        }

        return response()->json([
            'total_users' => User::count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'regular_users' => User::where('role', 'user')->count(),
            'total_projects' => Project::count(),
            'projects_with_collaborators' => Project::has('collaborators')->count(),
            'total_video_size' => $totalVideoSize,
            'video_count' => $videoCount,
        ]);
    }
}
