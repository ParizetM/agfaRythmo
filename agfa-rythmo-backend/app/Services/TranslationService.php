<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service de traduction automatique
 *
 * Providers supportés :
 * - NLLB-200 : Meta AI, 200 langues, gratuit, local (~2GB), batch natif ⭐⭐⭐⭐
 * - MyMemory : Gratuit 10k chars/jour, aucune API key, fallback simple ⭐⭐⭐
 */
class TranslationService
{
    private string $provider;
    private ?string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->provider = config('ai.translation.provider', 'nllb');
        $this->apiKey = config('ai.translation.api_key');
        $this->apiUrl = config('ai.translation.api_url', 'https://libretranslate.com');
    }

    /**
     * Traduire un texte
     *
     * @param string $text Texte à traduire
     * @param string $targetLang Langue cible (en, fr, zh, ja, etc.)
     * @param string|null $sourceLang Langue source (null = auto-détection)
     * @param array $context Contexte optionnel (personnages, etc.)
     * @return string Texte traduit
     * @throws Exception
     */
    public function translate(
        string $text,
        string $targetLang,
        ?string $sourceLang = null,
        array $context = []
    ): string {
        // NLLB-200 : Provider principal (gratuit, local, 200 langues)
        if (strtolower($this->provider) === 'nllb') {
            return $this->translateNLLB($text, $targetLang, $sourceLang);
        }

        // MyMemory : Fallback simple (gratuit, 10k chars/jour)
        if ($this->provider === 'mymemory') {
            return $this->translateMyMemory($text, $targetLang, $sourceLang);
        }

        // Provider inconnu : fallback MyMemory
        Log::warning("Provider {$this->provider} non supporté, fallback vers MyMemory");
        return $this->translateMyMemory($text, $targetLang, $sourceLang);
    }

    /**
     * Traduire un batch de textes
     *
     * @param array $texts Tableau de textes à traduire
     * @param string $targetLang Langue cible
     * @param string|null $sourceLang Langue source (null = auto-détection)
     * @param string|null $globalContext Contexte global appliqué à tous les textes
     * @return array Tableau de textes traduits dans le même ordre
     * @throws Exception
     */
    public function translateBatch(
        array $texts,
        string $targetLang,
        ?string $sourceLang = null,
        ?string $globalContext = null
    ): array {
        // NLLB-200 : Batch natif
        if ($this->provider === 'nllb') {
            return $this->translateBatchNLLB($texts, $targetLang, $sourceLang, $globalContext);
        }

        // MyMemory : Pas de batch, traduire un par un
        Log::warning("Provider {$this->provider} ne supporte pas batch, traduction individuelle");

        $translations = [];
        foreach ($texts as $text) {
            $translations[] = $this->translate($text, $targetLang, $sourceLang);
        }

        return $translations;
    }

    /**
     * Détecter la langue d'un texte
     *
     * @param string $text
     * @return string Code langue (en, fr, zh, ja, etc.)
     * @throws Exception
     */
    public function detectLanguage(string $text): string
    {
        // Auto-détection simple (regex basique)
        return $this->detectLanguageSimple($text);
    }

    /**
     * Obtenir les langues supportées par le provider
     *
     * @return array [['code' => 'en', 'name' => 'English'], ...]
     * @throws Exception
     */
    public function getSupportedLanguages(): array
    {
        // NLLB-200 : 200 langues supportées
        if ($this->provider === 'nllb') {
            return $this->getSupportedLanguagesNLLB();
        }

        // MyMemory : Langues communes
        return $this->getSupportedLanguagesMyMemory();
    }

    // ============================================================================
    // NLLB-200 (Meta AI) - Provider principal
    // ============================================================================

    /**
     * Traduction avec NLLB-200 (Meta AI) via Python script
     *
     * @param string $text Texte à traduire
     * @param string $targetLang Langue cible (code ISO)
     * @param string|null $sourceLang Langue source (code ISO)
     * @return string Texte traduit
     * @throws Exception
     */
    private function translateNLLB(
        string $text,
        string $targetLang,
        ?string $sourceLang = null
    ): string {
        $scriptPath = base_path('scripts/translate_nllb.py');

        if (!file_exists($scriptPath)) {
            throw new Exception("NLLB script not found at {$scriptPath}");
        }

        // Auto-détection si pas de langue source
        if (!$sourceLang) {
            $sourceLang = $this->detectLanguageSimple($text);
        }

        // Échapper les arguments pour shell
        $text = addslashes($text);
        $source = escapeshellarg($sourceLang);
        $target = escapeshellarg($targetLang);
        $modelSize = config('ai.translation.nllb_model_size', '600M');

        // Construire la commande
        $command = sprintf(
            'python3 %s --source %s --target %s --text %s --model-size %s 2>&1',
            escapeshellarg($scriptPath),
            $source,
            $target,
            escapeshellarg($text),
            escapeshellarg($modelSize)
        );

        Log::info("Exécution NLLB script", [
            'source' => $sourceLang,
            'target' => $targetLang,
            'text_length' => strlen($text),
        ]);

        // Exécuter le script Python
        $output = shell_exec($command);

        if ($output === null) {
            throw new Exception("Failed to execute NLLB script");
        }

        // Parser le JSON de sortie (dernière ligne seulement, car stderr contient des logs)
        $lines = explode("\n", trim($output));
        $jsonLine = end($lines);

        $result = json_decode($jsonLine, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("NLLB JSON parse error", [
                'output' => $output,
                'json_line' => $jsonLine,
                'error' => json_last_error_msg(),
            ]);
            throw new Exception("Invalid JSON response from NLLB script: " . json_last_error_msg());
        }

        if (!isset($result['success']) || !$result['success']) {
            $error = $result['error'] ?? 'Unknown error';
            throw new Exception("NLLB translation failed: {$error}");
        }

        return $result['translation'];
    }

    /**
     * Traduction batch avec NLLB-200
     *
     * @param array $texts Tableau de textes à traduire
     * @param string $targetLang Langue cible
     * @param string|null $sourceLang Langue source
     * @param string|null $globalContext Contexte global (non utilisé par NLLB)
     * @return array Tableau de textes traduits
     * @throws Exception
     */
    private function translateBatchNLLB(
        array $texts,
        string $targetLang,
        ?string $sourceLang = null,
        ?string $globalContext = null
    ): array {
        $scriptPath = base_path('scripts/translate_nllb.py');

        if (!file_exists($scriptPath)) {
            throw new Exception("NLLB script not found at {$scriptPath}");
        }

        // Auto-détection si pas de langue source
        if (!$sourceLang) {
            // Utiliser le premier texte pour détecter la langue
            $sourceLang = $this->detectLanguageSimple($texts[0] ?? '');
        }

        // Créer un fichier temporaire pour le batch
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $inputFile = $tempDir . '/nllb_batch_' . uniqid() . '.json';
        $outputFile = $tempDir . '/nllb_output_' . uniqid() . '.json';

        try {
            // Écrire les textes dans le fichier JSON
            file_put_contents($inputFile, json_encode($texts, JSON_UNESCAPED_UNICODE));

            $source = escapeshellarg($sourceLang);
            $target = escapeshellarg($targetLang);
            $modelSize = config('ai.translation.nllb_model_size', '600M');

            // Construire la commande
            $command = sprintf(
                'python3 %s --source %s --target %s --batch-file %s --output %s --model-size %s 2>&1',
                escapeshellarg($scriptPath),
                $source,
                $target,
                escapeshellarg($inputFile),
                escapeshellarg($outputFile),
                escapeshellarg($modelSize)
            );

            Log::info("Exécution NLLB batch script", [
                'source' => $sourceLang,
                'target' => $targetLang,
                'batch_size' => count($texts),
            ]);

            // Exécuter le script Python
            $output = shell_exec($command);

            if ($output === null) {
                throw new Exception("Failed to execute NLLB batch script");
            }

            // Lire le fichier de sortie JSON
            if (!file_exists($outputFile)) {
                Log::error("NLLB output file not found", [
                    'output' => $output,
                    'expected_file' => $outputFile,
                ]);
                throw new Exception("NLLB output file not created");
            }

            $outputContent = file_get_contents($outputFile);
            $result = json_decode($outputContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("NLLB batch JSON parse error", [
                    'output' => $outputContent,
                    'error' => json_last_error_msg(),
                ]);
                throw new Exception("Invalid JSON response from NLLB batch script");
            }

            if (!isset($result['success']) || !$result['success']) {
                $error = $result['error'] ?? 'Unknown error';
                throw new Exception("NLLB batch translation failed: {$error}");
            }

            return $result['translations'];

        } finally {
            // Nettoyer les fichiers temporaires
            if (file_exists($inputFile)) {
                unlink($inputFile);
            }
            if (file_exists($outputFile)) {
                unlink($outputFile);
            }
        }
    }

    /**
     * Langues supportées par NLLB-200
     */
    private function getSupportedLanguagesNLLB(): array
    {
        // NLLB-200 supporte 200 langues, voici les principales
        return [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'fr', 'name' => 'Français'],
            ['code' => 'es', 'name' => 'Español'],
            ['code' => 'de', 'name' => 'Deutsch'],
            ['code' => 'it', 'name' => 'Italiano'],
            ['code' => 'pt', 'name' => 'Português'],
            ['code' => 'ru', 'name' => 'Русский'],
            ['code' => 'zh', 'name' => '中文'],
            ['code' => 'ja', 'name' => '日本語'],
            ['code' => 'ko', 'name' => '한국어'],
            ['code' => 'ar', 'name' => 'العربية'],
            ['code' => 'hi', 'name' => 'हिन्दी'],
            ['code' => 'nl', 'name' => 'Nederlands'],
            ['code' => 'pl', 'name' => 'Polski'],
            ['code' => 'tr', 'name' => 'Türkçe'],
            ['code' => 'id', 'name' => 'Bahasa Indonesia'],
            ['code' => 'vi', 'name' => 'Tiếng Việt'],
            ['code' => 'th', 'name' => 'ไทย'],
            ['code' => 'uk', 'name' => 'Українська'],
            ['code' => 'cs', 'name' => 'Čeština'],
            ['code' => 'sv', 'name' => 'Svenska'],
            ['code' => 'no', 'name' => 'Norsk'],
            ['code' => 'da', 'name' => 'Dansk'],
            ['code' => 'fi', 'name' => 'Suomi'],
            ['code' => 'el', 'name' => 'Ελληνικά'],
        ];
    }

    // ============================================================================
    // MyMemory - Fallback gratuit
    // ============================================================================

    /**
     * Traduction via MyMemory (gratuit 10k chars/jour)
     */
    private function translateMyMemory(
        string $text,
        string $targetLang,
        ?string $sourceLang = null
    ): string {
        // MyMemory a une limite de 500 caractères par requête
        if (strlen($text) > 500) {
            throw new Exception("MyMemory ne supporte pas les textes > 500 caractères. Texte: " . strlen($text) . " chars");
        }

        $url = 'https://api.mymemory.translated.net/get';

        $params = [
            'q' => $text,
            'langpair' => ($sourceLang ?? 'auto') . '|' . $targetLang,
        ];

        Log::info("Traduction MyMemory", [
            'text_length' => strlen($text),
            'langpair' => $params['langpair'],
        ]);

        $response = Http::timeout(30)->get($url, $params);

        if (!$response->successful()) {
            throw new Exception('MyMemory API error: ' . $response->status());
        }

        $result = $response->json();

        // Vérifier si la réponse contient une erreur
        if (isset($result['responseStatus']) && $result['responseStatus'] != 200) {
            $error = $result['responseDetails'] ?? 'Unknown error';
            throw new Exception("MyMemory error: {$error}");
        }

        $translatedText = $result['responseData']['translatedText'] ?? '';

        // MyMemory retourne parfois l'erreur dans le texte traduit
        if (str_contains(strtolower($translatedText), 'limit reached') ||
            str_contains(strtolower($translatedText), 'quota')) {
            throw new Exception("MyMemory quota exceeded");
        }

        return $translatedText;
    }

    /**
     * Langues supportées par MyMemory
     */
    private function getSupportedLanguagesMyMemory(): array
    {
        // MyMemory supporte beaucoup de langues, retourner liste par défaut
        return $this->getDefaultLanguages();
    }

    // ============================================================================
    // Helpers communs
    // ============================================================================

    /**
     * Détection simple de langue par regex
     */
    private function detectLanguageSimple(string $text): string
    {
        // Détection basique par script Unicode
        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) {
            return 'zh'; // Chinois
        }
        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $text)) {
            return 'ja'; // Japonais
        }
        if (preg_match('/[\x{AC00}-\x{D7AF}]/u', $text)) {
            return 'ko'; // Coréen
        }
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ar'; // Arabe
        }
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $text)) {
            return 'ru'; // Russe
        }
        if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
            return 'hi'; // Hindi
        }
        if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $text)) {
            return 'th'; // Thaï
        }

        // Par défaut, anglais
        return 'en';
    }

    /**
     * Liste de langues par défaut
     */
    public function getDefaultLanguages(): array
    {
        return [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'fr', 'name' => 'Français'],
            ['code' => 'es', 'name' => 'Español'],
            ['code' => 'de', 'name' => 'Deutsch'],
            ['code' => 'it', 'name' => 'Italiano'],
            ['code' => 'pt', 'name' => 'Português'],
            ['code' => 'ru', 'name' => 'Русский'],
            ['code' => 'zh', 'name' => '中文'],
            ['code' => 'ja', 'name' => '日本語'],
            ['code' => 'ko', 'name' => '한국어'],
            ['code' => 'ar', 'name' => 'العربية'],
            ['code' => 'hi', 'name' => 'हिन्दी'],
            ['code' => 'nl', 'name' => 'Nederlands'],
            ['code' => 'pl', 'name' => 'Polski'],
            ['code' => 'tr', 'name' => 'Türkçe'],
        ];
    }
}
