<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\SceneChange;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DetectSceneChanges implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Project $project,
        public float $threshold = 0.4,
        public float $fps = 2
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Début de l'analyse de détection de plans pour le projet {$this->project->id}");

            // Vérifier si l'analyse a été annulée avant de commencer
            $this->project->refresh();
            if ($this->project->analysis_status === 'cancelled') {
                Log::info("Analyse annulée avant le démarrage pour le projet {$this->project->id}");
                return;
            }

            // Mettre le statut à "processing" avec progression 0%
            $this->project->update([
                'analysis_status' => 'processing',
                'analysis_progress' => 0,
                'analysis_message' => 'Préparation de l\'analyse...'
            ]);

            // Récupérer le chemin complet de la vidéo
            // Les vidéos sont stockées dans le disk 'local' (storage/app/private/public/videos/)
            // video_path contient juste le nom du fichier
            $videoPath = storage_path('app/private/public/videos/' . $this->project->video_path);

            if (!file_exists($videoPath)) {
                throw new \Exception("Fichier vidéo introuvable: {$videoPath}");
            }

            // Vérifier annulation
            $this->project->refresh();
            if ($this->project->analysis_status === 'cancelled') {
                Log::info("Analyse annulée pendant la préparation pour le projet {$this->project->id}");
                return;
            }

            // Progression : 10% - Fichier vidéo trouvé
            $this->project->update([
                'analysis_progress' => 10,
                'analysis_message' => 'Fichier vidéo validé, lancement FFmpeg...'
            ]);

            // Commande FFmpeg pour détecter les changements de plan
            // Utilise le filtre "select" avec scene detection
            // fps: réduit le framerate analysé pour accélérer le traitement
            // threshold: sensibilité de détection (0.1 = très sensible, 1.0 = peu sensible)
            $outputFile = storage_path('app/temp_scene_changes_' . $this->project->id . '.txt');
            $ffmpegLogFile = storage_path('app/temp_ffmpeg_log_' . $this->project->id . '.txt');

            // Étape 1 : Exécuter FFmpeg et sauvegarder le log complet
            // Applique d'abord le filtre fps pour réduire la fréquence d'analyse, puis select pour la détection
            $command = sprintf(
                'ffmpeg -i %s -vf "fps=%s,select=\'gt(scene,%s)\',showinfo" -f null - 2> %s',
                escapeshellarg($videoPath),
                $this->fps,
                $this->threshold,
                escapeshellarg($ffmpegLogFile)
            );

            Log::info("Exécution de la commande FFmpeg avec fps={$this->fps}, threshold={$this->threshold}: {$command}");

            // Progression : 20% - Analyse FFmpeg en cours
            $this->project->update([
                'analysis_progress' => 20,
                'analysis_message' => "Analyse vidéo avec FFmpeg (fps={$this->fps}, sensibilité={$this->threshold})..."
            ]);

            // Exécuter la commande FFmpeg
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            // Vérifier annulation après FFmpeg
            $this->project->refresh();
            if ($this->project->analysis_status === 'cancelled') {
                Log::info("Analyse annulée après FFmpeg pour le projet {$this->project->id}");
                // Nettoyer les fichiers temporaires
                if (file_exists($outputFile)) {
                    unlink($outputFile);
                }
                if (file_exists($ffmpegLogFile)) {
                    unlink($ffmpegLogFile);
                }
                return;
            }

            if ($returnCode !== 0 && $returnCode !== 1) {
                // FFmpeg retourne souvent 1 même en cas de succès avec -f null
                throw new \Exception("Erreur FFmpeg (code {$returnCode})");
            }

            // Étape 2 : Parser le log pour extraire les pts_time
            if (!file_exists($ffmpegLogFile)) {
                throw new \Exception("Log FFmpeg introuvable");
            }

            $logContent = file_get_contents($ffmpegLogFile);
            preg_match_all('/pts_time:(\d+\.?\d*)/', $logContent, $matches);

            if (empty($matches[1])) {
                Log::warning("Aucun changement de plan détecté pour le projet {$this->project->id}");
                // Sauvegarder un fichier vide pour la suite
                file_put_contents($outputFile, '');
            } else {
                // Sauvegarder les timecodes
                file_put_contents($outputFile, implode(PHP_EOL, $matches[1]));
            }

            // Nettoyer le log FFmpeg
            if (file_exists($ffmpegLogFile)) {
                unlink($ffmpegLogFile);
            }

            // Progression : 60% - FFmpeg terminé, lecture des résultats
            $this->project->update([
                'analysis_progress' => 60,
                'analysis_message' => 'Analyse terminée, lecture des résultats...'
            ]);

            // Lire les résultats
            if (!file_exists($outputFile)) {
                throw new \Exception("Fichier de sortie FFmpeg introuvable");
            }

            $timecodes = file($outputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Nettoyer le fichier temporaire
            unlink($outputFile);

            if (empty($timecodes)) {
                Log::warning("Aucun changement de plan détecté pour le projet {$this->project->id}");
                $this->project->update([
                    'analysis_status' => 'completed',
                    'analysis_progress' => 100,
                    'analysis_message' => 'Aucun changement de plan détecté'
                ]);
                return;
            }

            // Progression : 70% - Création des scene changes
            $this->project->update([
                'analysis_progress' => 70,
                'analysis_message' => 'Création des changements de plan...'
            ]);

            // Créer les SceneChanges dans la base de données
            $createdCount = 0;
            $totalTimecodes = count($timecodes);

            foreach ($timecodes as $index => $timecode) {
                // Vérifier annulation toutes les 10 itérations
                if ($index % 10 === 0) {
                    $this->project->refresh();
                    if ($this->project->analysis_status === 'cancelled') {
                        Log::info("Analyse annulée pendant la création des scene changes pour le projet {$this->project->id}");
                        // Supprimer les scene changes déjà créés
                        SceneChange::where('project_id', $this->project->id)->delete();
                        return;
                    }
                }

                $timecode = trim($timecode);
                if (empty($timecode) || !is_numeric($timecode)) {
                    continue;
                }

                $timecodeFloat = (float) $timecode;

                // Vérifier si ce timecode n'existe pas déjà (éviter les doublons)
                $exists = SceneChange::where('project_id', $this->project->id)
                    ->where('timecode', $timecodeFloat)
                    ->exists();

                if (!$exists) {
                    SceneChange::create([
                        'project_id' => $this->project->id,
                        'timecode' => $timecodeFloat,
                    ]);
                    $createdCount++;
                }

                // Mise à jour progressive : 70% -> 95%
                $progress = 70 + (($index + 1) / $totalTimecodes) * 25;
                if ($index % 10 === 0 || $index === $totalTimecodes - 1) {
                    $this->project->update([
                        'analysis_progress' => (int) $progress,
                        'analysis_message' => "Création des changements de plan : {$createdCount}/{$totalTimecodes}"
                    ]);
                }
            }

            Log::info("Analyse terminée pour le projet {$this->project->id}. {$createdCount} changements de plan créés.");

            // Progression : 100% - Terminé
            $this->project->update([
                'analysis_status' => 'completed',
                'analysis_progress' => 100,
                'analysis_message' => "{$createdCount} changement(s) de plan détecté(s)"
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'analyse de détection de plans pour le projet {$this->project->id}: " . $e->getMessage());

            // Mettre le statut à "failed"
            $this->project->update([
                'analysis_status' => 'failed',
                'analysis_progress' => 0,
                'analysis_message' => 'Erreur : ' . $e->getMessage()
            ]);

            throw $e;
        }
    }
}

