#!/usr/bin/env python3
"""
Séparation vocale avec Demucs
Extrait uniquement les voix (vocals) d'un fichier audio pour améliorer transcription et diarisation.

Usage:
    python separate_vocals.py input.wav output_vocals.wav [--model MODEL]

Models disponibles:
    - htdemucs (défaut) : Plus précis mais plus lent (~30s pour 1min audio)
    - htdemucs_ft : Fine-tuned, meilleure qualité
    - mdx_extra : Très rapide (~5s pour 1min audio) - RECOMMANDÉ pour production

Output: Fichier WAV contenant uniquement les voix (sans musique, bruits de fond, etc.)
"""

import argparse
import sys
import os
import gc  # Pour nettoyage mémoire agressif
import torch
import soundfile as sf  # Utiliser soundfile au lieu de torchaudio
import numpy as np
from pathlib import Path
import warnings

# Supprimer les warnings
warnings.filterwarnings("ignore")

def separate_vocals(audio_path: str, output_path: str, model_name: str = "mdx_extra") -> bool:
    """
    Sépare les voix de l'audio avec Demucs.

    Args:
        audio_path: Chemin vers le fichier audio source
        output_path: Chemin de sortie pour les voix extraites
        model_name: Modèle Demucs à utiliser (mdx_extra, htdemucs, htdemucs_ft)

    Returns:
        True si succès, False sinon
    """
    try:
        # Import Demucs
        from demucs.pretrained import get_model
        from demucs.apply import apply_model

        # Charger le modèle
        gc.collect()

        model = get_model(model_name)
        model.eval()

        # Charger l'audio
        waveform, sample_rate = sf.read(audio_path, always_2d=True)
        # soundfile retourne (samples, channels), torch veut (channels, samples)
        waveform = torch.from_numpy(waveform.T).float()

        audio_duration = waveform.shape[1] / sample_rate

        # Demucs nécessite stereo (2 channels)
        if waveform.shape[0] == 1:
            waveform = waveform.repeat(2, 1)

        # Resample si nécessaire (Demucs utilise 44.1kHz)
        if sample_rate != 44100:
            # Utiliser librosa au lieu de torchaudio.transforms (évite TorchCodec)
            import librosa
            # Convertir torch tensor -> numpy pour librosa
            waveform_np = waveform.cpu().numpy()
            waveform_resampled = np.array([
                librosa.resample(waveform_np[0], orig_sr=sample_rate, target_sr=44100),
                librosa.resample(waveform_np[1], orig_sr=sample_rate, target_sr=44100)
            ])
            waveform = torch.from_numpy(waveform_resampled).float()
            sample_rate = 44100

        # Normaliser
        waveform = waveform / waveform.abs().max()

        # Appliquer le modèle (séparation)
        print(f"Separating vocals ({audio_duration:.1f}s audio)...", file=sys.stderr)
        with torch.no_grad():
            # OPTIMISATION: Utiliser shifts=0 pour désactiver l'ensembling (économise RAM et temps)
            # split=True pour traiter par chunks
            sources = apply_model(
                model,
                waveform.unsqueeze(0),
                device='cpu',
                shifts=0,  # IMPORTANT: Pas d'ensembling = beaucoup plus rapide et léger
                split=True,  # Traite par chunks
                overlap=0.25,  # 25% overlap entre chunks
                num_workers=0  # Pas de multiprocessing pour économiser RAM
            )[0]

        # Libérer mémoire immédiatement et agressivement
        del model
        del waveform
        gc.collect()

        # Demucs retourne: [drums, bass, other, vocals] pour htdemucs
        # ou [vocals, drums, bass, guitar, piano, other] pour htdemucs_6s
        # On récupère uniquement les vocals

        # Pour htdemucs (4 sources) : vocals est à l'index 3
        # Pour htdemucs_6s (6 sources) : vocals est à l'index 0
        try:
            if sources.shape[0] == 6:
                vocals = sources[0]  # htdemucs_6s
            else:
                vocals = sources[3]  # htdemucs (4 sources)
            # Libérer immédiatement la mémoire des autres sources
            del sources
            gc.collect()
        except Exception as e:
            print(f"ERROR: sources not defined or failed: {e}", file=sys.stderr)
            return False

        # Convertir en mono pour Whisper/Resemblyzer
        if vocals.shape[0] == 2:
            vocals = vocals.mean(dim=0, keepdim=True)

        # Normaliser à nouveau
        vocals = vocals / vocals.abs().max()

        # Sauvegarder
        # Convertir torch tensor en numpy pour soundfile (channels, samples) -> (samples, channels)
        vocals_np = vocals.cpu().numpy().T
        sf.write(output_path, vocals_np, sample_rate)

        # Libérer mémoire après sauvegarde
        del vocals
        del vocals_np
        gc.collect()

        file_size = os.path.getsize(output_path) / (1024 * 1024)
        print(f"SUCCESS - Vocals extracted: {output_path} ({file_size:.1f}MB)", file=sys.stderr)

        return True

    except Exception as e:
        print(f"ERROR - Vocal separation failed: {e}", file=sys.stderr)
        return False
def main():
    parser = argparse.ArgumentParser(
        description="Sépare les voix d'un fichier audio avec Demucs"
    )
    parser.add_argument(
        "audio_path",
        type=str,
        help="Chemin vers le fichier audio source (WAV)"
    )
    parser.add_argument(
        "output_path",
        type=str,
        help="Chemin de sortie pour les voix extraites (WAV)"
    )
    parser.add_argument(
        "--model",
        type=str,
        default="htdemucs_6s",
        choices=["htdemucs_6s", "mdx_extra", "htdemucs", "htdemucs_ft"],
        help="Modèle Demucs à utiliser (défaut: htdemucs_6s - léger en RAM)"
    )

    args = parser.parse_args()

    # Vérifier que le fichier source existe
    if not os.path.exists(args.audio_path):
        print(f"❌ Erreur: Fichier source introuvable: {args.audio_path}", file=sys.stderr)
        sys.exit(1)

    # Créer le dossier de sortie si nécessaire
    output_dir = os.path.dirname(args.output_path)
    if output_dir and not os.path.exists(output_dir):
        os.makedirs(output_dir, exist_ok=True)

    # Séparer les voix
    success = separate_vocals(args.audio_path, args.output_path, args.model)

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
