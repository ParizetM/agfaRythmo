<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Timecode;
use App\Models\Character;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExtractDialogues implements ShouldQueue
{
    use Queueable;

    /**
     * Palette de couleurs distinctes pour les personnages auto-générés
     */
    private const COLOR_PALETTE = [
        ['bg' => '#EF4444', 'text' => '#FFFFFF'], // Rouge
        ['bg' => '#3B82F6', 'text' => '#FFFFFF'], // Bleu
        ['bg' => '#10B981', 'text' => '#FFFFFF'], // Vert
        ['bg' => '#F59E0B', 'text' => '#FFFFFF'], // Orange
        ['bg' => '#8B5CF6', 'text' => '#FFFFFF'], // Violet
        ['bg' => '#EC4899', 'text' => '#FFFFFF'], // Rose
        ['bg' => '#14B8A6', 'text' => '#FFFFFF'], // Turquoise
        ['bg' => '#F97316', 'text' => '#FFFFFF'], // Orange foncé
        ['bg' => '#6366F1', 'text' => '#FFFFFF'], // Indigo
        ['bg' => '#84CC16', 'text' => '#000000'], // Lime
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Project $project,
        public string $language = 'auto',
        public int $maxSpeakers = 10,
        public string $whisperModel = 'tiny'
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Début de l'extraction de dialogues pour le projet {$this->project->id}");

            // Vérifier si l'extraction a été annulée avant de commencer
            $this->project->refresh();
            if ($this->project->dialogue_extraction_status === 'cancelled') {
                Log::info("Extraction annulée avant le démarrage pour le projet {$this->project->id}");
                return;
            }

            // Mettre le statut à "processing" avec progression 0%
            $this->project->update([
                'dialogue_extraction_status' => 'processing',
                'dialogue_extraction_progress' => 0,
                'dialogue_extraction_message' => 'Préparation de l\'extraction...'
            ]);

            // Récupérer le chemin complet de la vidéo
            $videoPath = storage_path('app/private/public/videos/' . $this->project->video_path);

            if (!file_exists($videoPath)) {
                throw new \Exception("Fichier vidéo introuvable: {$videoPath}");
            }

            // Vérifier annulation
            $this->checkCancellation();

            // Progression : 5% - Fichier vidéo trouvé
            $this->updateProgress(5, 'Fichier vidéo validé, préparation du script Python...');

            // Préparer les chemins
            $scriptPath = base_path('scripts/extract_dialogues.py');
            $outputJsonPath = storage_path('app/temp_dialogues_' . $this->project->id . '.json');
            $pythonLogPath = storage_path('app/temp_python_log_' . $this->project->id . '.txt');

            if (!file_exists($scriptPath)) {
                throw new \Exception("Script Python introuvable: {$scriptPath}");
            }

            // Vérifier annulation
            $this->checkCancellation();

            // Progression : 10% - Lancement du script Python
            $this->updateProgress(10, 'Lancement de l\'extraction avec Whisper...');

            // Construire la commande Python (simple)
            $command = sprintf(
                'python3 %s %s %s --model %s --language %s --max-speakers %d',
                escapeshellarg($scriptPath),
                escapeshellarg($videoPath),
                escapeshellarg($outputJsonPath),
                escapeshellarg($this->whisperModel),
                escapeshellarg($this->language),
                $this->maxSpeakers
            );

            Log::info("Exécution du script Python: {$command}");

            // Exécuter le script Python (peut prendre plusieurs minutes)
            // Simuler la progression pendant l'exécution
            $this->executeWithProgress($command, $pythonLogPath);

            // Vérifier annulation après Python
            $this->checkCancellation();

            // Progression : 85% - Lecture des résultats
            $this->updateProgress(85, 'Analyse terminée, lecture des dialogues...');

            // Lire le fichier JSON de résultats
            if (!file_exists($outputJsonPath)) {
                throw new \Exception("Fichier de résultats JSON introuvable");
            }

            $dialoguesData = json_decode(file_get_contents($outputJsonPath), true);

            // Nettoyer les fichiers temporaires
            if (file_exists($outputJsonPath)) {
                unlink($outputJsonPath);
            }
            if (file_exists($pythonLogPath)) {
                unlink($pythonLogPath);
            }

            if (empty($dialoguesData['dialogues'])) {
                Log::warning("Aucun dialogue extrait pour le projet {$this->project->id}");
                $this->project->update([
                    'dialogue_extraction_status' => 'completed',
                    'dialogue_extraction_progress' => 100,
                    'dialogue_extraction_message' => 'Aucun dialogue détecté dans la vidéo'
                ]);
                return;
            }

            // Vérifier annulation
            $this->checkCancellation();

            // Progression : 90% - Création des personnages et timecodes
            $this->updateProgress(90, 'Création des personnages et timecodes...');

            // Créer les personnages automatiquement
            $speakerCharacters = $this->createCharactersFromSpeakers(
                $dialoguesData['speakers'] ?? ['SPEAKER_00']
            );

            // Créer les timecodes avec assignation personnages
            $this->createTimecodesFromDialogues(
                $dialoguesData['dialogues'],
                $speakerCharacters
            );

            Log::info("Extraction terminée pour le projet {$this->project->id}. " .
                     count($dialoguesData['dialogues']) . " dialogues extraits, " .
                     count($speakerCharacters) . " personnages créés.");

            // Stocker la langue détectée dans project_settings
            $settings = $this->project->project_settings ?? [];
            $detectedLanguage = $dialoguesData['metadata']['language'] ?? $dialoguesData['language'] ?? 'unknown';
            $settings['last_extraction_language'] = $detectedLanguage;

            // Progression : 100% - Terminé
            $this->project->update([
                'dialogue_extraction_status' => 'completed',
                'dialogue_extraction_progress' => 100,
                'dialogue_extraction_message' => sprintf(
                    '%d dialogue(s) extrait(s), %d personnage(s) détecté(s)',
                    count($dialoguesData['dialogues']),
                    count($speakerCharacters)
                ),
                'project_settings' => $settings
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'extraction de dialogues pour le projet {$this->project->id}: " . $e->getMessage());

            // Rollback : supprimer les timecodes et characters créés par ce job
            $this->rollbackCreatedData();

            // Mettre le statut à "failed"
            $this->project->update([
                'dialogue_extraction_status' => 'failed',
                'dialogue_extraction_progress' => 0,
                'dialogue_extraction_message' => 'Erreur : ' . $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Vérifier si l'extraction a été annulée
     */
    private function checkCancellation(): void
    {
        $this->project->refresh();
        if ($this->project->dialogue_extraction_status === 'cancelled') {
            Log::info("Extraction annulée pour le projet {$this->project->id}");

            // Nettoyer les données créées
            $this->rollbackCreatedData();

            throw new \Exception('Extraction annulée par l\'utilisateur');
        }
    }

    /**
     * Mettre à jour la progression
     */
    private function updateProgress(int $progress, string $message): void
    {
        $this->project->update([
            'dialogue_extraction_progress' => $progress,
            'dialogue_extraction_message' => $message
        ]);
    }

    /**
     * Exécuter une commande avec simulation de progression
     */
    private function executeWithProgress(string $command, string $logPath): void
    {
        // Préparer les variables d'environnement
        $env = getenv(); // Récupérer toutes les variables

        // Passer AI_DIARIZATION_METHOD au script Python
        $env['AI_DIARIZATION_METHOD'] = config('ai.diarization_method', 'mfcc');

        // Si HF_TOKEN est configuré, on doit le passer explicitement
        if ($hfToken = env('HF_TOKEN')) {
            $env['HF_TOKEN'] = trim($hfToken, "'\"");
            Log::info("HF_TOKEN configuré pour la diarization");
        }

        Log::info("Diarization method: {$env['AI_DIARIZATION_METHOD']}");

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w'],  // stderr
            ],
            $pipes,
            null, // working directory
            $env  // variables d'environnement
        );

        if (!is_resource($process)) {
            throw new \Exception("Impossible de lancer le script Python");
        }

        fclose($pipes[0]); // Fermer stdin

        // Mettre stderr en mode non-bloquant pour lecture
        stream_set_blocking($pipes[2], false);

        // Simuler la progression pendant l'exécution (10% -> 80%)
        $startTime = time();
        $progressSteps = [
            0 => 'Extraction audio en cours...',
            20 => 'Transcription Whisper (20%)...',
            40 => 'Transcription Whisper (40%)...',
            60 => 'Transcription Whisper (60%)...',
            70 => 'Diarization des locuteurs...',
        ];

        $stderrBuffer = '';

        while (proc_get_status($process)['running']) {
            $elapsed = time() - $startTime;
            $estimatedProgress = min(80, 10 + ($elapsed * 2)); // Progresser ~2% par seconde

            // Lire stderr sans bloquer
            $stderr = stream_get_contents($pipes[2]);
            if ($stderr !== false && $stderr !== '') {
                $stderrBuffer .= $stderr;
                // Logger les messages stderr en temps réel
                Log::info("Python stderr: " . trim($stderr));
            }

            // Trouver le message correspondant
            $message = 'Extraction en cours...';
            foreach ($progressSteps as $threshold => $msg) {
                if ($estimatedProgress >= $threshold) {
                    $message = $msg;
                }
            }

            $this->updateProgress((int) $estimatedProgress, $message);

            // Vérifier annulation toutes les 2 secondes
            $this->checkCancellation();

            sleep(2);
        }

        // Lire le reste de stderr après la fin du process
        $remainingStderr = stream_get_contents($pipes[2]);
        if ($remainingStderr !== false && $remainingStderr !== '') {
            $stderrBuffer .= $remainingStderr;
            Log::info("Python stderr (final): " . trim($remainingStderr));
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        // Récupérer le code de sortie
        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            $errorLog = file_exists($logPath) ? file_get_contents($logPath) : 'Log indisponible';
            $fullError = "Code de sortie: {$returnCode}\n\nStderr:\n{$stderrBuffer}\n\nLog:\n{$errorLog}";
            Log::error("Erreur script Python: {$fullError}");
            throw new \Exception("Erreur script Python (code {$returnCode}). Consultez les logs pour plus de détails.");
        }

        // Si pas d'erreur mais qu'il y a des warnings dans stderr, les logger
        if (!empty($stderrBuffer)) {
            Log::warning("Python stderr (success but with warnings): {$stderrBuffer}");
        }
    }

    /**
     * Créer les personnages automatiquement à partir des speakers détectés
     */
    private function createCharactersFromSpeakers(array $speakers): array
    {
        $characters = [];

        foreach ($speakers as $index => $speakerName) {
            // Utiliser la palette de couleurs cycliquement
            $colorIndex = $index % count(self::COLOR_PALETTE);
            $colors = self::COLOR_PALETTE[$colorIndex];

            // Nom du personnage : "Speaker 1", "Speaker 2", etc.
            $characterName = 'Locuteur ' . ($index + 1);

            $character = Character::create([
                'project_id' => $this->project->id,
                'name' => $characterName,
                'color' => $colors['bg'],
                'text_color' => $colors['text'],
            ]);

            $characters[$speakerName] = $character;

            Log::info("Personnage créé: {$characterName} ({$speakerName}) avec couleur {$colors['bg']}");
        }

        return $characters;
    }

    /**
     * Créer les timecodes à partir des dialogues extraits
     */
    private function createTimecodesFromDialogues(array $dialogues, array $speakerCharacters): void
    {
        $totalDialogues = count($dialogues);
        $rythmoLinesCount = $this->project->rythmo_lines_count;
        $speakersCount = count($speakerCharacters);

        Log::info("Création de {$totalDialogues} timecodes. Lignes rythmo: {$rythmoLinesCount}, Speakers: {$speakersCount}");

        foreach ($dialogues as $index => $dialogue) {
            // Déterminer la ligne rythmo
            $lineNumber = $this->determineLineNumber($dialogue, $speakersCount, $rythmoLinesCount);

            // Récupérer le personnage correspondant
            $character = $speakerCharacters[$dialogue['speaker']] ?? null;

            Timecode::create([
                'project_id' => $this->project->id,
                'line_number' => $lineNumber,
                'start' => $dialogue['start'],
                'end' => $dialogue['end'],
                'text' => $dialogue['text'],
                'character_id' => $character?->id,
                'show_character' => true,
                'separator_positions' => null,
            ]);

            // Mise à jour progressive : 90% -> 98%
            if ($index % 10 === 0 || $index === $totalDialogues - 1) {
                $progress = 90 + (($index + 1) / $totalDialogues) * 8;
                $this->updateProgress((int) $progress, "Création timecodes: " . ($index + 1) . "/{$totalDialogues}");
            }

            // Vérifier annulation tous les 10 timecodes
            if ($index % 10 === 0) {
                $this->checkCancellation();
            }
        }
    }

    /**
     * Déterminer le numéro de ligne rythmo pour un dialogue
     */
    private function determineLineNumber(array $dialogue, int $speakersCount, int $rythmoLinesCount): int
    {
        // Si assez de lignes rythmo pour chaque speaker, distribuer
        if ($rythmoLinesCount >= $speakersCount) {
            // Extraire le numéro du speaker (SPEAKER_00 -> 0, SPEAKER_01 -> 1, etc.)
            preg_match('/SPEAKER_(\d+)/', $dialogue['speaker'], $matches);
            $speakerIndex = isset($matches[1]) ? (int) $matches[1] : 0;

            // Ligne = index speaker + 1 (1-based), limité au nombre de lignes
            return min($speakerIndex + 1, $rythmoLinesCount);
        }

        // Sinon, tout mettre sur la ligne 1
        return 1;
    }

    /**
     * Rollback : supprimer les données créées en cas d'erreur ou annulation
     */
    private function rollbackCreatedData(): void
    {
        Log::info("Rollback : suppression des timecodes et personnages créés pour le projet {$this->project->id}");

        // Supprimer tous les timecodes du projet (seulement si créés récemment)
        Timecode::where('project_id', $this->project->id)
            ->where('created_at', '>', now()->subMinutes(30))
            ->delete();

        // Supprimer tous les personnages du projet (seulement si créés récemment)
        Character::where('project_id', $this->project->id)
            ->where('created_at', '>', now()->subMinutes(30))
            ->delete();
    }
}
