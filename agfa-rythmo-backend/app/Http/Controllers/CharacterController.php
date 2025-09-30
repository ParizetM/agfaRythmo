<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CharacterController extends Controller
{
    /**
     * Get all characters for a project.
     */
    public function index(int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $characters = $project->characters()->orderBy('created_at')->get();

        return response()->json([
            'characters' => $characters
        ]);
    }

    /**
     * Create a new character.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'text_color' => 'sometimes|nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $character = Character::create($validated);

        return response()->json($character, 201);
    }

    /**
     * Update a character.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $character = Character::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'text_color' => 'sometimes|nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $character->update($validated);

        return response()->json($character);
    }

    /**
     * Delete a character.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $character = Character::findOrFail($id);
        $deleteTimecodes = $request->boolean('deleteTimecodes', false);

        if ($deleteTimecodes) {
            // Supprimer tous les timecodes associés
            $character->timecodes()->delete();
        } else {
            // Désassocier les timecodes (character_id = null)
            $character->timecodes()->update(['character_id' => null]);
        }

        $character->delete();

        return response()->json(['message' => 'Character deleted successfully']);
    }

    /**
     * Clone a character from another project.
     */
    public function clone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_character_id' => 'required|exists:characters,id',
            'target_project_id' => 'required|exists:projects,id',
        ]);

        $sourceCharacter = Character::findOrFail($validated['source_character_id']);
        $targetProject = Project::findOrFail($validated['target_project_id']);

        // Vérifier que l'utilisateur a accès aux deux projets (à implémenter selon votre logique d'auth)

        $newCharacter = Character::create([
            'project_id' => $targetProject->id,
            'name' => $sourceCharacter->name,
            'color' => $sourceCharacter->color,
            'text_color' => $sourceCharacter->text_color,
        ]);

        return response()->json($newCharacter, 201);
    }

    /**
     * Get characters from other projects (for cloning).
     */
    public function getForCloning(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exclude_project_id' => 'sometimes|exists:projects,id',
        ]);

        $query = Character::with('project:id,name');

        if (isset($validated['exclude_project_id'])) {
            $query->where('project_id', '!=', $validated['exclude_project_id']);
        }

        $characters = $query->orderBy('name')->get();

        return response()->json([
            'characters' => $characters
        ]);
    }
}
