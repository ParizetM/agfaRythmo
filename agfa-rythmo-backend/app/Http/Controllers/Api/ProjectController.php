<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Retourne la liste de tous les projets
        return response()->json(Project::all());
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
        return response()->json($project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
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
        // Supprimer la vidéo associée si elle existe
        if ($project->video_path) {
            $videoPath = 'public/videos/' . $project->video_path;
            if (Storage::exists($videoPath)) {
                Storage::delete($videoPath);
            }
        }
        $project->delete();
        return response()->json(['message' => 'Projet supprimé']);
    }
}
