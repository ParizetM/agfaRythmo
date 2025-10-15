<?php

namespace App\Services;

class SrtParser
{
    /**
     * Parse un fichier SRT et retourne un tableau de timecodes
     *
     * @param string $content Contenu du fichier SRT
     * @return array Array de ['start' => float, 'end' => float, 'text' => string]
     * @throws \Exception Si le format est invalide
     */
    public function parse(string $content): array
    {
        // Normalise les fins de ligne
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Divise en blocs (séparés par des lignes vides)
        $blocks = preg_split('/\n\s*\n/', trim($content));

        $timecodes = [];

        foreach ($blocks as $block) {
            $lines = explode("\n", trim($block));

            // Chaque bloc doit avoir au moins 3 lignes : numéro, timecode, texte
            if (count($lines) < 3) {
                continue;
            }

            // Ligne 1 : numéro de séquence (on l'ignore)
            // Ligne 2 : timecode au format "00:00:01,000 --> 00:00:04,000"
            $timecodeLine = $lines[1];

            if (!preg_match('/(\d{2}:\d{2}:\d{2},\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2},\d{3})/', $timecodeLine, $matches)) {
                // Format invalide, on passe au suivant
                continue;
            }

            $startTime = $this->srtTimeToSeconds($matches[1]);
            $endTime = $this->srtTimeToSeconds($matches[2]);

            // Le texte peut être sur plusieurs lignes (lignes 3+)
            $text = implode(' ', array_slice($lines, 2));

            // Nettoie le texte des balises HTML éventuelles
            $text = strip_tags($text);
            $text = trim($text);

            if ($startTime !== null && $endTime !== null && !empty($text)) {
                $timecodes[] = [
                    'start' => $startTime,
                    'end' => $endTime,
                    'text' => $text
                ];
            }
        }

        if (empty($timecodes)) {
            throw new \Exception('Aucun timecode valide trouvé dans le fichier SRT');
        }

        return $timecodes;
    }

    /**
     * Convertit un timecode SRT (HH:MM:SS,mmm) en secondes
     *
     * @param string $srtTime Timecode au format SRT
     * @return float|null Temps en secondes ou null si invalide
     */
    private function srtTimeToSeconds(string $srtTime): ?float
    {
        // Format : HH:MM:SS,mmm
        if (!preg_match('/(\d{2}):(\d{2}):(\d{2}),(\d{3})/', $srtTime, $matches)) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];
        $seconds = (int) $matches[3];
        $milliseconds = (int) $matches[4];

        $totalSeconds = $hours * 3600 + $minutes * 60 + $seconds + ($milliseconds / 1000);

        return round($totalSeconds, 3);
    }
}
