#!/bin/bash

# Test Resemblyzer Diarization sur une vraie vidéo

echo "🧪 Test Resemblyzer Diarization"
echo "==============================="
echo ""

cd /Users/martinp/Documents/GitHub/agfaRythmo/agfa-rythmo-backend

# Trouver une vidéo de test
VIDEO=$(find storage/app/private/public/videos -name "*.mp4" -type f | head -1)

if [ ! -f "$VIDEO" ]; then
    echo "❌ Aucune vidéo de test trouvée"
    exit 1
fi

echo "📹 Vidéo: $VIDEO"
echo ""

# Créer répertoire temporaire
TEMP_DIR=$(mktemp -d)
echo "📁 Répertoire temporaire: $TEMP_DIR"

# 1. Extraire l'audio
AUDIO="$TEMP_DIR/audio.wav"
echo "🎵 Extraction audio..."
ffmpeg -i "$VIDEO" -ar 16000 -ac 1 -y "$AUDIO" 2>&1 | grep -E "Duration|Stream|Output" || true
echo ""

# 2. Transcription Whisper (modèle tiny pour test rapide)
TRANSCRIPTION="$TEMP_DIR/transcription.json"
echo "📝 Transcription Whisper (modèle tiny, auto-detect)..."
whisper "$AUDIO" \
    --model tiny \
    --output_format json \
    --output_dir "$TEMP_DIR" 2>&1 | tail -10
    
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

# 3. Diarization Resemblyzer
OUTPUT="$TEMP_DIR/diarization.json"
echo "👥 Diarization Resemblyzer..."
echo "============================="
python3 scripts/resemblyzer_diarization.py \
    "$AUDIO" \
    "$TRANSCRIPTION" \
    "$OUTPUT" \
    --max-speakers 10 \
    --skip-spleeter

echo ""
echo "============================="
echo ""

# Analyser résultat
if [ -f "$OUTPUT" ]; then
    echo "✅ Diarization réussie !"
    echo ""
    
    # Afficher statistiques
    python3 << PYEOF
import json

with open('$OUTPUT') as f:
    data = json.load(f)

print(f"🎯 Méthode: {data.get('method', 'unknown')}")
print(f"📊 Embedding dimensions: {data.get('embedding_dim', 'unknown')}")
print(f"")

speakers = {}
for seg in data.get('segments', []):
    speaker = seg['speaker']
    if speaker not in speakers:
        speakers[speaker] = []
    speakers[speaker].append(seg)

print(f"🎭 Speakers détectés: {len(speakers)}")
print(f"")

for speaker in sorted(speakers.keys()):
    segs = speakers[speaker]
    total_duration = sum(s['end'] - s['start'] for s in segs)
    print(f"   {speaker}:")
    print(f"      • {len(segs)} segments")
    print(f"      • {total_duration:.1f}s de parole")
    if len(segs) > 0:
        sample = segs[0]['text'][:60]
        print(f"      • Exemple: \"{sample}...\"")
    print("")

print("📝 Premiers segments:")
for i, seg in enumerate(data['segments'][:5]):
    print(f"   [{seg['start']:.1f}s-{seg['end']:.1f}s] {seg['speaker']}: {seg['text'][:50]}...")
PYEOF

else
    echo "❌ Échec diarization"
fi

echo ""
echo "🗑️  Fichiers conservés dans: $TEMP_DIR"
echo "   (supprimez avec: rm -rf $TEMP_DIR)"
echo ""
echo "✅ Terminé"
