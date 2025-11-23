<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SettingsPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsPresetController extends Controller
{
    /**
     * Liste tous les presets de l'utilisateur connecté
     */
    public function index()
    {
        $presets = SettingsPreset::where('user_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($presets);
    }

    /**
     * Créer un nouveau preset
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Vérifier la limite de 5 presets
        if (!SettingsPreset::canCreate($user)) {
            return response()->json([
                'message' => 'Vous avez atteint la limite de 5 presets. Supprimez-en un pour en créer un nouveau.'
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'settings' => 'required|array',
            'settings.bandHeight' => 'required|numeric|min:40|max:200',
            'settings.fontSize' => 'required|numeric|min:1.0|max:3.5',
            'settings.fontFamily' => 'required|string|max:255',
            'settings.fontWeight' => 'required|integer|min:100|max:900',
            'settings.bandBackgroundColor' => 'required|string|max:7',
            'settings.sceneChangeColor' => 'required|string|max:7',
            'settings.overlayPosition' => 'required|in:over,under-full,under-video-width,contained-16-9,audio-only',
            'settings.bandScale' => 'required|numeric|min:1|max:5',
            'settings.timecodeStyle' => 'required|in:default,character-color',
        ]);

        $preset = SettingsPreset::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'settings' => $validated['settings'],
        ]);

        return response()->json($preset, 201);
    }

    /**
     * Mettre à jour un preset existant
     */
    public function update(Request $request, SettingsPreset $preset)
    {
        // Vérifier que l'utilisateur est propriétaire du preset
        if ($preset->user_id !== Auth::id()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'settings' => 'sometimes|required|array',
            'settings.bandHeight' => 'sometimes|required|numeric|min:40|max:200',
            'settings.fontSize' => 'sometimes|required|numeric|min:1.0|max:3.5',
            'settings.fontFamily' => 'sometimes|required|string|max:255',
            'settings.fontWeight' => 'sometimes|required|integer|min:100|max:900',
            'settings.bandBackgroundColor' => 'sometimes|required|string|max:7',
            'settings.sceneChangeColor' => 'sometimes|required|string|max:7',
            'settings.overlayPosition' => 'sometimes|required|in:over,under-full,under-video-width,contained-16-9,audio-only',
            'settings.bandScale' => 'sometimes|required|numeric|min:1|max:5',
            'settings.timecodeStyle' => 'sometimes|required|in:default,character-color',
        ]);

        $preset->update($validated);

        return response()->json($preset);
    }

    /**
     * Supprimer un preset
     */
    public function destroy(SettingsPreset $preset)
    {
        // Vérifier que l'utilisateur est propriétaire du preset
        if ($preset->user_id !== Auth::id()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $preset->delete();

        return response()->json(['message' => 'Preset supprimé avec succès']);
    }
}
