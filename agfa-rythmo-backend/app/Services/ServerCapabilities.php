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

        // Ajouter méthode de diarization si extraction dialogues activée
        if ($capabilities['dialogue_extraction']) {
            $configuredMethod = config('ai.diarization_method', 'mfcc');
            $availableRam = self::getAvailableRamMB();

            // Si Resemblyzer configuré mais RAM insuffisante, fallback vers MFCC
            if ($configuredMethod === 'resemblyzer' && $availableRam < 4000) {
                $capabilities['diarization_method'] = 'mfcc';
                $capabilities['diarization_fallback_reason'] = 'RAM insuffisante (< 4GB)';
                $capabilities['available_ram_mb'] = $availableRam;
            } else {
                $capabilities['diarization_method'] = $configuredMethod;
            }
        }

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

    /**
     * Détecte la RAM disponible sur le serveur (en MB)
     */
    private static function getAvailableRamMB(): int
    {
        // Linux
        if (PHP_OS_FAMILY === 'Linux') {
            $meminfo = @file_get_contents('/proc/meminfo');
            if ($meminfo && preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $matches)) {
                return (int)($matches[1] / 1024); // KB to MB
            }
        }

        // macOS
        if (PHP_OS_FAMILY === 'Darwin') {
            $output = shell_exec('sysctl hw.memsize');
            if ($output && preg_match('/hw\.memsize:\s+(\d+)/', $output, $matches)) {
                return (int)($matches[1] / 1024 / 1024); // Bytes to MB
            }
        }

        // Windows
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('wmic ComputerSystem get TotalPhysicalMemory');
            if ($output && preg_match('/\d+/', $output, $matches)) {
                return (int)($matches[0] / 1024 / 1024); // Bytes to MB
            }
        }

        // Fallback : retourner 0 si détection impossible
        return 0;
    }
}
