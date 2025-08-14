<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

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
        ]);

        // Si timecodes est fourni, l'encoder en JSON
        if (isset($validated['timecodes'])) {
            $validated['timecodes'] = json_encode($validated['timecodes']);
        }

        $project = Project::create($validated);
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
        ]);

        if (isset($validated['timecodes'])) {
            $validated['timecodes'] = json_encode($validated['timecodes']);
        }

        $project->update($validated);
        return response()->json($project);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Projet supprimé']);
    }
}
