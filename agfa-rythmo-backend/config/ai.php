<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fonctionnalités IA Activées
    |--------------------------------------------------------------------------
    |
    | Configurez ici les fonctionnalités IA disponibles dans l'application.
    | Mettez à 'true' pour activer, 'false' pour désactiver.
    |
    */

    // Détection automatique des changements de plan vidéo
    // Nécessite : FFmpeg installé + Queue workers actifs
    'scene_detection' => env('AI_SCENE_DETECTION_ENABLED', false),

    // Extraction automatique de dialogues avec Whisper + diarization
    // Nécessite : Python 3.8+, FFmpeg, openai-whisper, torch
    'dialogue_extraction' => env('AI_DIALOGUE_EXTRACTION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Configuration Whisper
    |--------------------------------------------------------------------------
    |
    | Paramètres pour la transcription audio automatique
    |
    */

    // Modèle Whisper à utiliser (tiny/base/small/medium/large)
    // Recommandé pour serveur 2GB RAM : 'tiny' ou 'base'
    // tiny: ~1GB RAM, rapide, précision correcte
    // small: ~2GB RAM, plus lent, meilleure précision
    'whisper_model' => env('AI_WHISPER_MODEL', 'tiny'),

    // Activer la diarization (séparation des locuteurs)
    'diarization_enabled' => env('AI_DIARIZATION_ENABLED', false),

    // Méthode de diarization :
    // - mfcc : MFCC clustering (léger, ~50MB RAM, précision 30-50%, serveur 2GB) ⭐⭐
    // - resemblyzer : Embeddings 256D pré-entraînés (lourd, ~2GB RAM, précision 85-95%, serveur 4GB) ⭐⭐⭐⭐⭐
    'diarization_method' => env('AI_DIARIZATION_METHOD', 'mfcc'),

    // Nombre maximum de locuteurs à détecter (2-20)
    'max_speakers' => env('AI_MAX_SPEAKERS', 10),

    // Langues supportées pour la transcription
    // Liste complète : https://github.com/openai/whisper#available-models-and-languages
    'supported_languages' => [
        'auto' => 'Détection automatique',
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어',
        'ru' => 'Русский',
        'ar' => 'العربية',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration Traduction Automatique
    |--------------------------------------------------------------------------
    |
    | Paramètres pour la traduction des dialogues
    |
    */

    'translation' => [
        // Activer la traduction automatique
        'enabled' => env('AI_TRANSLATION_ENABLED', false),

        // Provider de traduction :
        // - nllb : Meta AI, 200 langues, GRATUIT, local (~2GB), batch natif ⭐⭐⭐⭐
        // - mymemory : GRATUIT 10k chars/jour, aucune API key, limite 500 chars/phrase ⭐⭐⭐
        'provider' => env('AI_TRANSLATION_PROVIDER', 'nllb'),

        // Configuration NLLB-200
        'nllb_model_size' => env('AI_NLLB_MODEL_SIZE', '600M'), // 600M, 1.3B, 3.3B

        // Contexte : inclure noms des personnages dans la traduction
        // Améliore la qualité mais peut ralentir légèrement
        'use_character_context' => env('AI_TRANSLATION_USE_CONTEXT', true),
    ],

];
