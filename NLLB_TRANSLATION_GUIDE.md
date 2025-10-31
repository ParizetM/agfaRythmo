# Guide d'Utilisation NLLB-200 pour Traduction

**Version** : 1.0.0 | **Date** : 31 octobre 2025

## 📋 Vue d'ensemble

NLLB-200 (No Language Left Behind) est un modèle de traduction développé par Meta AI qui supporte **200 langues**. Cette intégration permet d'utiliser ce modèle localement et gratuitement pour traduire les dialogues de vos projets.

### ✨ Avantages

- ✅ **Gratuit** : Aucun coût, aucune API key requise
- ✅ **Local** : Traitement sur votre serveur, confidentialité totale
- ✅ **200 langues** : Incluant chinois, japonais, coréen, arabe, hindi, etc.
- ✅ **Support source** : Toutes les langues supportées comme SOURCE (contrairement à DeepL)
- ✅ **Batch natif** : Traduction de plusieurs textes en une seule passe
- ✅ **Qualité ⭐⭐⭐⭐** : Excellente qualité, particulièrement pour langues asiatiques

### ⚠️ Limitations

- Taille : ~2GB pour le modèle 600M (téléchargement à la première utilisation)
- Startup : ~3-5 secondes de chargement du modèle à chaque traduction
- Performance : Plus lent que les API cloud (DeepL, Google) mais acceptable en background
- RAM : Nécessite ~2GB de RAM disponible

## 🛠️ Installation

### 1. Dépendances Python

Les dépendances sont déjà installées si vous avez configuré l'extraction de dialogues. Sinon :

```bash
cd agfa-rythmo-backend
pip3 install -r scripts/requirements.txt
```

Packages requis :
- `transformers>=4.30.0` : Hugging Face Transformers
- `sentencepiece>=0.1.99` : Tokenizer pour NLLB
- `protobuf>=3.20.0` : Sérialisation
- `torch>=2.0.0` : PyTorch (déjà installé)

### 2. Configuration

Modifier `.env` :

```bash
# Activer la traduction
AI_TRANSLATION_ENABLED=true

# Choisir NLLB comme provider
AI_TRANSLATION_PROVIDER=nllb

# Taille du modèle (optionnel, défaut: 600M)
# Options : 600M (recommandé), 1.3B, 3.3B
AI_NLLB_MODEL_SIZE=600M
```

### 3. Rechargement configuration

```bash
php artisan config:cache
php artisan queue:restart
```

## 📖 Utilisation

### Via l'interface web

1. Ouvrir un projet avec des timecodes
2. Cliquer sur le bouton **"IA"** (gradient violet/rose)
3. Sélectionner **"Traduction automatique"**
4. Configurer :
   - **Langue source** : Auto-détection ou sélection manuelle
   - **Langue cible** : Langue de destination
   - **Contexte personnages** : Cocher pour améliorer la qualité
5. Cliquer sur **"Lancer la traduction"**
6. La modal affiche **"NLLB-200"** comme provider avec ⭐⭐⭐⭐
7. Suivre la progression en temps réel

### Via la ligne de commande

#### Test simple

```bash
cd agfa-rythmo-backend

# Traduction chinois → français
python3 scripts/translate_nllb.py \
  --source zh \
  --target fr \
  --text "你好，世界" \
  --model-size 600M
```

Sortie :
```json
{
  "success": true,
  "translation": "Bonjour, le monde",
  "source_lang": "zh",
  "target_lang": "fr"
}
```

#### Traduction batch

Créer `texts.json` :
```json
["你好", "谢谢", "再见"]
```

Exécuter :
```bash
python3 scripts/translate_nllb.py \
  --source zh \
  --target fr \
  --batch-file texts.json \
  --output results.json \
  --model-size 600M
```

Résultat dans `results.json` :
```json
{
  "success": true,
  "translations": ["Bonjour", "Merci", "Au revoir"],
  "source_lang": "zh",
  "target_lang": "fr"
}
```

### Via le service Laravel

```php
use App\Services\TranslationService;

$service = new TranslationService('nllb');

// Traduction simple
$result = $service->translate('你好', 'fr', 'zh');
// => "Bonjour"

// Traduction batch
$texts = ['你好', '谢谢', '再见'];
$results = $service->translateBatch($texts, 'fr', 'zh');
// => ["Bonjour", "Merci", "Au revoir"]
```

## 🌍 Langues supportées

NLLB-200 supporte 200 langues avec codes FLORES-200. Le système convertit automatiquement les codes ISO standards.

### Principales langues (25 configurées)

| Code ISO | Langue | Code FLORES-200 |
|----------|--------|-----------------|
| `en` | Anglais | `eng_Latn` |
| `fr` | Français | `fra_Latn` |
| `zh` | Chinois | `zho_Hans` |
| `ja` | Japonais | `jpn_Jpan` |
| `ko` | Coréen | `kor_Hang` |
| `es` | Espagnol | `spa_Latn` |
| `de` | Allemand | `deu_Latn` |
| `it` | Italien | `ita_Latn` |
| `pt` | Portugais | `por_Latn` |
| `ru` | Russe | `rus_Cyrl` |
| `ar` | Arabe | `arb_Arab` |
| `hi` | Hindi | `hin_Deva` |
| `tr` | Turc | `tur_Latn` |
| `id` | Indonésien | `ind_Latn` |
| `vi` | Vietnamien | `vie_Latn` |
| `th` | Thaï | `tha_Thai` |
| `nl` | Néerlandais | `nld_Latn` |
| `pl` | Polonais | `pol_Latn` |
| `uk` | Ukrainien | `ukr_Cyrl` |
| `cs` | Tchèque | `ces_Latn` |
| `sv` | Suédois | `swe_Latn` |
| `no` | Norvégien | `nob_Latn` |
| `da` | Danois | `dan_Latn` |
| `fi` | Finnois | `fin_Latn` |
| `el` | Grec | `ell_Grek` |

