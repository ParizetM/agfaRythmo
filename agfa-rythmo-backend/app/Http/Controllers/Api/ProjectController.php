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
use Illuminate\Support\Facades\Log;

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
            'status' => 'nullable|in:in_progress,completed',
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
            'status' => 'nullable|in:in_progress,completed',
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
     * Update the project settings (bandHeight, fontSize, etc.).
     */
    public function updateSettings(Request $request, Project $project)
    {
        // Vérifier les permissions d'écriture
        if (!$project->canModify(Auth::user())) {
            return response()->json(['message' => 'Droits insuffisants'], 403);
        }

        $validated = $request->validate([
            'project_settings' => 'required|array',
            'project_settings.bandHeight' => 'required|numeric|min:40|max:200',
            'project_settings.fontSize' => 'required|numeric|min:1.0|max:3.5',
            'project_settings.fontFamily' => 'required|string|max:255',
            'project_settings.bandBackgroundColor' => 'required|string|max:7',
            'project_settings.sceneChangeColor' => 'required|string|max:7',
            'project_settings.overlayPosition' => 'required|in:over,under-full,under-video-width,contained-16-9,audio-only',
        ]);

        $project->update(['project_settings' => $validated['project_settings']]);
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

    /**
     * Export all project data to encrypted .agfa format.
     */
    public function export(Project $project)
    {
        // Vérifier l'accès
        if (!$project->hasAccess(Auth::user())) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Charger toutes les relations (y compris les relations imbriquées)
        $project->load(['timecodes.character', 'characters', 'sceneChanges']);

        // Récupérer les relations chargées (évite le conflit avec la colonne timecodes)
        $timecodes = $project->getRelation('timecodes');
        $characters = $project->getRelation('characters');
        $sceneChanges = $project->getRelation('sceneChanges');

        // Construire l'export JSON
        $exportData = [
            'export_version' => '1.0',
            'export_date' => now()->toIso8601String(),
            'project' => [
                'name' => $project->name,
                'description' => $project->description,
                'rythmo_lines_count' => $project->rythmo_lines_count,
                'project_settings' => $project->project_settings,
                'video_path' => $project->video_path, // Note: la vidéo n'est pas incluse, juste le chemin
            ],
            'timecodes' => $timecodes->map(function ($timecode) {
                return [
                    'line_number' => $timecode->line_number,
                    'start' => $timecode->start,
                    'end' => $timecode->end,
                    'text' => $timecode->text,
                    'character_name' => $timecode->character ? $timecode->character->name : null,
                    'show_character' => $timecode->show_character,
                    'separator_positions' => $timecode->separator_positions,
                ];
            })->values()->toArray(),
            'characters' => $characters->map(function ($character) {
                return [
                    'name' => $character->name,
                    'color' => $character->color,
                    'text_color' => $character->text_color,
                ];
            })->values()->toArray(),
            'scene_changes' => $sceneChanges->map(function ($sceneChange) {
                return [
                    'timecode' => $sceneChange->timecode,
                ];
            })->values()->toArray(),
        ];

        $jsonContent = json_encode($exportData, JSON_PRETTY_PRINT);
        $filename = \Illuminate\Support\Str::slug($project->name) . '_export.agfa';

        // Toujours crypter avec AES-256 (Laravel Crypt utilise la clé APP_KEY)
        $encrypted = encrypt($jsonContent);

        return response($encrypted, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Import project data from JSON file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'name' => 'required|string|max:255',
            'video_path' => 'nullable|string', // Chemin de la vidéo uploadée
        ]);

        try {
            $fileContent = file_get_contents($request->file('file')->getRealPath());
            $uploadedFile = $request->file('file');
            $extension = $uploadedFile->getClientOriginalExtension();

            // Vérifier si c'est un fichier crypté (.agfa)
            if ($extension === 'agfa') {
                try {
                    // Décrypter le contenu
                    $fileContent = decrypt($fileContent);
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Impossible de décrypter le fichier. Assurez-vous d\'utiliser le même environnement que celui utilisé pour l\'export.',
                        'error' => $e->getMessage()
                    ], 422);
                }
            }

            $data = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'Fichier JSON invalide'], 422);
            }

            // Vérifier la structure du fichier
            if (!isset($data['export_version']) || !isset($data['project'])) {
                return response()->json(['message' => 'Format de fichier non reconnu'], 422);
            }

            $project = DB::transaction(function () use ($data, $request) {
                // Créer le projet
                $projectData = [
                    'name' => $request->input('name'),
                    'description' => $data['project']['description'] ?? null,
                    'rythmo_lines_count' => $data['project']['rythmo_lines_count'] ?? 1,
                    'project_settings' => $data['project']['project_settings'] ?? null,
                    'video_path' => $request->input('video_path'), // Utiliser le chemin vidéo fourni
                    'user_id' => Auth::id(),
                ];

                $project = Project::create($projectData);

                // Mapping des noms de personnages vers leurs IDs
                $characterMap = [];

                // Importer les personnages
                if (isset($data['characters']) && is_array($data['characters'])) {
                    foreach ($data['characters'] as $characterData) {
                        $character = Character::create([
                            'project_id' => $project->id,
                            'name' => $characterData['name'],
                            'color' => $characterData['color'] ?? '#8455F6',
                            'text_color' => $characterData['text_color'] ?? '#FFFFFF',
                        ]);
                        $characterMap[$characterData['name']] = $character->id;
                    }
                }

                // Si aucun personnage n'a été importé, créer un personnage par défaut
                if (empty($characterMap)) {
                    $character = Character::create([
                        'project_id' => $project->id,
                        'name' => 'Personnage principal',
                        'color' => '#8455F6',
                        'text_color' => '#FFFFFF',
                    ]);
                }

                // Importer les timecodes
                if (isset($data['timecodes']) && is_array($data['timecodes'])) {
                    foreach ($data['timecodes'] as $timecodeData) {
                        $characterId = null;
                        if (isset($timecodeData['character_name']) && isset($characterMap[$timecodeData['character_name']])) {
                            $characterId = $characterMap[$timecodeData['character_name']];
                        }

                        $project->timecodes()->create([
                            'line_number' => $timecodeData['line_number'] ?? 1,
                            'start' => $timecodeData['start'],
                            'end' => $timecodeData['end'],
                            'text' => $timecodeData['text'],
                            'character_id' => $characterId,
                            'show_character' => $timecodeData['show_character'] ?? false,
                            'separator_positions' => $timecodeData['separator_positions'] ?? [],
                        ]);
                    }
                }

                // Importer les changements de plan
                if (isset($data['scene_changes']) && is_array($data['scene_changes'])) {
                    foreach ($data['scene_changes'] as $sceneChangeData) {
                        $project->sceneChanges()->create([
                            'timecode' => $sceneChangeData['timecode'],
                        ]);
                    }
                }

                return $project;
            });

            return response()->json([
                'message' => 'Projet importé avec succès',
                'project' => $project->load(['timecodes', 'characters', 'sceneChanges']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'importation du projet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Générer la piste instrumentale (sans voix) pour un projet
     */
    public function generateInstrumental(Project $project)
    {
        /** @var User $user */
        $user = Auth::user();

        // Vérifier permissions (edit minimum)
        if (!$project->canModify($user)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Vérifier que le projet a une vidéo
        if (!$project->video_path) {
            return response()->json(['message' => 'Ce projet n\'a pas de vidéo'], 400);
        }

        // Si déjà généré, retourner info
        if ($project->instrumental_status === 'completed' && $project->instrumental_audio_path) {
            return response()->json([
                'message' => 'Piste instrumentale déjà générée',
                'status' => $project->instrumental_status,
                'progress' => $project->instrumental_progress,
                'audio_path' => $project->instrumental_audio_path,
            ]);
        }

        // Si en cours de génération
        if ($project->instrumental_status === 'processing') {
            return response()->json([
                'message' => 'Génération déjà en cours',
                'status' => $project->instrumental_status,
                'progress' => $project->instrumental_progress,
            ]);
        }

        // Dispatcher le job
        \App\Jobs\ExtractInstrumental::dispatch($project);

        return response()->json([
            'message' => 'Génération de la piste instrumentale lancée',
            'status' => 'processing',
            'progress' => 0,
        ], 202);
    }

    /**
     * Récupérer le statut de génération de la piste instrumentale
     */
    public function instrumentalStatus(Project $project)
    {
        /** @var User $user */
        $user = Auth::user();

        // Vérifier permissions (view minimum)
        if (!$project->hasAccess($user)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return response()->json([
            'status' => $project->instrumental_status,
            'progress' => $project->instrumental_progress,
            'audio_path' => $project->instrumental_audio_path,
        ]);
    }

    /**
     * Supprimer la piste instrumentale générée
     */
    public function deleteInstrumental(Project $project)
    {
        /** @var User $user */
        $user = Auth::user();

        // Vérifier permissions (edit minimum)
        if (!$project->canModify($user)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $project->deleteInstrumental();

        return response()->json([
            'message' => 'Piste instrumentale supprimée',
        ]);
    }
}
