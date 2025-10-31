<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Timecode;
use App\Services\TranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class TranslateDialogues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes max

    protected Project $project;
    protected string $targetLang;
    protected ?string $sourceLang;
    protected bool $useCharacterContext;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Project $project,
        string $targetLang,
        ?string $sourceLang = null,
        bool $useCharacterContext = true
    ) {
        $this->project = $project;
        $this->targetLang = $targetLang;
        $this->sourceLang = $sourceLang;
        $this->useCharacterContext = $useCharacterContext;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Début de la traduction pour le projet {$this->project->id}");

        try {
            // Vérifier si le job a été annulé avant de commencer
            if ($this->checkCancellation()) {
                return;
            }

            // Initialiser le statut
            $this->updateStatus('processing', 0, 'Initialisation de la traduction...');

            // Récupérer tous les timecodes du projet
            $timecodes = Timecode::where('project_id', $this->project->id)->get();

            if ($timecodes->isEmpty()) {
                throw new Exception('Aucun timecode à traduire');
            }

            $totalTimecodes = $timecodes->count();
            Log::info("Nombre de timecodes à traduire : {$totalTimecodes}");

            // Créer le service de traduction
            $translationService = new TranslationService();

            // Auto-détection de la langue source si non spécifiée
            if (!$this->sourceLang || $this->sourceLang === 'auto') {
                $this->updateStatus('processing', 5, 'Détection de la langue source...');

                // Utiliser les 5 premiers timecodes non vides pour meilleure détection
                $sampleTexts = $timecodes
                    ->filter(fn($tc) => !empty($tc->text))
                    ->take(5)
                    ->pluck('text')
                    ->join(' ');

                if ($sampleTexts) {
                    $this->sourceLang = $translationService->detectLanguage($sampleTexts);
                    Log::info("Langue source détectée : {$this->sourceLang}", [
                        'sample_length' => strlen($sampleTexts),
                        'timecodes_used' => min(5, $timecodes->filter(fn($tc) => !empty($tc->text))->count())
                    ]);
                } else {
                    $this->sourceLang = 'en'; // Fallback par défaut
                    Log::warning("Aucun texte trouvé pour détection langue, fallback vers 'en'");
                }
            }

            // Sauvegarder les langues dans le projet
            $this->project->update([
                'source_language' => $this->sourceLang,
                'target_language' => $this->targetLang,
            ]);

            // Préparer le contexte des personnages si activé
            $charactersContext = [];
            if ($this->useCharacterContext) {
                $characters = $this->project->characters;
                foreach ($characters as $character) {
                    $charactersContext[$character->id] = $character->name;
                }

                if (!empty($charactersContext)) {
                    Log::info("Utilisation du contexte personnages", ['characters' => $charactersContext]);
                }
            }

            // Récupérer le provider utilisé pour l'afficher
            $provider = config('ai.translation.provider', 'unknown');
            $providerName = match($provider) {
                'nllb' => 'NLLB-200',
                'mymemory' => 'MyMemory',
                default => ucfirst($provider),
            };

            // NLLB supporte le batch, MyMemory non
            $useBatchMode = ($provider === 'nllb');

            if ($useBatchMode) {
                Log::info("Mode traduction: BATCH (NLLB-200)", [
                    'provider' => $provider,
                    'batch_size' => $totalTimecodes
                ]);

                // Mode BATCH pour NLLB-200 (batch natif)
                $translatedCount = 0;
                $failedCount = 0;

                try {
                    // Préparer les textes à traduire
                    $textsToTranslate = [];
                    $timecodeIds = [];

                    foreach ($timecodes as $timecode) {
                        if (!empty($timecode->text)) {
                            $textsToTranslate[] = $timecode->text;
                            $timecodeIds[] = $timecode->id;
                        }
                    }

                    if (empty($textsToTranslate)) {
                        throw new Exception('Aucun texte à traduire');
                    }

                    $this->updateStatus('processing', 10, "Traduction batch avec {$providerName}...");

                    // Contexte global (non utilisé par NLLB mais passé pour compatibilité)
                    $globalContext = null;
                    if (!empty($charactersContext)) {
                        $charactersList = implode(', ', $charactersContext);
                        $globalContext = "Film dialogue. Characters: {$charactersList}";
                    }

                    // Traduire tout en batch (1 seule requête API pour DeepL ≤50, Google illimité)
                    $translatedTexts = $translationService->translateBatch(
                        $textsToTranslate,
                        $this->targetLang,
                        $this->sourceLang,
                        $globalContext
                    );

                    $this->updateStatus('processing', 80, "Application des traductions...");

                    // Appliquer les traductions
                    foreach ($timecodeIds as $index => $timecodeId) {
                        if (isset($translatedTexts[$index])) {
                            $timecode = Timecode::find($timecodeId);
                            if ($timecode) {
                                $timecode->update(['text' => $translatedTexts[$index]]);
                                $translatedCount++;
                            }
                        }
                    }

                } catch (Exception $e) {
                    Log::error("Erreur batch traduction: " . $e->getMessage());
                    throw $e;
                }

            } else {
                Log::info("Mode traduction: PHRASE PAR PHRASE", ['provider' => $provider]);

                // Mode PHRASE PAR PHRASE pour MyMemory/LibreTranslate
                $translatedCount = 0;
                $failedCount = 0;

                foreach ($timecodes as $index => $timecode) {
                    // Vérifier annulation toutes les 5 phrases
                    if ($index % 5 === 0 && $this->checkCancellation()) {
                        return;
                    }

                    // Ignorer les timecodes vides
                    if (empty($timecode->text)) {
                        continue;
                    }

                    // Calculer progression
                    $progress = 10 + (int)((($translatedCount + $failedCount) / $totalTimecodes) * 90);
                    $this->updateStatus(
                        'processing',
                        $progress,
                        "Traduction ({$providerName})... {$translatedCount}/{$totalTimecodes}"
                    );

                    try {
                        // Préparer le contexte
                        $context = [];
                        if ($this->useCharacterContext && $timecode->character_id && isset($charactersContext[$timecode->character_id])) {
                            $context['character'] = $charactersContext[$timecode->character_id];
                        }

                        // Traduire le texte
                        $translatedText = $translationService->translate(
                            $timecode->text,
                            $this->targetLang,
                            $this->sourceLang,
                            $context
                        );

                        // Mettre à jour le timecode
                        $timecode->update(['text' => $translatedText]);
                        $translatedCount++;

                    } catch (Exception $e) {
                        $failedCount++;
                        Log::error("Erreur traduction timecode {$timecode->id}: " . $e->getMessage());
                    }

                    // Petite pause pour éviter rate limiting
                    usleep(100000); // 100ms
                }
            }

            // Traduction terminée
            $message = "{$translatedCount} timecode(s) traduit(s) avec {$providerName}";
            if ($failedCount > 0) {
                $message .= ", {$failedCount} échec(s)";
            }
            $message .= " ({$this->sourceLang} → {$this->targetLang})";

            $this->updateStatus('completed', 100, $message);

            Log::info("Traduction terminée pour le projet {$this->project->id}", [
                'translated' => $translatedCount,
                'failed' => $failedCount,
                'source_lang' => $this->sourceLang,
                'target_lang' => $this->targetLang,
            ]);

        } catch (Exception $e) {
            Log::error("Erreur lors de la traduction pour le projet {$this->project->id}: " . $e->getMessage());

            $this->updateStatus(
                'failed',
                0,
                "Erreur: " . $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Vérifier si le job a été annulé
     */
    private function checkCancellation(): bool
    {
        $this->project->refresh();

        if ($this->project->translation_status === 'cancelled') {
            Log::info("Traduction annulée pour le projet {$this->project->id}");
            $this->updateStatus('cancelled', 0, 'Traduction annulée par l\'utilisateur');
            return true;
        }

        return false;
    }

    /**
     * Mettre à jour le statut de traduction
     */
    private function updateStatus(string $status, int $progress, string $message): void
    {
        $this->project->update([
            'translation_status' => $status,
            'translation_progress' => $progress,
            'translation_message' => $message,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error("Job de traduction échoué pour le projet {$this->project->id}", [
            'error' => $exception?->getMessage(),
        ]);

        $this->updateStatus(
            'failed',
            0,
            $exception?->getMessage() ?? 'Erreur inconnue'
        );
    }
}
