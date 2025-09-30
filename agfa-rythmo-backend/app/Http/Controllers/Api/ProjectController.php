<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            // Les admins voient tous les projets
            $projects = Project::with(['owner', 'collaborators'])->get();
        } else {
            // Les utilisateurs voient uniquement leurs projets + ceux où ils sont collaborateurs
            $projects = $user->accessibleProjects()
                ->with(['owner', 'collaborators'])
                ->get();
        }

        return response()->json($projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_path' => 'nullable|string',
            'timecodes' => 'nullable|array',
            'text_content' => 'nullable|string',
            'json_path' => 'nullable|string',
            'rythmo_lines_count' => 'nullable|integer|min:1|max:6',
        ]);

        // Si timecodes est fourni, l'encoder en JSON
        if (isset($validated['timecodes'])) {
            $validated['timecodes'] = json_encode($validated['timecodes']);
        }

        $project = DB::transaction(function () use ($validated) {
            // Assigner l'utilisateur courant comme propriétaire
            $validated['user_id'] = Auth::id();
            $project = Project::create($validated);

            // Créer automatiquement un personnage par défaut
            Character::create([
                'project_id' => $project->id,
                'name' => 'Personnage principal',
                'color' => '#8455F6', // Couleur par défaut
            ]);

            return $project;
        });

        return response()->json($project, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        // Vérifier l'accès
        if (!$project->hasAccess(Auth::user())) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $project->load(['owner', 'collaborators']);
        return response()->json($project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        // Vérifier les permissions d'écriture
        if (!$project->canModify(Auth::user())) {
            return response()->json(['message' => 'Droits insuffisants'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'video_path' => 'nullable|string',
            'timecodes' => 'nullable|array',
            'text_content' => 'nullable|string',
            'json_path' => 'nullable|string',
            'rythmo_lines_count' => 'nullable|integer|min:1|max:6',
        ]);

        if (isset($validated['timecodes'])) {
            $validated['timecodes'] = json_encode($validated['timecodes']);
        }

        $project->update($validated);
        return response()->json($project);
    }

    /**
     * Update the rythmo lines count for a project.
     */
    public function updateRythmoLinesCount(Request $request, Project $project)
    {
        // Vérifier les permissions d'écriture
        if (!$project->canModify(Auth::user())) {
            return response()->json(['message' => 'Droits insuffisants'], 403);
        }

        $validated = $request->validate([
            'rythmo_lines_count' => 'required|integer|min:1|max:6',
        ]);

        $project->update($validated);
        return response()->json($project);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        /** @var User $user */
        $user = Auth::user();

        // Seul le propriétaire ou un admin peut supprimer
        if (!$user->isAdmin() && $project->user_id !== $user->id) {
            return response()->json(['message' => 'Seul le propriétaire peut supprimer ce projet'], 403);
        }

        DB::transaction(function () use ($project) {
            // Supprimer toutes les relations et données associées

            // 1. Supprimer les timecodes
            $project->timecodes()->delete();

            // 2. Supprimer les changements de plan
            $project->sceneChanges()->delete();

            // 3. Supprimer les personnages
            $project->characters()->delete();

            // 4. Supprimer les collaborations (table pivot)
            $project->collaborators()->detach();

            // 5. Supprimer les invitations en attente
            $project->invitations()->delete();

            // 6. Supprimer la vidéo associée si elle existe
            if ($project->video_path) {
                $videoPath = 'public/videos/' . $project->video_path;
                if (Storage::exists($videoPath)) {
                    Storage::delete($videoPath);
                }
            }

            // 7. Supprimer le projet lui-même
            $project->delete();
        });

        return response()->json(['message' => 'Projet et toutes ses données associées supprimés avec succès']);
    }
}
