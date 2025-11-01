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
except ImportError:
    print("❌ Erreur: whisper ou torch non installé", file=sys.stderr)
    print("Installation: pip install openai-whisper torch", file=sys.stderr)
    sys.exit(1)



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
        Appliquer la diarization (séparation locuteurs) avec clustering MFCC ultra-light
        Optimisé pour serveurs 2GB RAM - pas de deep learning

        Args:
            audio_path: Chemin vers l'audio
            transcription: Résultat Whisper

        Returns:
            Liste de dialogues avec speakers assignés
        """
        # Vérifier si diarization activée
        diarization_enabled = os.getenv('AI_DIARIZATION_ENABLED', 'false').lower() == 'true'
        
        if not diarization_enabled:
            print("", file=sys.stderr)
            print("╔════════════════════════════════════════════════════════════════╗", file=sys.stderr)
            print("║  ⚠️  DIARIZATION DÉSACTIVÉE                                   ║", file=sys.stderr)
            print("╠════════════════════════════════════════════════════════════════╣", file=sys.stderr)
            print("║  AI_DIARIZATION_ENABLED=false dans le fichier .env            ║", file=sys.stderr)
            print("║                                                                ║", file=sys.stderr)
            print("║  Résultat : UN SEUL locuteur (SPEAKER_00)                      ║", file=sys.stderr)
            print("║                                                                ║", file=sys.stderr)
            print("║  Pour activer la détection multi-locuteurs :                  ║", file=sys.stderr)
            print("║  Mettre AI_DIARIZATION_ENABLED=true dans .env                 ║", file=sys.stderr)
            print("╚════════════════════════════════════════════════════════════════╝", file=sys.stderr)
            print("", file=sys.stderr)
            return self._assign_single_speaker(transcription)

        # Utiliser le script de diarization ultra-light (clustering MFCC)
        try:
            # Sauvegarder temporairement la transcription
            with tempfile.NamedTemporaryFile(mode='w', suffix='.json', delete=False, encoding='utf-8') as tf:
                json.dump(transcription, tf, ensure_ascii=False)
                transcription_temp = tf.name
            
            # Fichier output temporaire
            output_temp = tempfile.NamedTemporaryFile(mode='w', suffix='.json', delete=False).name
            
            # Appeler le script de diarization
            script_path = Path(__file__).parent / 'simple_diarization.py'
            cmd = [
                sys.executable,
                str(script_path),
                audio_path,
                transcription_temp,
                output_temp,
                '--max-speakers', str(self.max_speakers)
            ]
            
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            
            # Afficher stderr (progression)
            if result.stderr:
                print(result.stderr, file=sys.stderr, end='')
            
            # Charger résultat
            with open(output_temp, 'r', encoding='utf-8') as f:
                diarization_result = json.load(f)
            
            # Nettoyer fichiers temporaires
            os.unlink(transcription_temp)
            os.unlink(output_temp)
            
            return diarization_result['dialogues']
            
        except subprocess.CalledProcessError as e:
            print(f"❌ ERREUR Diarization: {e.stderr}", file=sys.stderr)
            print("⚠️  Fallback: Attribution de tous les dialogues à SPEAKER_00", file=sys.stderr)
            return self._assign_single_speaker(transcription)
        except Exception as e:
            print(f"❌ ERREUR Diarization: {str(e)}", file=sys.stderr)
            print("⚠️  Fallback: Attribution de tous les dialogues à SPEAKER_00", file=sys.stderr)
            return self._assign_single_speaker(transcription)

    def _assign_single_speaker(self, transcription: Dict) -> List[Dict]:
        """
        Assigner tous les segments à un seul speaker (fallback)

        Args:
            transcription: Résultat Whisper

        Returns:
            Liste de dialogues avec un seul speaker
        """
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
