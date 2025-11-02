<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExtractInstrumental implements ShouldQueue
{
    use Queueable;

    public $timeout = 1800; // 30 minutes (même que ExtractDialogues)
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Project $project
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("[ExtractInstrumental] Starting for project {$this->project->id}");

            // Mettre à jour le statut
            $this->project->update([
                'instrumental_status' => 'processing',
                'instrumental_progress' => 0,
            ]);

            // Vérifier que la vidéo existe
            $videoPath = storage_path('app/private/public/videos/' . $this->project->video_path);

            if (!file_exists($videoPath)) {
                throw new \Exception("Fichier vidéo introuvable: {$videoPath}");
            }

            // Progression: 10%
            $this->project->update(['instrumental_progress' => 10]);

            // 1. Extraire audio de la vidéo avec FFmpeg
            Log::info("[ExtractInstrumental] Step 1: Extracting audio from video");
            $audioPath = $this->extractAudioFromVideo($videoPath);

            // Progression: 30%
            $this->project->update(['instrumental_progress' => 30]);

            // 2. Séparer instrumental avec Demucs
            Log::info("[ExtractInstrumental] Step 2: Separating instrumental with Demucs");
            $instrumentalPath = $this->separateInstrumental($audioPath);

            // Progression: 90%
            $this->project->update(['instrumental_progress' => 90]);

            // 3. Sauvegarder dans storage public (pour accès CORS)
            $storagePath = "instrumental/{$this->project->id}/instrumental.wav";
            Storage::disk('public')->put($storagePath, file_get_contents($instrumentalPath));

            // Nettoyage fichiers temporaires
            @unlink($audioPath);
            @unlink($instrumentalPath);

            // Progression: 100% - Terminé
            $this->project->update([
                'instrumental_audio_path' => $storagePath,
                'instrumental_status' => 'completed',
                'instrumental_progress' => 100,
            ]);

            Log::info("[ExtractInstrumental] Completed successfully for project {$this->project->id}");
        } catch (\Exception $e) {
            Log::error("[ExtractInstrumental] Failed for project {$this->project->id}: " . $e->getMessage());

            $this->project->update([
                'instrumental_status' => 'failed',
                'instrumental_progress' => 0,
            ]);

            throw $e;
        }
    }

    /**
     * Extraire l'audio de la vidéo avec FFmpeg
     */
    private function extractAudioFromVideo(string $videoPath): string
    {
        $tempAudio = tempnam(sys_get_temp_dir(), 'audio_') . '.wav';

        $cmd = [
            'ffmpeg',
            '-i',
            $videoPath,
            '-vn',  // Pas de vidéo
            '-acodec',
            'pcm_s16le',  // WAV 16-bit
            '-ar',
            '44100',  // 44.1kHz (requis par Demucs)
            '-ac',
            '2',  // Stereo (requis par Demucs)
            '-y',  // Overwrite
            $tempAudio
        ];

        $process = proc_open(
            $cmd,
            [
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w'],  // stderr
            ],
            $pipes
        );

        if (!is_resource($process)) {
            throw new \Exception("Failed to start FFmpeg process");
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            throw new \Exception("FFmpeg failed: " . $stderr);
        }

        return $tempAudio;
    }

    /**
     * Séparer instrumental avec le script Python
     */
    private function separateInstrumental(string $audioPath): string
    {
        $tempInstrumental = tempnam(sys_get_temp_dir(), 'instrumental_') . '.wav';
        $scriptPath = base_path('scripts/separate_instrumental.py');

        if (!file_exists($scriptPath)) {
            throw new \Exception("Script not found: {$scriptPath}");
        }

        $cmd = [
            'python3',
            $scriptPath,
            $audioPath,
            $tempInstrumental,
            '--model',
            'htdemucs'
        ];

        // Lancer le processus en arrière-plan
        $process = proc_open(
            $cmd,
            [
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w'],  // stderr
            ],
            $pipes
        );

        if (!is_resource($process)) {
            throw new \Exception("Failed to start Python process");
        }

        // Mettre les pipes en mode non-bloquant pour pouvoir lire pendant l'exécution
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        // Simuler une progression réaliste pendant que Demucs s'exécute
        $startTime = time();
        $estimatedDuration = 120; // Estimation: 2 minutes (ajustable selon durée vidéo)
        $stdout = '';
        $stderr = '';

        // Polling toutes les 3 secondes
        while (true) {
            // Vérifier si le processus est toujours en cours
            $status = proc_get_status($process);

            // Lire la sortie disponible
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            if (!$status['running']) {
                // Processus terminé
                break;
            }

            // Calculer progression simulée (30% -> 85%) pendant l'exécution
            $elapsed = time() - $startTime;
            $progressFraction = min($elapsed / $estimatedDuration, 1.0);
            // Courbe réaliste: plus lent au début, accélère vers la fin
            $progressPercent = 30 + (55 * $progressFraction);

            $this->project->update(['instrumental_progress' => (int)$progressPercent]);

            Log::info("[ExtractInstrumental] Demucs progress: {$progressPercent}% (elapsed: {$elapsed}s)");

            // Attendre 3 secondes avant la prochaine mise à jour
            sleep(3);
        }

        // Récupérer le reste de la sortie
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            Log::error("[ExtractInstrumental] Python stderr: " . $stderr);
            throw new \Exception("Instrumental separation failed");
        }

        Log::info("[ExtractInstrumental] Python output: " . $stderr);

        return $tempInstrumental;
    }
}
