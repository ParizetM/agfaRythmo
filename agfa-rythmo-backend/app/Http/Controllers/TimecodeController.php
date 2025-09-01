<?php

namespace App\Http\Controllers;

use App\Models\Timecode;
use App\Models\Project;
use Illuminate\Http\Request;

class TimecodeController extends Controller
{
    public function index(Project $project)
    {
        return response()->json([
            'timecodes' => $project->timecodes()->orderBy('line_number')->orderBy('start')->get()
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'line_number' => 'required|integer|between:1,6',
            'start' => 'required|numeric|min:0',
            'end' => 'required|numeric|min:0',
            'text' => 'required|string',
        ]);

        $timecode = $project->timecodes()->create($validated);

        return response()->json([
            'timecode' => $timecode
        ], 201);
    }

    public function show(Project $project, Timecode $timecode)
    {
        // Vérifier que le timecode appartient au projet
        if ($timecode->project_id !== $project->id) {
            return response()->json(['error' => 'Timecode not found'], 404);
        }

        return response()->json([
            'timecode' => $timecode
        ]);
    }

    public function update(Request $request, Project $project, Timecode $timecode)
    {
        // Vérifier que le timecode appartient au projet
        if ($timecode->project_id !== $project->id) {
            return response()->json(['error' => 'Timecode not found'], 404);
        }

        $validated = $request->validate([
            'line_number' => 'sometimes|integer|between:1,6',
            'start' => 'sometimes|numeric|min:0',
            'end' => 'sometimes|numeric|min:0',
            'text' => 'sometimes|string',
        ]);

        $timecode->update($validated);

        return response()->json([
            'timecode' => $timecode
        ]);
    }

    public function destroy(Project $project, Timecode $timecode)
    {
        // Vérifier que le timecode appartient au projet
        if ($timecode->project_id !== $project->id) {
            return response()->json(['error' => 'Timecode not found'], 404);
        }

        $timecode->delete();

        return response()->json([
            'message' => 'Timecode deleted successfully'
        ]);
    }

    public function getByLine(Project $project, $lineNumber)
    {
        if ($lineNumber < 1 || $lineNumber > 6) {
            return response()->json(['error' => 'Invalid line number'], 400);
        }

        $timecodes = $project->timecodesForLine($lineNumber)->orderBy('start')->get();

        return response()->json([
            'timecodes' => $timecodes
        ]);
    }
}
