#!/usr/bin/env python3
"""
Diarization avec Resemblyzer (embeddings vocaux pré-entraînés)
Workflow: Demucs → Whisper → Resemblyzer → Clustering

Estimation RAM: ~2-2.5GB (compatible serveur 4GB)
Précision: ⭐⭐⭐⭐⭐ (vrais embeddings vocaux)

Usage:
    python resemblyzer_diarization.py audio.wav transcription.json output.json --max-speakers 10
"""

import argparse
import json
import sys
import traceback
from pathlib import Path
from typing import List, Dict, Tuple

import numpy as np
import soundfile as sf
from resemblyzer import VoiceEncoder, preprocess_wav
from sklearn.cluster import AgglomerativeClustering
from sklearn.metrics import silhouette_score


def separate_vocals_with_spleeter(audio_path: str, output_dir: str) -> str:
    """
    Séparer les voix de l'instrumental avec Spleeter
    DÉSACTIVÉ pour l'instant (utilise Demucs à la place dans extract_dialogues.py)

    Returns:
        str: Chemin vers le fichier vocals.wav
    """
    return audio_path


def extract_embeddings_for_segments(
    encoder: VoiceEncoder,
    vocals_path: str,
    segments: List[Dict]
) -> List[np.ndarray]:
    """
    Extrait les embeddings pour chaque segment (lecture optimisée).

    Args:
        encoder: Modèle Resemblyzer
        vocals_path: Chemin du fichier audio
        segments: Liste des segments avec start/end

    Returns:
        Liste des embeddings (256D) par segment
    """
    embeddings = []

    # Obtenir les métadonnées du fichier sans le charger entièrement
    with sf.SoundFile(vocals_path) as f:
        sample_rate = f.samplerate
        total_frames = len(f)
        total_duration = total_frames / sample_rate

    for idx, segment in enumerate(segments, 1):
        start_time = segment['start']
        end_time = segment['end']

        # Convertir temps en frames
        start_frame = int(start_time * sample_rate)
        end_frame = int(end_time * sample_rate)
        num_frames = end_frame - start_frame

        # Lire SEULEMENT ce segment (évite de charger tout le fichier)
        wav_segment, sr = sf.read(
            vocals_path,
            start=start_frame,
            frames=num_frames
        )

        # Convertir stereo -> mono si nécessaire
        if len(wav_segment.shape) > 1 and wav_segment.shape[1] == 2:
            wav_segment = wav_segment.mean(axis=1)

        # Prétraiter le segment
        wav_preprocessed = preprocess_wav(wav_segment, source_sr=sr)

        # Extraire l'embedding
        embedding = encoder.embed_utterance(wav_preprocessed)
        embeddings.append(embedding)

    print(f"Embeddings extracted: {len(embeddings)} segments", file=sys.stderr)
    return embeddings


def cluster_embeddings(
    embeddings: np.ndarray,
    max_speakers: int = 10,
    min_speakers: int = 2
) -> np.ndarray:
    """
    Clusteriser les embeddings pour identifier les speakers

    Args:
        embeddings: (n_segments, 256) embeddings Resemblyzer
        max_speakers: Nombre max de speakers à détecter
        min_speakers: Nombre min de speakers

    Returns:
        labels: (n_segments,) assignations speaker
    """
    n_segments = len(embeddings)
    max_k = min(max_speakers, n_segments // 2, 10)
    min_k = min(min_speakers, max_k)

    best_score = -1
    best_labels = None
    best_k = min_k

    for k in range(min_k, max_k + 1):
        # Clustering hiérarchique avec cosine distance
        clustering = AgglomerativeClustering(
            n_clusters=k,
            metric='cosine',
            linkage='average'
        )
        labels = clustering.fit_predict(embeddings)

        # Vérifier distribution
        unique, counts = np.unique(labels, return_counts=True)

        # Note: On accepte maintenant les clusters de 1 segment (locuteurs qui parlent peu)
        # Skip uniquement si cluster vide (ne devrait pas arriver)
        if np.any(counts < 1):
            continue

        # Calculer silhouette score
        try:
            score = silhouette_score(embeddings, labels, metric='cosine')
        except:
            score = -1

        # Logique de sélection améliorée:
        # 1. Si score nettement meilleur (>0.05), on prend ce k
        # 2. Si scores proches (<=0.05), on favorise plus de clusters
        if score > best_score + 0.05:
            best_score = score
            best_labels = labels
            best_k = k
        elif score > best_score - 0.05 and k > best_k:
            # Scores similaires → favoriser plus de clusters
            best_score = score
            best_labels = labels
            best_k = k

    # Fallback si aucun bon clustering
    if best_labels is None:
        clustering = AgglomerativeClustering(n_clusters=2, metric='cosine', linkage='average')
        best_labels = clustering.fit_predict(embeddings)
        best_k = 2
        best_score = silhouette_score(embeddings, best_labels, metric='cosine')

    print(f"Detected {best_k} speakers (silhouette={best_score:.3f})", file=sys.stderr)

    return best_labels


def main():
    parser = argparse.ArgumentParser(description='Diarization avec Resemblyzer (embeddings vocaux)')
    parser.add_argument('audio_path', help='Chemin vers le fichier audio (MP4/WAV)')
    parser.add_argument('transcription_json', help='Chemin vers la transcription Whisper (JSON)')
    parser.add_argument('output_json', help='Chemin vers le fichier JSON de sortie')
    parser.add_argument('--max-speakers', type=int, default=10, help='Nombre max de speakers')
    parser.add_argument('--skip-spleeter', action='store_true', help='Skip séparation Spleeter (si déjà fait)')

    args = parser.parse_args()

    try:
        # 1. Charger transcription Whisper
        with open(args.transcription_json, 'r', encoding='utf-8') as f:
            transcription = json.load(f)

        segments = transcription.get('segments', [])

        if len(segments) == 0:
            raise ValueError("No segments in transcription")

        # 2. Séparation vocals (Spleeter) - DÉSACTIVÉ
        vocals_path = args.audio_path

        if not args.skip_spleeter:
            output_dir = Path(args.audio_path).parent / 'spleeter_output'
            output_dir.mkdir(exist_ok=True)

            vocals_path = separate_vocals_with_spleeter(args.audio_path, str(output_dir))

        # 3. Initialiser encoder Resemblyzer
        encoder = VoiceEncoder(device='cpu')  # Force CPU (pas de GPU)

        # 4. Extraire embeddings pour chaque segment
        embeddings = extract_embeddings_for_segments(
            encoder,
            vocals_path,
            segments
        )

        # Convertir liste en array numpy
        embeddings_array = np.array(embeddings)

        # 5. Clustering des embeddings
        labels = cluster_embeddings(embeddings_array, args.max_speakers)

        # 6. Assigner speakers aux segments
        for i, label in enumerate(labels):
            segments[i]['speaker'] = f"SPEAKER_{label:02d}"

        # 7. Sauvegarder résultat
        output = {
            'segments': segments,
            'num_speakers': len(np.unique(labels)),
            'method': 'resemblyzer',
            'embedding_dim': 256,
        }

        with open(args.output_json, 'w', encoding='utf-8') as f:
            json.dump(output, f, ensure_ascii=False, indent=2)

        print(f"SUCCESS - Diarization completed: {len(np.unique(labels))} speakers detected", file=sys.stderr)

    except Exception as e:
        print(f"ERROR: {e}", file=sys.stderr)
        traceback.print_exc(file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
