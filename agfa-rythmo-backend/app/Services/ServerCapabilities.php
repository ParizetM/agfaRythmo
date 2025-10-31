<?php

namespace App\Services;

class ServerCapabilities
{
    /**
     * Retourne les fonctionnalités IA activées
     */
    public static function getCapabilities(): array
    {
        $capabilities = [
            'scene_detection' => config('ai.scene_detection', false),
            'dialogue_extraction' => config('ai.dialogue_extraction', false),
            'translation' => config('ai.translation.enabled', false),
            'auto_subtitles' => config('ai.auto_subtitles', false),
            'voice_recognition' => config('ai.voice_recognition', false),
            'audio_analysis' => config('ai.audio_analysis', false),
        ];

        // Ajouter les langues supportées si traduction activée
        if ($capabilities['translation']) {
            try {
                $translationService = new TranslationService();
                $capabilities['supported_languages'] = $translationService->getSupportedLanguages();
                $capabilities['translation_provider'] = config('ai.translation.provider', 'unknown');
            } catch (\Exception $e) {
                // En cas d'erreur, langues par défaut
                $capabilities['supported_languages'] = $translationService->getDefaultLanguages();
                $capabilities['translation_provider'] = config('ai.translation.provider', 'unknown');
            }
        }

        return $capabilities;
    }
}
