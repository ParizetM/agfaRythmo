#!/usr/bin/env python3
"""
Script d'extraction automatique de dialogues avec Whisper + diarization
Optimisé pour serveurs avec 2GB RAM

Usage:
    python extract_dialogues.py <video_path> <output_json> [--model tiny] [--language auto] [--max-speakers 10]

Output JSON format:
    {
        "dialogues": [
            {
                "start": 1.5,
                "end": 4.2,
                "text": "Hello, how are you?",
                "speaker": "SPEAKER_00",
                "confidence": 0.95,
                "language": "en"
            },
            ...
        ],
        "speakers": ["SPEAKER_00", "SPEAKER_01", ...],
        "metadata": {
            "duration": 120.5,
            "total_dialogues": 42,
            "model": "tiny",
            "language": "auto"
        }
    }
"""

import argparse
import json
import os
import sys
import tempfile
import subprocess
from pathlib import Path
from typing import List, Dict, Optional
import gc

# Vérifier les dépendances
try:
    import whisper
    import torch
    import soundfile as sf
    import numpy as np
except ImportError:
    print("❌ Erreur: whisper ou torch non installé", file=sys.stderr)
    print("Installation: pip install openai-whisper torch soundfile numpy", file=sys.stderr)
    sys.exit(1)

# Diarization est optionnelle
DIARIZATION_AVAILABLE = False
try:
    from pyannote.audio import Pipeline
    DIARIZATION_AVAILABLE = True
except ImportError:
    print("⚠️  Warning: pyannote.audio non installé, diarization désactivée", file=sys.stderr)
    print("Installation: pip install pyannote.audio", file=sys.stderr)


