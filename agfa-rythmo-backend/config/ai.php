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


];
