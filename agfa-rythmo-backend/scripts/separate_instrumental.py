#!/usr/bin/env python3
"""
Séparation instrumentale avec Demucs (INVERSE de separate_vocals.py)
Extrait drums + bass + other (tout SAUF vocals)
Optimisé pour serveurs 4GB+ RAM
"""

import argparse
import gc
import os
import sys
from pathlib import Path

try:
    import torch
    import numpy as np
    import soundfile as sf
except ImportError as e:
    print(f"ERROR: Missing dependencies: {e}", file=sys.stderr)
    print("Install: pip install torch soundfile numpy", file=sys.stderr)
    sys.exit(1)


def separate_instrumental(
    audio_path: str,
    output_path: str,
    model_name: str = 'htdemucs'
) -> bool:
    """
    Sépare la partie instrumentale (drums + bass + other) d'un fichier audio

    Args:
        audio_path: Chemin vers le fichier audio source
        output_path: Chemin où sauvegarder l'instrumental
        model_name: Modèle Demucs (htdemucs par défaut)

    Returns:
        True si succès, False sinon
    """
    try:
        print(f"Demucs instrumental separation starting...", file=sys.stderr)
        print(f"  Model: {model_name}", file=sys.stderr)
        print(f"  Input: {audio_path}", file=sys.stderr)
        print(f"  Output: {output_path}", file=sys.stderr)

        # Import Demucs
        from demucs.pretrained import get_model
        from demucs.apply import apply_model

        # Charger le modèle
        print(f"  Loading Demucs model '{model_name}'...", file=sys.stderr)
        gc.collect()
        model = get_model(model_name)
        model.eval()
        print(f"  ✓ Model loaded successfully", file=sys.stderr)

        # Charger l'audio
        print("  Loading audio file...", file=sys.stderr)
        waveform, sample_rate = sf.read(audio_path, always_2d=True)
        waveform = torch.from_numpy(waveform.T).float()

        audio_duration = waveform.shape[1] / sample_rate
        print(f"  ✓ Audio loaded: {sample_rate}Hz, {audio_duration:.1f}s duration", file=sys.stderr)

        # Demucs nécessite stereo
        if waveform.shape[0] == 1:
            print("  Converting mono to stereo...", file=sys.stderr)
            waveform = waveform.repeat(2, 1)

        # Resample si nécessaire (Demucs utilise 44.1kHz)
        if sample_rate != 44100:
            print(f"  Resampling {sample_rate}Hz -> 44100Hz...", file=sys.stderr)
            import librosa
            waveform_np = waveform.cpu().numpy()
            waveform_resampled = np.array([
                librosa.resample(waveform_np[0], orig_sr=sample_rate, target_sr=44100),
                librosa.resample(waveform_np[1], orig_sr=sample_rate, target_sr=44100)
            ])
            waveform = torch.from_numpy(waveform_resampled).float()
            sample_rate = 44100
            print(f"  ✓ Resampling done", file=sys.stderr)

        # Normaliser
        print("  Normalizing audio...", file=sys.stderr)
        waveform = waveform / waveform.abs().max()

        # Appliquer le modèle (séparation)
        print("  🎵 Separating INSTRUMENTAL (drums + bass + other, NO vocals)...", file=sys.stderr)
        print(f"  Processing {audio_duration:.1f}s of audio...", file=sys.stderr)
        with torch.no_grad():
            sources = apply_model(
                model,
                waveform.unsqueeze(0),
                device='cpu',
                shifts=0,
                split=True,
                overlap=0.25,
                num_workers=0
            )[0]

        print(f"  ✓ Instrumental separation completed!", file=sys.stderr)

        # Libérer mémoire
        del model
        del waveform
        gc.collect()
        print("  ✓ Memory cleanup done", file=sys.stderr)

        # Demucs htdemucs retourne: [drums, bass, other, vocals]
        # On veut TOUT SAUF vocals = drums + bass + other
        print("  Extracting instrumental tracks (drums + bass + other)...", file=sys.stderr)

        try:
            if sources.shape[0] == 4:
                # Mixer drums (0) + bass (1) + other (2)
                instrumental = sources[0] + sources[1] + sources[2]
            else:
                # Fallback: prendre tout sauf le dernier (vocals)
                instrumental = sources[:-1].sum(dim=0)

            del sources
            gc.collect()
        except Exception as e:
            print(f"  ERROR: sources not defined or failed: {e}", file=sys.stderr)
            return False

        # Convertir stereo en mono (optionnel, économise espace)
        if instrumental.shape[0] == 2:
            print("  Converting stereo instrumental to mono...", file=sys.stderr)
            instrumental = instrumental.mean(dim=0, keepdim=True)

        # Sauvegarder
        print(f"  Saving extracted instrumental to {output_path}...", file=sys.stderr)
        instrumental_np = instrumental.cpu().numpy().T

        os.makedirs(os.path.dirname(output_path), exist_ok=True)
        sf.write(output_path, instrumental_np, sample_rate)

        del instrumental
        del instrumental_np
        gc.collect()

        file_size = os.path.getsize(output_path) / (1024 * 1024)
        print(f"SUCCESS - Instrumental extracted and saved: {output_path} ({file_size:.1f}MB)", file=sys.stderr)

        return True

    except Exception as e:
        print(f"ERROR - Instrumental separation failed: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc(file=sys.stderr)
        return False


def main():
    parser = argparse.ArgumentParser(description='Extract instrumental (no vocals) from audio using Demucs')
    parser.add_argument('audio_path', help='Input audio file path')
    parser.add_argument('output_path', help='Output instrumental file path')
    parser.add_argument('--model', default='htdemucs', help='Demucs model (default: htdemucs)')

    args = parser.parse_args()

    if not os.path.exists(args.audio_path):
        print(f"❌ Erreur: Fichier source introuvable: {args.audio_path}", file=sys.stderr)
        sys.exit(1)

    # Créer répertoire de sortie si nécessaire
    output_dir = os.path.dirname(args.output_path)
    if output_dir and not os.path.exists(output_dir):
        os.makedirs(output_dir, exist_ok=True)

    success = separate_instrumental(args.audio_path, args.output_path, args.model)
    sys.exit(0 if success else 1)


if __name__ == '__main__':
    main()
