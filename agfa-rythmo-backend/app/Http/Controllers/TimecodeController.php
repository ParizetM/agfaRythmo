<?php

namespace App\Http\Controllers;

use App\Models\Timecode;
use App\Models\Project;
use App\Services\SrtParser;
use Illuminate\Http\Request;

class TimecodeController extends Controller
{
    public function index(Project $project)
    {
        return response()->json([
            'timecodes' => $project->timecodes()
                ->with('character')
                ->orderBy('line_number')
                ->orderBy('start')
                ->get()
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'line_number' => 'required|integer|between:1,6',
            'start' => 'required|numeric|min:0',
            'end' => 'required|numeric|min:0',
            'text' => 'required|string',
            'character_id' => 'nullable|exists:characters,id',
            'show_character' => 'nullable|boolean',
            'separator_positions' => 'nullable|array',
        ]);

        // Si aucun character_id n'est fourni ou est null, ne pas forcer l'assignation
        // La logique métier pour assigner un personnage par défaut doit être gérée côté frontend

        $timecode = $project->timecodes()->create($validated);

        return response()->json([
            'timecode' => $timecode->load('character')
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
            'character_id' => 'sometimes|nullable|exists:characters,id',
            'show_character' => 'sometimes|boolean',
            'separator_positions' => 'sometimes|nullable|array',
        ]);

        $timecode->update($validated);

        return response()->json([
            'timecode' => $timecode->load('character')
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

    /**
     * Importe un fichier SRT et crée des timecodes
     */
    public function importSrt(Request $request, Project $project)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:srt,txt|max:10240', // Max 10MB
            'line_number' => 'required|integer|between:1,' . ($project->rythmo_lines_count ?? 1),
            'character_id' => 'nullable|exists:characters,id',
        ]);

        try {
            // Lire le contenu du fichier
            $content = file_get_contents($request->file('file')->getRealPath());

            // Parser le fichier SRT
            $parser = new SrtParser();
            $parsedTimecodes = $parser->parse($content);

            // Créer les timecodes en batch
            $createdTimecodes = [];
            foreach ($parsedTimecodes as $tc) {
                $timecode = $project->timecodes()->create([
                    'line_number' => $validated['line_number'],
                    'start' => $tc['start'],
                    'end' => $tc['end'],
                    'text' => $tc['text'],
                    'character_id' => $validated['character_id'] ?? null,
                    'show_character' => false, // Par défaut
                ]);

                $createdTimecodes[] = $timecode;
            }

            return response()->json([
                'message' => 'Timecodes importés avec succès',
                'count' => count($createdTimecodes),
                'timecodes' => $createdTimecodes
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'import du fichier SRT',
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
