#!/usr/bin/env python3
"""
Diarization avec Resemblyzer (embeddings vocaux pré-entraînés)
Workflow: Spleeter → Whisper (déjà fait) → Resemblyzer → Clustering

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

print("╔══════════════════════════════════════════════════════════════╗", file=sys.stderr)
print("║  🎤 DIARIZATION RESEMBLYZER - Embeddings vocaux 256D        ║", file=sys.stderr)
print("║  (Optimisé pour serveurs 4GB RAM)                           ║", file=sys.stderr)
print("╚══════════════════════════════════════════════════════════════╝", file=sys.stderr)
print("", file=sys.stderr)


def separate_vocals_with_spleeter(audio_path: str, output_dir: str) -> str:
    """
    Séparer les voix de l'instrumental avec Spleeter
    DÉSACTIVÉ pour l'instant (problème installation Spleeter)
    TODO: Réactiver quand Spleeter installé
    
    Returns:
        str: Chemin vers le fichier vocals.wav
    """
    print("⚠️  Spleeter désactivé (pas installé), utilisation audio complet", file=sys.stderr)
    return audio_path
    
    # CODE ORIGINAL (à réactiver plus tard)
    # from spleeter.separator import Separator
    # print("🎵 Séparation vocals/instrumental avec Spleeter...", file=sys.stderr)
    # separator = Separator('spleeter:2stems')
    # separator.separate_to_file(
    #     audio_path,
    #     output_dir,
    #     filename_format='{filename}/{instrument}.{codec}'
    # )
    # audio_name = Path(audio_path).stem
    # vocals_path = Path(output_dir) / audio_name / 'vocals.wav'
    # if not vocals_path.exists():
    #     raise FileNotFoundError(f"Vocals file not found: {vocals_path}")
    # print(f"✅ Vocals séparés : {vocals_path}", file=sys.stderr)
    # return str(vocals_path)


def extract_embeddings_for_segments(
    vocals_path: str,
    segments: List[Dict],
    encoder: VoiceEncoder
) -> Tuple[np.ndarray, List[Dict]]:
    """
    Extraire les embeddings Resemblyzer pour chaque segment Whisper
    
    Args:
        vocals_path: Chemin vers fichier vocals.wav
        segments: Segments Whisper avec timestamps
        encoder: VoiceEncoder de Resemblyzer
    
    Returns:
        embeddings_array: (n_segments, 256) embeddings
        valid_segments: Segments avec embeddings valides
    """
    print(f"\n🧬 Extraction embeddings Resemblyzer pour {len(segments)} segments...", file=sys.stderr)
    
    # Charger audio vocals
    wav, sr = sf.read(vocals_path)
    
    # Preprocess pour Resemblyzer (16kHz)
    wav_preprocessed = preprocess_wav(wav, source_sr=sr)
    
    embeddings_list = []
    valid_segments = []
    
    for i, segment in enumerate(segments):
        start = segment['start']
        end = segment['end']
        
        # Vérifier durée minimum (0.3s)
        duration = end - start
        if duration < 0.3:
            print(f"  ⚠️  Segment {i} trop court ({duration:.2f}s), ignoré", file=sys.stderr)
            continue
        
        # Extraire slice audio (16kHz après preprocess)
        start_sample = int(start * 16000)
        end_sample = int(end * 16000)
        
        if end_sample > len(wav_preprocessed):
            end_sample = len(wav_preprocessed)
        
        audio_slice = wav_preprocessed[start_sample:end_sample]
        
        if len(audio_slice) < 4800:  # Minimum 0.3s @ 16kHz
            continue
        
        try:
            # Calculer embedding 256D
            embedding = encoder.embed_utterance(audio_slice)
            
            embeddings_list.append(embedding)
            valid_segments.append(segment)
            
            if (i + 1) % 10 == 0:
                print(f"  Progression: {i + 1}/{len(segments)} segments", file=sys.stderr)
        
        except Exception as e:
            print(f"  ⚠️  Erreur embedding segment {i}: {e}", file=sys.stderr)
            continue
    
    if len(embeddings_list) == 0:
        raise ValueError("Aucun embedding valide extrait")
    
    embeddings_array = np.array(embeddings_list)
    print(f"✅ {len(embeddings_array)} embeddings extraits (shape: {embeddings_array.shape})", file=sys.stderr)
    
    return embeddings_array, valid_segments


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
    print(f"\n🎯 Clustering avec distance cosine...", file=sys.stderr)
    
    n_segments = len(embeddings)
    max_k = min(max_speakers, n_segments // 2, 10)
    min_k = min(min_speakers, max_k)
    
    best_score = -1
    best_labels = None
    best_k = min_k
    
    print(f"  Test de {min_k} à {max_k} clusters...", file=sys.stderr)
    
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
        
        # Skip si clusters trop déséquilibrés (min 2 segments par cluster)
        if np.any(counts < 2):
            continue
        
        # Calculer silhouette score
        try:
            score = silhouette_score(embeddings, labels, metric='cosine')
        except:
            score = -1
        
        print(f"    k={k}: silhouette={score:.3f}, distribution={dict(zip(unique, counts))}", file=sys.stderr)
        
        if score > best_score:
            best_score = score
            best_labels = labels
            best_k = k
    
    # Fallback si aucun bon clustering
    if best_labels is None:
        print("  ⚠️  Fallback: 2 clusters par défaut", file=sys.stderr)
        clustering = AgglomerativeClustering(n_clusters=2, metric='cosine', linkage='average')
        best_labels = clustering.fit_predict(embeddings)
        best_k = 2
        best_score = silhouette_score(embeddings, best_labels, metric='cosine')
    
    print(f"\n✅ Meilleur: {best_k} speakers (silhouette={best_score:.3f})", file=sys.stderr)
    
    # Afficher détails par cluster
    for cluster_id in range(best_k):
        cluster_count = np.sum(best_labels == cluster_id)
        print(f"  Speaker {cluster_id}: {cluster_count} segments", file=sys.stderr)
    
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
        print("📥 Chargement transcription Whisper...", file=sys.stderr)
        with open(args.transcription_json, 'r', encoding='utf-8') as f:
            transcription = json.load(f)
        
        segments = transcription.get('segments', [])
        print(f"✅ {len(segments)} segments chargés", file=sys.stderr)
        
        if len(segments) == 0:
            raise ValueError("Aucun segment dans la transcription")
        
        # 2. Séparation vocals (Spleeter)
        vocals_path = args.audio_path
        
        if not args.skip_spleeter:
            output_dir = Path(args.audio_path).parent / 'spleeter_output'
            output_dir.mkdir(exist_ok=True)
            
            vocals_path = separate_vocals_with_spleeter(args.audio_path, str(output_dir))
        else:
            print("⏭️  Spleeter skipped, utilisation audio original", file=sys.stderr)
        
        # 3. Initialiser encoder Resemblyzer
        print("\n🧠 Chargement modèle Resemblyzer...", file=sys.stderr)
        encoder = VoiceEncoder(device='cpu')  # Force CPU (pas de GPU)
        print("✅ Modèle chargé", file=sys.stderr)
        
        # 4. Extraire embeddings pour chaque segment
        embeddings, valid_segments = extract_embeddings_for_segments(
            vocals_path,
            segments,
            encoder
        )
        
        # 5. Clustering des embeddings
        labels = cluster_embeddings(embeddings, args.max_speakers)
        
        # 6. Assigner speakers aux segments
        print("\n📝 Assignation speakers...", file=sys.stderr)
        for i, label in enumerate(labels):
            valid_segments[i]['speaker'] = f"SPEAKER_{label:02d}"
        
        # 7. Sauvegarder résultat
        output = {
            'segments': valid_segments,
            'num_speakers': len(np.unique(labels)),
            'method': 'resemblyzer',
            'embedding_dim': 256,
        }
        
        with open(args.output_json, 'w', encoding='utf-8') as f:
            json.dump(output, f, ensure_ascii=False, indent=2)
        
        print(f"\n✅ Diarization terminée : {len(np.unique(labels))} speakers détectés", file=sys.stderr)
        print(f"📁 Résultat sauvegardé : {args.output_json}", file=sys.stderr)
    
    except Exception as e:
        print(f"\n❌ ERREUR: {e}", file=sys.stderr)
        traceback.print_exc(file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