class DialogueExtractor:
    """Extracteur de dialogues optimisé pour faible RAM"""

    # Modèles Whisper par taille (RAM requise approximative)
    MODELS = {
        'tiny': 1,      # ~1GB RAM
        'base': 1,      # ~1GB RAM
        'small': 2,     # ~2GB RAM
        'medium': 5,    # ~5GB RAM (non recommandé pour 2GB serveur)
        'large': 10,    # ~10GB RAM (impossible sur 2GB serveur)
    }

    # Langues supportées par Whisper
    SUPPORTED_LANGUAGES = [
        'auto', 'en', 'fr', 'es', 'de', 'it', 'pt', 'nl', 'ru', 'zh', 'ja', 'ko'
    ]

    def __init__(self, model_name: str = 'tiny', language: str = 'auto', max_speakers: int = 10):
        """
        Initialize dialogue extractor

        Args:
            model_name: Whisper model (tiny/base/small)
            language: Language code or 'auto' for detection
            max_speakers: Maximum number of speakers to detect
        """
        if model_name not in self.MODELS:
            raise ValueError(f"Model invalide. Choisir parmi: {list(self.MODELS.keys())}")

        if language not in self.SUPPORTED_LANGUAGES:
            raise ValueError(f"Langue non supportée: {language}")

        self.model_name = model_name
        self.language = None if language == 'auto' else language
        self.max_speakers = max_speakers
        self.model = None
        self.diarization_pipeline = None

        print(f"🔧 Configuration: model={model_name}, language={language}, max_speakers={max_speakers}")

    def _load_whisper_model(self):
        """Charger le modèle Whisper (lazy loading)"""
        if self.model is None:
            print(f"📥 Chargement du modèle Whisper '{self.model_name}'...")
            # Force CPU pour économiser RAM (GPU optionnel si disponible)
            device = "cuda" if torch.cuda.is_available() else "cpu"
            self.model = whisper.load_model(self.model_name, device=device)
            print(f"✅ Modèle chargé sur {device}")

    def _unload_whisper_model(self):
        """Décharger le modèle Whisper pour libérer RAM"""
        if self.model is not None:
            del self.model
            self.model = None
            gc.collect()
            if torch.cuda.is_available():
                torch.cuda.empty_cache()
            print("🗑️  Modèle Whisper déchargé (RAM libérée)")

    def extract_audio(self, video_path: str) -> str:
        """
        Extraire l'audio de la vidéo avec FFmpeg

        Args:
            video_path: Chemin vers la vidéo

        Returns:
            Chemin vers le fichier audio temporaire (WAV 16kHz mono)
        """
        print(f"🎵 Extraction audio depuis: {video_path}")

        if not os.path.exists(video_path):
            raise FileNotFoundError(f"Vidéo introuvable: {video_path}")

        # Créer fichier audio temporaire
        temp_audio = tempfile.NamedTemporaryFile(suffix='.wav', delete=False)
        audio_path = temp_audio.name
        temp_audio.close()

        # Extraire audio avec FFmpeg (16kHz mono pour Whisper)
        cmd = [
            'ffmpeg', '-i', video_path,
            '-vn',  # Pas de vidéo
            '-acodec', 'pcm_s16le',  # WAV 16-bit
            '-ar', '16000',  # 16kHz
            '-ac', '1',  # Mono
            '-y',  # Overwrite
            audio_path
        ]

        try:
            subprocess.run(cmd, check=True, capture_output=True, text=True)
            print(f"✅ Audio extrait: {audio_path}")
            return audio_path
        except subprocess.CalledProcessError as e:
            raise RuntimeError(f"Erreur FFmpeg: {e.stderr}")

    def transcribe_audio(self, audio_path: str) -> Dict:
        """
        Transcrire l'audio avec Whisper

        Args:
            audio_path: Chemin vers le fichier audio

        Returns:
            Dictionnaire avec segments transcrits
        """
        self._load_whisper_model()

        print(f"🎤 Transcription en cours...")

        # Options Whisper optimisées pour mémoire
        options = {
            'language': self.language,
            'task': 'transcribe',
            'verbose': False,
            'word_timestamps': True,  # Timestamps précis par mot
        }

        # Transcription
        result = self.model.transcribe(audio_path, **options)

        print(f"✅ Transcription terminée: {len(result['segments'])} segments")

        return result

    def apply_diarization(self, audio_path: str, transcription: Dict) -> List[Dict]:
        """
        Appliquer la diarization (séparation locuteurs)

        Args:
            audio_path: Chemin vers l'audio
            transcription: Résultat Whisper

        Returns:
            Liste de dialogues avec speakers assignés
        """
        if not DIARIZATION_AVAILABLE:
            print("", file=sys.stderr)
            print("╔════════════════════════════════════════════════════════════════╗", file=sys.stderr)
            print("║  ⚠️  DIARIZATION DÉSACTIVÉE                                   ║", file=sys.stderr)
            print("╠════════════════════════════════════════════════════════════════╣", file=sys.stderr)
            print("║  AI_DIARIZATION_ENABLED=false dans le fichier .env            ║", file=sys.stderr)
            print("║                                                                ║", file=sys.stderr)
            print("║  Résultat : UN SEUL locuteur (SPEAKER_00)                      ║", file=sys.stderr)
            print("║                                                                ║", file=sys.stderr)
            print("║  Pour activer la détection multi-locuteurs :                  ║", file=sys.stderr)
            print("║  1. Mettre AI_DIARIZATION_ENABLED=true dans .env              ║", file=sys.stderr)
            print("║  2. Configurer HF_TOKEN (voir .env.example)                   ║", file=sys.stderr)
            print("║  3. Installer: pip install pyannote.audio                     ║", file=sys.stderr)
            print("╚════════════════════════════════════════════════════════════════╝", file=sys.stderr)
            print("", file=sys.stderr)
            return self._assign_single_speaker(transcription)

        print("", file=sys.stderr)
        print("╔════════════════════════════════════════════════════════════════╗", file=sys.stderr)
        print("║  👥 DIARIZATION ACTIVÉE - Détection des locuteurs            ║", file=sys.stderr)
        print("╚════════════════════════════════════════════════════════════════╝", file=sys.stderr)
        print("", file=sys.stderr)

        try:
            # Charger le modèle de diarization si pas déjà fait
            if self.diarization_pipeline is None:
                from pyannote.audio import Pipeline
                print("📥 Chargement du modèle de diarization...", file=sys.stderr)

                # Nécessite un token HuggingFace (gratuit)
                # Export HF_TOKEN="your_token" dans .env ou environnement
                hf_token = os.getenv('HF_TOKEN') or os.getenv('HUGGINGFACE_TOKEN')

                # Nettoyer le token (enlever guillemets si présents)
                if hf_token:
                    hf_token = hf_token.strip().strip("'\"")

                if not hf_token:
                    print("⚠️  Warning: HF_TOKEN non trouvé, diarization peut échouer", file=sys.stderr)
                    print("⚠️  Obtenez un token gratuit sur: https://huggingface.co/settings/tokens", file=sys.stderr)

                self.diarization_pipeline = Pipeline.from_pretrained(
                    "pyannote/speaker-diarization-3.1",
                    token=hf_token  # Changé de use_auth_token à token (nouvelle API pyannote)
                )
                print("✅ Modèle de diarization chargé", file=sys.stderr)

            # Appliquer la diarization
            print(f"🔍 Analyse des locuteurs (max {self.max_speakers})...", file=sys.stderr)
            
            # Charger l'audio avec soundfile (évite le problème torchcodec)
            waveform, sample_rate = sf.read(audio_path)
            # Convertir en tensor PyTorch et ajouter dimension channel si nécessaire
            waveform = torch.from_numpy(waveform.T).float()
            if waveform.dim() == 1:
                waveform = waveform.unsqueeze(0)  # Ajouter dimension channel
            
            # Pyannote attend un dict avec 'waveform' et 'sample_rate'
            audio_dict = {
                "waveform": waveform,
                "sample_rate": sample_rate
            }
            
            diarization = self.diarization_pipeline(
                audio_dict,
                num_speakers=None,  # Auto-détection
                min_speakers=1,
                max_speakers=self.max_speakers
            )            # Convertir les résultats de diarization en dict
            speaker_segments = []
            for turn, _, speaker in diarization.itertracks(yield_label=True):
                speaker_segments.append({
                    'start': turn.start,
                    'end': turn.end,
                    'speaker': speaker
                })

            num_speakers = len(set(s['speaker'] for s in speaker_segments))

            print("", file=sys.stderr)
            print("╔════════════════════════════════════════════════════════════════╗", file=sys.stderr)
            print(f"║  ✅ SUCCÈS: {num_speakers} locuteur(s) détecté(s)                           ║", file=sys.stderr)
            print("╠════════════════════════════════════════════════════════════════╣", file=sys.stderr)
            print(f"║  {num_speakers} personnage(s) seront créés automatiquement              ║", file=sys.stderr)
            print("║  Vous pourrez les renommer ou fusionner après l'extraction     ║", file=sys.stderr)
            print("╚════════════════════════════════════════════════════════════════╝", file=sys.stderr)
            print("", file=sys.stderr)

            # Assigner les speakers aux segments Whisper
            return self._merge_transcription_with_diarization(transcription, speaker_segments)

        except Exception as e:
            error_msg = str(e)
            print(f"❌ ERREUR Diarization: {error_msg}", file=sys.stderr)

            # Messages d'erreur explicites selon le type d'erreur
            if "token" in error_msg.lower() or "unauthorized" in error_msg.lower() or "403" in error_msg:
                print("", file=sys.stderr)
                print("╔════════════════════════════════════════════════════════════════╗", file=sys.stderr)
                print("║  ❌ ERREUR: Token HuggingFace manquant ou invalide           ║", file=sys.stderr)
                print("╠════════════════════════════════════════════════════════════════╣", file=sys.stderr)
                print("║  La diarization (détection locuteurs) nécessite un token HF   ║", file=sys.stderr)
                print("║                                                                ║", file=sys.stderr)
                print("║  📝 SOLUTION (2 minutes) :                                    ║", file=sys.stderr)
                print("║  1. Créer compte gratuit: https://huggingface.co              ║", file=sys.stderr)
                print("║  2. Générer token: https://huggingface.co/settings/tokens     ║", file=sys.stderr)
                print("║  3. Accepter conditions du modèle:                            ║", file=sys.stderr)
                print("║     https://huggingface.co/pyannote/speaker-diarization-3.1   ║", file=sys.stderr)
                print("║  4. Ajouter dans .env : HF_TOKEN=hf_xxxxx                     ║", file=sys.stderr)
                print("║  5. Relancer l'extraction                                     ║", file=sys.stderr)
                print("║                                                                ║", file=sys.stderr)
                print("║  ⚠️  Sans token : UN SEUL locuteur sera créé                 ║", file=sys.stderr)
                print("╚════════════════════════════════════════════════════════════════╝", file=sys.stderr)
                print("", file=sys.stderr)
            elif "model" in error_msg.lower() or "download" in error_msg.lower():
                print("", file=sys.stderr)
                print("╔════════════════════════════════════════════════════════════════╗", file=sys.stderr)
                print("║  ❌ ERREUR: Échec du téléchargement du modèle                ║", file=sys.stderr)
                print("╠════════════════════════════════════════════════════════════════╣", file=sys.stderr)
                print("║  Le modèle de diarization n'a pas pu être téléchargé          ║", file=sys.stderr)
                print("║                                                                ║", file=sys.stderr)
                print("║  📝 VÉRIFIER :                                                ║", file=sys.stderr)
                print("║  - Connexion internet active                                   ║", file=sys.stderr)
                print("║  - Espace disque disponible (~1GB)                            ║", file=sys.stderr)
                print("║  - Token HF valide et conditions acceptées                    ║", file=sys.stderr)
                print("║                                                                ║", file=sys.stderr)
                print("║  ⚠️  Fallback : UN SEUL locuteur sera créé                   ║", file=sys.stderr)
                print("╚════════════════════════════════════════════════════════════════╝", file=sys.stderr)
                print("", file=sys.stderr)
            else:
                print("", file=sys.stderr)
                print("╔════════════════════════════════════════════════════════════════╗", file=sys.stderr)
                print("║  ⚠️  AVERTISSEMENT: Diarization échouée                      ║", file=sys.stderr)
                print("╠════════════════════════════════════════════════════════════════╣", file=sys.stderr)
                print(f"║  Erreur: {error_msg[:54]:<54} ║", file=sys.stderr)
                print("║                                                                ║", file=sys.stderr)
                print("║  ⚠️  Fallback : UN SEUL locuteur sera créé                   ║", file=sys.stderr)
                print("║  💡 Vous pourrez renommer/fusionner après l'extraction        ║", file=sys.stderr)
                print("╚════════════════════════════════════════════════════════════════╝", file=sys.stderr)
                print("", file=sys.stderr)

            print("⚠️  Fallback: Attribution de tous les dialogues à SPEAKER_00", file=sys.stderr)
            return self._assign_single_speaker(transcription)

    def _merge_transcription_with_diarization(
        self,
        transcription: Dict,
        speaker_segments: List[Dict]
    ) -> List[Dict]:
        """
        Fusionner la transcription Whisper avec les résultats de diarization

        Args:
            transcription: Résultat Whisper avec segments
            speaker_segments: Résultats de diarization avec speakers

        Returns:
            Liste de dialogues avec speakers assignés
        """
        dialogues = []

        for segment in transcription['segments']:
            seg_start = segment['start']
            seg_end = segment['end']
            seg_mid = (seg_start + seg_end) / 2  # Point milieu du segment

            # Trouver le speaker qui parle au milieu du segment
            assigned_speaker = 'SPEAKER_00'  # Défaut
            max_overlap = 0.0

            for spk_seg in speaker_segments:
                # Calculer le chevauchement
                overlap_start = max(seg_start, spk_seg['start'])
                overlap_end = min(seg_end, spk_seg['end'])
                overlap = max(0, overlap_end - overlap_start)

                if overlap > max_overlap:
                    max_overlap = overlap
                    assigned_speaker = spk_seg['speaker']

            dialogues.append({
                'start': seg_start,
                'end': seg_end,
                'text': segment['text'].strip(),
                'speaker': assigned_speaker,
                'confidence': segment.get('confidence', segment.get('no_speech_prob', 0.0)),
                'language': transcription.get('language', 'unknown')
            })

        return dialogues

    def _assign_single_speaker(self, transcription: Dict) -> List[Dict]:
        """Assigner tous les segments à un speaker unique (fallback)"""
        dialogues = []

        for segment in transcription['segments']:
            dialogues.append({
                'start': segment['start'],
                'end': segment['end'],
                'text': segment['text'].strip(),
                'speaker': 'SPEAKER_00',
                'confidence': segment.get('confidence', segment.get('no_speech_prob', 0.0)),
                'language': transcription.get('language', 'unknown')
            })

        return dialogues

    def process_video(self, video_path: str, output_json: str) -> Dict:
        """
        Pipeline complet d'extraction de dialogues

        Args:
            video_path: Chemin vers la vidéo
            output_json: Chemin de sortie JSON

        Returns:
            Données extraites
        """
        audio_path = None

        try:
            # Étape 1: Extraction audio (0-20%)
            audio_path = self.extract_audio(video_path)

            # Étape 2: Transcription Whisper (20-70%)
            transcription = self.transcribe_audio(audio_path)

            # Libérer mémoire après transcription
            self._unload_whisper_model()

            # Étape 3: Diarization (70-90%)
            dialogues = self.apply_diarization(audio_path, transcription)

            # Étape 4: Formater résultat (90-100%)
            speakers = list(set(d['speaker'] for d in dialogues))
            speakers.sort()

            result = {
                'dialogues': dialogues,
                'speakers': speakers,
                'metadata': {
                    'duration': transcription.get('duration', 0),
                    'total_dialogues': len(dialogues),
                    'model': self.model_name,
                    'language': transcription.get('language', 'unknown'),
                    'detected_speakers': len(speakers)
                }
            }

            # Sauvegarder JSON
            with open(output_json, 'w', encoding='utf-8') as f:
                json.dump(result, f, ensure_ascii=False, indent=2)

            print(f"✅ Résultat sauvegardé: {output_json}")
            print(f"📊 Statistiques:")
            print(f"   - Dialogues: {len(dialogues)}")
            print(f"   - Locuteurs: {len(speakers)}")
            print(f"   - Durée: {result['metadata']['duration']:.1f}s")
            print(f"   - Langue détectée: {result['metadata']['language']}")

            return result

        finally:
            # Nettoyage fichier audio temporaire
            if audio_path and os.path.exists(audio_path):
                os.unlink(audio_path)
                print(f"🗑️  Fichier audio temporaire supprimé")

            # Libérer mémoire
            self._unload_whisper_model()
            gc.collect()


def main():
    parser = argparse.ArgumentParser(
        description="Extraction automatique de dialogues avec Whisper + diarization"
    )
    parser.add_argument('video_path', help="Chemin vers la vidéo")
    parser.add_argument('output_json', help="Chemin de sortie JSON")
    parser.add_argument('--model', default='tiny', choices=list(DialogueExtractor.MODELS.keys()),
                        help="Modèle Whisper (défaut: tiny)")
    parser.add_argument('--language', default='auto',
                        help="Langue source (auto/en/fr/zh/ja/..., défaut: auto)")
    parser.add_argument('--max-speakers', type=int, default=10,
                        help="Nombre max de locuteurs (défaut: 10)")

    args = parser.parse_args()

    try:
        extractor = DialogueExtractor(
            model_name=args.model,
            language=args.language,
            max_speakers=args.max_speakers
        )

        result = extractor.process_video(args.video_path, args.output_json)

        print("\n✅ Extraction terminée avec succès!")
        sys.exit(0)

    except Exception as e:
        print(f"\n❌ Erreur: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == '__main__':
    main()
