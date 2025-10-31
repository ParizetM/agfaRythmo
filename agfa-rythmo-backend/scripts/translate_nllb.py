#!/usr/bin/env python3
"""
Script de traduction avec NLLB-200 (Meta AI)
Modèle léger (600M paramètres) supportant 200 langues

Usage:
    python translate_nllb.py --source zh --target fr --text "你好" --model-size 600M
    python translate_nllb.py --source zh --target fr --batch-file input.json --output output.json
"""

import argparse
import json
import sys
import os
from typing import List, Dict
import warnings

# Supprimer les warnings verbeux
warnings.filterwarnings('ignore')
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

try:
    from transformers import AutoTokenizer, AutoModelForSeq2SeqLM, pipeline
    import torch
except ImportError as e:
    print(json.dumps({
        "error": f"Packages manquants: {e}. Installez avec: pip install transformers sentencepiece torch",
        "success": False
    }))
    sys.exit(1)

# Mapping codes langues ISO vers codes FLORES-200 utilisés par NLLB
LANG_CODE_MAP = {
    'en': 'eng_Latn',
    'fr': 'fra_Latn',
    'es': 'spa_Latn',
    'de': 'deu_Latn',
    'it': 'ita_Latn',
    'pt': 'por_Latn',
    'ru': 'rus_Cyrl',
    'zh': 'zho_Hans',  # Chinois simplifié
    'ja': 'jpn_Jpan',
    'ko': 'kor_Hang',
    'ar': 'arb_Arab',
    'hi': 'hin_Deva',
    'nl': 'nld_Latn',
    'pl': 'pol_Latn',
    'tr': 'tur_Latn',
    'id': 'ind_Latn',
    'vi': 'vie_Latn',
    'th': 'tha_Thai',
    'uk': 'ukr_Cyrl',
    'sv': 'swe_Latn',
    'no': 'nob_Latn',
    'da': 'dan_Latn',
    'fi': 'fin_Latn',
}

def get_flores_code(iso_code: str) -> str:
    """Convertir code ISO en code FLORES-200"""
    return LANG_CODE_MAP.get(iso_code.lower(), 'eng_Latn')

def load_model(model_size: str = '600M'):
    """
    Charger le modèle NLLB

    Args:
        model_size: Taille du modèle (600M, 1.3B, 3.3B)
                   600M = ~2GB RAM (recommandé)
                   1.3B = ~5GB RAM
                   3.3B = ~13GB RAM
    """
    model_name = f"facebook/nllb-200-distilled-{model_size.lower()}"

    print(f"Chargement du modèle {model_name}...", file=sys.stderr)

    try:
        # Charger sur CPU par défaut (plus compatible)
        device = 0 if torch.cuda.is_available() else -1

        tokenizer = AutoTokenizer.from_pretrained(model_name)
        model = AutoModelForSeq2SeqLM.from_pretrained(model_name)

        # Créer pipeline de traduction
        translator = pipeline(
            'translation',
            model=model,
            tokenizer=tokenizer,
            device=device,
            max_length=512
        )

        print(f"Modèle chargé avec succès (device: {'GPU' if device == 0 else 'CPU'})", file=sys.stderr)
        return translator

    except Exception as e:
        print(json.dumps({
            "error": f"Erreur chargement modèle: {e}",
            "success": False
        }))
        sys.exit(1)

def translate_text(translator, text: str, source_lang: str, target_lang: str) -> str:
    """Traduire un texte unique"""
    src_flores = get_flores_code(source_lang)
    tgt_flores = get_flores_code(target_lang)

    try:
        result = translator(
            text,
            src_lang=src_flores,
            tgt_lang=tgt_flores,
            max_length=512
        )
        return result[0]['translation_text']
    except Exception as e:
        print(f"Erreur traduction: {e}", file=sys.stderr)
        return text  # Retourner texte original si échec

def translate_batch(translator, texts: List[str], source_lang: str, target_lang: str) -> List[str]:
    """Traduire un batch de textes"""
    src_flores = get_flores_code(source_lang)
    tgt_flores = get_flores_code(target_lang)

    try:
        results = translator(
            texts,
            src_lang=src_flores,
            tgt_lang=tgt_flores,
            max_length=512,
            batch_size=8  # Traiter 8 phrases à la fois
        )
        return [r['translation_text'] for r in results]
    except Exception as e:
        print(f"Erreur traduction batch: {e}", file=sys.stderr)
        # Fallback : traduire un par un
        return [translate_text(translator, text, source_lang, target_lang) for text in texts]

def main():
    parser = argparse.ArgumentParser(description='Traduction avec NLLB-200')
    parser.add_argument('--source', '-s', required=True, help='Langue source (en, fr, zh, ja, etc.)')
    parser.add_argument('--target', '-t', required=True, help='Langue cible (en, fr, zh, ja, etc.)')
    parser.add_argument('--text', help='Texte à traduire (mode texte unique)')
    parser.add_argument('--batch-file', help='Fichier JSON avec array de textes (mode batch)')
    parser.add_argument('--output', help='Fichier de sortie JSON (mode batch)')
    parser.add_argument('--model-size', default='600M', choices=['600M', '1.3B', '3.3B'],
                        help='Taille du modèle (défaut: 600M)')

    args = parser.parse_args()

    # Charger le modèle
    translator = load_model(args.model_size)

    # Mode texte unique
    if args.text:
        translation = translate_text(translator, args.text, args.source, args.target)
        result = {
            "success": True,
            "translation": translation,
            "source_lang": args.source,
            "target_lang": args.target
        }
        print(json.dumps(result, ensure_ascii=False))

    # Mode batch
    elif args.batch_file:
        try:
            with open(args.batch_file, 'r', encoding='utf-8') as f:
                data = json.load(f)

            if isinstance(data, list):
                texts = data
            elif isinstance(data, dict) and 'texts' in data:
                texts = data['texts']
            else:
                raise ValueError("Format JSON invalide. Attendu: array ou {texts: [...]}")

            print(f"Traduction de {len(texts)} textes...", file=sys.stderr)
            translations = translate_batch(translator, texts, args.source, args.target)

            result = {
                "success": True,
                "translations": translations,
                "source_lang": args.source,
                "target_lang": args.target,
                "count": len(translations)
            }

            # Écrire dans fichier ou stdout
            output = json.dumps(result, ensure_ascii=False, indent=2)
            if args.output:
                with open(args.output, 'w', encoding='utf-8') as f:
                    f.write(output)
                print(f"Traductions écrites dans {args.output}", file=sys.stderr)
            else:
                print(output)

        except Exception as e:
            print(json.dumps({
                "error": f"Erreur batch: {e}",
                "success": False
            }))
            sys.exit(1)

    else:
        parser.print_help()
        sys.exit(1)

if __name__ == '__main__':
    main()
