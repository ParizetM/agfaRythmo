<?php

namespace App\Services;

class ServerCapabilities
{
    /**
     * Retourne les fonctionnalités IA activées
     */
    public static function getCapabilities(): array
    {
        return [
            'scene_detection' => config('ai.scene_detection', false),
            'auto_subtitles' => config('ai.auto_subtitles', false),
            'voice_recognition' => config('ai.voice_recognition', false),
            'audio_analysis' => config('ai.audio_analysis', false),
        ];
    }
}
