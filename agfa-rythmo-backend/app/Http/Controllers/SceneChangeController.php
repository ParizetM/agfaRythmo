<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SceneChange;
use Illuminate\Http\Request;

class SceneChangeController extends Controller
{
    // Liste des changements de plan pour un projet
    public function index($projectId)
    {
        $project = Project::findOrFail($projectId);
        return response()->json($project->sceneChanges()->orderBy('timecode')->get());
    }

    // Ajout d'un changement de plan
    public function store(Request $request, $projectId)
    {
        $request->validate([
            'timecode' => 'required|numeric|min:0',
        ]);
        $project = Project::findOrFail($projectId);
        $sceneChange = $project->sceneChanges()->create([
            'timecode' => $request->timecode,
        ]);
        return response()->json($sceneChange, 201);
    }

    // Suppression d'un changement de plan
    public function destroy($id)
    {
        $sceneChange = SceneChange::findOrFail($id);
        $sceneChange->delete();
        return response()->json(['success' => true]);
    }
}
