#!/bin/bash

# Script de test pour le nouveau clustering avec normalisation L2 + cosine distance

echo "🧪 Test du nouveau système de clustering avec embeddings"
echo "=========================================================="
echo ""

cd agfa-rythmo-backend

# Choisir une vidéo de test
VIDEO="storage/app/private/public/videos/2rmhA4CPIDKWNiPjuIEx.mp4"

if [ ! -f "$VIDEO" ]; then
    echo "❌ Vidéo de test non trouvée: $VIDEO"
    exit 1
fi

echo "📹 Vidéo: $VIDEO"
echo ""

# Créer répertoire temporaire
TEMP_DIR=$(mktemp -d)
echo "📁 Répertoire temporaire: $TEMP_DIR"

# Extraire l'audio
AUDIO="$TEMP_DIR/audio.wav"
echo "🎵 Extraction audio..."
ffmpeg -i "$VIDEO" -ar 16000 -ac 1 -y "$AUDIO" 2>&1 | grep -E "Duration|Stream|Output" || true
echo ""

# Transcription Whisper (modèle tiny pour test rapide)
TRANSCRIPTION="$TEMP_DIR/transcription.json"
echo "📝 Transcription Whisper (modèle tiny)..."
whisper "$AUDIO" \
    --model tiny \
    --language fr \
    --output_format json \
    --output_dir "$TEMP_DIR" 2>&1 | tail -5
mv "$TEMP_DIR/audio.json" "$TRANSCRIPTION" 2>/dev/null || true
echo ""

# Vérifier transcription
if [ ! -f "$TRANSCRIPTION" ]; then
    echo "❌ Échec transcription Whisper"
    rm -rf "$TEMP_DIR"
    exit 1
fi

SEGMENT_COUNT=$(python3 -c "import json; print(len(json.load(open('$TRANSCRIPTION')).get('segments', [])))" 2>/dev/null || echo "0")
echo "✅ $SEGMENT_COUNT segments transcrits"
echo ""

# Diarization avec nouveau clustering
OUTPUT="$TEMP_DIR/diarization.json"
echo "👥 Diarization (nouveau clustering L2+cosine)..."
echo "================================================"
python3 scripts/simple_diarization.py \
    "$AUDIO" \
    "$TRANSCRIPTION" \
    "$OUTPUT" \
    --max-speakers 10

echo ""
echo "================================================"
echo ""

# Analyser résultat
if [ -f "$OUTPUT" ]; then
    echo "✅ Diarization réussie !"
    echo ""
    
    # Compter speakers détectés
    SPEAKERS=$(python3 << 'PYEOF'
import json, sys
with open('$OUTPUT') as f:
    data = json.load(f)
    speakers = set(s['speaker'] for s in data.get('segments', []))
    print(f"🎭 Speakers détectés: {len(speakers)}")
    for speaker in sorted(speakers):
        count = sum(1 for s in data['segments'] if s['speaker'] == speaker)
        print(f"   {speaker}: {count} segments")
PYEOF
)
    echo "$SPEAKERS"
    
    echo ""
    echo "📊 Premiers segments:"
    python3 -c "import json; segs = json.load(open('$OUTPUT'))['segments'][:5]; [print(f\"   [{s['start']:.1f}s-{s['end']:.1f}s] {s['speaker']}: {s['text'][:50]}...\") for s in segs]"
else
    echo "❌ Échec diarization"
fi

echo ""
echo "🗑️  Nettoyage..."
rm -rf "$TEMP_DIR"
echo "✅ Terminé"