Liste complète : https://github.com/facebookresearch/flores/blob/main/flores200/README.md#languages-in-flores-200

## ⚙️ Configuration avancée

### Taille du modèle

Trois modèles disponibles :

| Taille | RAM | Vitesse | Qualité | Recommandé pour |
|--------|-----|---------|---------|-----------------|
| 600M | ~2GB | ⚡⚡⚡ Rapide | ⭐⭐⭐⭐ Très bonne | **Production** |
| 1.3B | ~5GB | ⚡⚡ Moyen | ⭐⭐⭐⭐⭐ Excellente | Serveurs puissants |
| 3.3B | ~13GB | ⚡ Lent | ⭐⭐⭐⭐⭐ Exceptionnelle | Recherche |

Configuration dans `.env` :
```bash
AI_NLLB_MODEL_SIZE=600M  # ou 1.3B, 3.3B
```

### Batch size

Le script Python traite les textes par lots de 8. Modifier dans `translate_nllb.py` ligne 112 :

```python
batch_size = 8  # Ajuster selon RAM disponible
```

### GPU (optionnel)

Si CUDA/GPU disponible, le script l'utilise automatiquement. Vérifier :

```bash
python3 -c "import torch; print('GPU:', torch.cuda.is_available())"
```

Si GPU disponible, la traduction sera **beaucoup plus rapide** (~10-50x).

## 🔍 Debugging

### Logs Laravel

```bash
tail -f storage/logs/laravel.log
```

Chercher :
- `Exécution NLLB script` : Démarrage traduction
- `NLLB JSON parse error` : Erreur parsing réponse Python
- `NLLB translation failed` : Erreur modèle

### Logs Python

Le script affiche sur stderr :
```
Chargement du modèle facebook/nllb-200-distilled-600m...
Device set to use cpu
Modèle chargé avec succès (device: CPU)
```

### Erreurs communes

#### `NLLB script not found`

Le script `scripts/translate_nllb.py` est manquant ou non exécutable.

Solution :
```bash
chmod +x scripts/translate_nllb.py
```

#### `Invalid JSON response`

Le script Python a échoué. Vérifier :
```bash
python3 scripts/translate_nllb.py --source en --target fr --text "Hello"
```

#### `ModuleNotFoundError: No module named 'transformers'`

Dépendances manquantes.

Solution :
```bash
pip3 install -r scripts/requirements.txt
```

#### `torch.cuda.OutOfMemoryError`

GPU manque de mémoire. Forcer CPU :

```bash
export CUDA_VISIBLE_DEVICES=""
```

## 📊 Comparaison providers

| Provider | Gratuit | Langues | Qualité | Batch | API Key | Local |
|----------|---------|---------|---------|-------|---------|-------|
| **NLLB-200** | ✅ Illimité | 200 | ⭐⭐⭐⭐ | ✅ Natif | ❌ Non | ✅ Oui |
| DeepL | ✅ 500k/mois | 30 | ⭐⭐⭐⭐⭐ | ✅ 50 texts | ✅ Oui | ❌ Non |
| MyMemory | ✅ 10k/jour | 100+ | ⭐⭐⭐ | ❌ Non | ❌ Non | ❌ Non |
| LibreTranslate | ⚠️ Limité | 50+ | ⭐⭐⭐ | ❌ Non | ✅ Oui | ⚠️ Possible |

### Recommandations

- **Langues européennes** : DeepL (si source supportée) > NLLB-200 > MyMemory
- **Langues asiatiques** : **NLLB-200** > MyMemory (DeepL non supporté)
- **Volume élevé** : **NLLB-200** (illimité) > DeepL (500k/mois) > MyMemory (10k/jour)
- **Confidentialité** : **NLLB-200** (local) > Autres (cloud)
- **Qualité maximale** : DeepL > NLLB-200 > LibreTranslate > MyMemory

## 🚀 Performance

### Benchmark projet 27 timecodes (chinois → français)

| Provider | Mode | Temps | Qualité | Notes |
|----------|------|-------|---------|-------|
| **NLLB-200 600M** | Batch | **~15s** | ⭐⭐⭐⭐ | 3-5s startup + 12s traduction |
| DeepL | Fallback MyMemory | ~35s | ⭐⭐⭐ | Source zh non supporté |
| MyMemory | Phrase-by-phrase | ~40s | ⭐⭐⭐ | 100ms délai entre phrases |

## 📚 Ressources

- **Modèle** : https://huggingface.co/facebook/nllb-200-distilled-600m
- **Paper** : https://arxiv.org/abs/2207.04672
- **FLORES-200** : https://github.com/facebookresearch/flores
- **Transformers** : https://huggingface.co/docs/transformers

## 🐛 Troubleshooting

### Le modèle prend du temps à télécharger

Le premier lancement télécharge ~2GB depuis Hugging Face. Ensuite, le modèle est caché dans `~/.cache/huggingface/`.

### Traduction de mauvaise qualité

- Vérifier la langue source détectée dans les logs
- Augmenter le modèle : `AI_NLLB_MODEL_SIZE=1.3B`
- Activer le contexte personnages : `AI_TRANSLATION_USE_CONTEXT=true`

### Worker Laravel bloqué

Le worker attend la fin du script Python (~15s pour 27 timecodes). C'est normal.

Logs :
```
[processing] Traduction batch avec NLLB-200...
[processing] 10/27 timecodes traduits...
[completed] Traduction terminée !
```

---

**Dernière mise à jour** : 31 octobre 2025  
**Version** : 1.0.0  
**Maintainer** : Martin P. (@ParizetM)
