# Guide de Traduction Automatique - AgfaRythmo

**Version** : 2.2.0-beta | **Date** : 31 octobre 2025

## 📋 Vue d'ensemble

La fonctionnalité de **traduction automatique des dialogues** permet de traduire tous les timecodes d'un projet vers une autre langue. Elle utilise des services de traduction professionnels avec support du contexte des personnages pour améliorer la qualité.

### 🎯 Tableau comparatif des services

| Service | Qualité | Gratuit | Limite | Config | Recommandation |
|---------|---------|---------|--------|--------|----------------|
| **DeepL** | ⭐⭐⭐⭐⭐ | ✅ 500k/mois | Très généreuse | API key | **MEILLEURE QUALITÉ** |
| **Google Translate** | ⭐⭐⭐⭐ | ⚠️ Crédits | $300 gratuits | API key | Bon choix général |
| **MyMemory** | ⭐⭐⭐ | ✅ 10k/jour | Limitée | Aucune | **PLUS SIMPLE** |
| **LibreTranslate** | ⭐⭐⭐ | ✅ Oui | Selon instance | Optionnelle | Open-source |

**Recommandations** :
- 🥇 **Pour la meilleure qualité** : DeepL (gratuit jusqu'à 500k chars/mois)
- 🥈 **Pour simplicité maximale** : MyMemory (aucune config, immédiat)
- 🥉 **Pour gros volumes** : Google Translate ($300 crédits gratuits)

### Fonctionnalités principales

- ✅ **Traduction automatique** de tous les timecodes
- ✅ **Multi-langues** : Support de 15+ langues (selon le provider)
- ✅ **Détection automatique** de la langue source
- ✅ **Contexte personnages** : Utilise les noms des personnages pour améliorer la traduction
- ✅ **Trois modes d'utilisation** :
  - Extraction + traduction simultanée (futur)
  - Traduction standalone (actuel)
  - Re-traduction vers autre langue
- ✅ **Progression en temps réel** avec annulation possible
- ✅ **Rate limiting** pour respecter les limites des APIs gratuites

## 🔧 Configuration

### 1. Services de traduction disponibles

#### DeepL (Recommandé pour la MEILLEURE qualité)

**Leader mondial en qualité de traduction**

- ⭐⭐⭐⭐⭐ **Qualité exceptionnelle** : Meilleure traduction du marché
- ✅ **500k caractères/mois GRATUIT** : Largement suffisant
- ✅ **Aucune installation** : Fonctionne immédiatement
- ✅ **API key gratuite** : Inscription simple
- ✅ **30+ langues** : Toutes les principales
- ✅ **Support contexte** : Amélioration avec noms personnages

**Obtenir une clé gratuite** : https://www.deepl.com/pro-api

**Configuration dans `.env`** :
```bash
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=deepl
AI_DEEPL_API_KEY=votre-cle-api-ici
```

#### Google Translate (Très bonne qualité)

**Service Google Cloud Translation**

- ⭐⭐⭐⭐ **Très bonne qualité** : Fiable et rapide
- ✅ **100+ langues** : Le plus large choix
- ⚠️ **Payant** : Mais crédits gratuits lors de l'inscription
- ✅ **Aucune installation** : API cloud
- ✅ **Détection automatique langue** : Très précise

**Obtenir une clé** : https://cloud.google.com/translate/docs/setup

**Configuration dans `.env`** :
```bash
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=google
AI_GOOGLE_API_KEY=votre-cle-api-ici
```

#### MyMemory (100% gratuit, aucune config)

**Service gratuit sans configuration**

- ⭐⭐⭐ **Bonne qualité** : Suffisant pour la plupart des cas
- ✅ **Aucune installation** : Fonctionne immédiatement
- ✅ **Aucune API key** : Totalement gratuit
- ✅ **API publique** : `https://api.mymemory.translated.net`
- ✅ **50+ langues**
- ⚠️ **Limitation** : 10 000 caractères/jour

**Configuration dans `.env`** :
```bash
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=mymemory
```

**C'est tout !** Aucune autre configuration nécessaire.

#### LibreTranslate (Open-source)

**Alternative open-source**

- ⭐⭐⭐ **Qualité correcte** : Open-source, amélioré continuellement
- ✅ **API publique gratuite** : Sans installation
- ⚠️ **Limites requêtes** : API key optionnelle
- ✅ **30+ langues**
- 🐳 **Auto-hébergement possible** : Nécessite Docker pour illimité

**Configuration dans `.env`** :
```bash
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=libretranslate
AI_LIBRETRANSLATE_API_URL=https://libretranslate.com
```

### 2. Configuration Backend (.env)

#### Configuration RECOMMANDÉE (DeepL - meilleure qualité)

1. **Obtenir une clé gratuite DeepL** : https://www.deepl.com/pro-api
2. Ajoutez dans `agfa-rythmo-backend/.env` :

```bash
# Traduction avec DeepL (meilleure qualité)
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=deepl
AI_DEEPL_API_KEY=votre-cle-deepl-ici
```

3. Rechargez la config :
```bash
php artisan config:cache
```

**Terminé !** ✨

#### Configuration SIMPLE (MyMemory - aucune config)

Ajoutez ces 2 lignes dans `agfa-rythmo-backend/.env` :

```bash
# Traduction automatique avec MyMemory (gratuit, aucune config)
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=mymemory
```

**Terminé !** La traduction fonctionne immédiatement.

#### Configuration AVANCÉE (LibreTranslate - optionnel)

**Option A : API publique LibreTranslate (sans Docker)**

Ajoutez dans `agfa-rythmo-backend/.env` :

```bash
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=libretranslate
AI_LIBRETRANSLATE_API_URL=https://libretranslate.com
AI_LIBRETRANSLATE_API_KEY=          # Optionnel
```

**Option B : LibreTranslate auto-hébergé (avec Docker)**

```bash
# .env
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=libretranslate
AI_LIBRETRANSLATE_API_URL=http://localhost:5000
```

Puis lancer Docker :
```bash
docker run -ti --rm -p 5000:5000 libretranslate/libretranslate
```

### 3. Activation de la fonctionnalité

Après avoir modifié `.env`, rechargez la configuration :

```bash
cd agfa-rythmo-backend
php artisan config:cache
```

La traduction est maintenant disponible dans le menu IA ! 🎉

## 🚀 Utilisation

### Workflow complet

1. **Ouvrir le projet** avec des timecodes existants
2. **Cliquer sur le bouton "IA"** (gradient violet/rose, en haut de l'interface)
3. **Menu IA s'ouvre** avec toutes les fonctionnalités disponibles
4. **Cliquer sur "Traduction automatique"** (carte orange/jaune avec icône globe)
   - ⚠️ Désactivée si aucun timecode n'existe
5. **Configurer la traduction** :
   - Langue source : auto-détection ou manuelle
   - Langue cible : sélectionner dans la liste
   - Contexte personnages : cocher/décocher
6. **Lancer la traduction**
7. **Suivre la progression** :
   - Barre de progression 0-100%
   - Message de statut en temps réel
   - Statistiques (timecodes totaux, traduits)
   - Bouton annuler disponible
8. **Traduction terminée** :
   - Timecodes mis à jour automatiquement
   - Notification de succès
   - Rafraîchissement auto des bandes rythmo

### Modes d'utilisation

#### Mode 1 : Extraction + Traduction (futur)
```
Vidéo → Extraction dialogues (Whisper) → Traduction automatique → Timecodes traduits
```

#### Mode 2 : Traduction standalone (actuel)
```
Timecodes existants → Traduction automatique → Timecodes traduits
```

#### Mode 3 : Re-traduction
```
Timecodes traduits (fr) → Re-traduction (en) → Timecodes re-traduits
```

### Contexte des personnages

**Améliore la qualité de traduction en fournissant le nom du personnage**

Exemple sans contexte :
```
Source: "He's coming!"
Traduction: "Il arrive !"
```

Exemple avec contexte (Character: "Dr. Smith"):
```
Source: "He's coming!" [Context: Dr. Smith]
Traduction: "Le Dr Smith arrive !"
```

**Activation** :
- Configuré via `AI_TRANSLATION_USE_CONTEXT=true` (par défaut)
- Désactivable par utilisateur dans la modal de configuration
- Recommandé si vous avez défini des personnages

## 📊 Architecture Technique

### Backend (Laravel)

#### Migration
```php
// database/migrations/2025_10_31_134844_add_translation_to_projects_table.php
$table->string('translation_status')->nullable();
$table->integer('translation_progress')->default(0);
$table->text('translation_message')->nullable();
$table->string('source_language')->nullable();
$table->string('target_language')->nullable();
```

#### Service TranslationService
```php
// app/Services/TranslationService.php
- translate($text, $targetLang, $sourceLang, $context)
- detectLanguage($text)
- getSupportedLanguages()
- translateLibreTranslate()
- translateMyMemory()
- detectLanguageSimple() // Fallback regex-based
- getDefaultLanguages() // 15 langues par défaut
```

#### Job TranslateDialogues
```php
// app/Jobs/TranslateDialogues.php
- Timeout: 1800s (30 minutes)
- Auto-détection langue source (5% progression)
- Extraction contexte personnages
- Boucle timecodes avec progression 5-100%
- Vérification annulation toutes les 5 timecodes
- Rate limiting: 100ms entre requêtes (usleep)
- Gestion erreurs individuelles sans bloquer job
- Stats finales: X traduit(s), Y échec(s)
```

#### Controller TranslationController
```php
// app/Http/Controllers/Api/TranslationController.php
- POST startTranslation(): Valide + dispatch job
- GET getStatus(): Retourne état + progression
- POST cancelTranslation(): Marque status 'cancelled'
- GET getSupportedLanguages(): Récupère langues du provider
```

#### Routes API
```php
// routes/api.php
POST   /api/projects/{project}/translation/start
GET    /api/projects/{project}/translation/status
POST   /api/projects/{project}/translation/cancel
GET    /api/translation/supported-languages
```

### Frontend (Vue 3 + TypeScript)

#### Service API
```typescript
// src/api/translation.ts
- startTranslation(projectId, options)
- getTranslationStatus(projectId)
- cancelTranslation(projectId)
- getSupportedLanguages()
```

#### Composants

**TranslateDialoguesModal.vue**
- Sélecteur langue source (auto-détection)
- Sélecteur langue cible (requis)
- Checkbox contexte personnages
- Info provider
- Validation + émission événement @start

**TranslationProgress.vue**
- Polling status toutes les 2s
- Barre progression 0-100%
- Message statut avec icônes
- Stats (totaux, traduits)
- Bouton annuler
- Émissions: @completed, @failed, @cancelled

**AiMenuModal.vue (mise à jour)**
- Carte traduction (gradient orange/yellow, icône globe)
- Désactivée si `!hasTimecodes`
- Warning si pas de timecodes
- Émission @start-translation

#### ProjectDetailView.vue (intégration)
```typescript
// 5 handlers
- handleStartTranslation(): Ouvre modal settings
- handleTranslationStart(options): Lance traduction via API
- handleTranslationCompleted(): Recharge timecodes + refresh
- handleTranslationFailed(message): Affiche erreur
- handleTranslationCancelled(): Notification annulation
```

#### ServerCapabilities
```typescript
interface ServerCapabilities {
  translation: boolean;
  supported_languages?: SupportedLanguage[];
  // ...
}
```

## 🔍 Détection de langue

### Méthode 1 : API (LibreTranslate)
```
POST https://libretranslate.com/detect
Body: { "q": "Hello world" }
Response: [{ "confidence": 0.95, "language": "en" }]
```

### Méthode 2 : Regex Fallback (MyMemory)
```typescript
// Détection basée sur patterns Unicode
- Chinois/Japonais/Coréen: /[\u4E00-\u9FFF\u3040-\u309F\u30A0-\u30FF\uAC00-\uD7AF]/
- Arabe: /[\u0600-\u06FF]/
- Cyrillique (russe): /[\u0400-\u04FF]/
- Défaut: 'en'
```

## 📝 Langues supportées

### Par défaut (15 langues)
- 🇬🇧 English (en)
- 🇫🇷 Français (fr)
- 🇪🇸 Español (es)
- 🇩🇪 Deutsch (de)
- 🇮🇹 Italiano (it)
- 🇵🇹 Português (pt)
- 🇷🇺 Русский (ru)
- 🇨🇳 中文 (zh)
- 🇯🇵 日本語 (ja)
- 🇰🇷 한국어 (ko)
- 🇸🇦 العربية (ar)
- 🇮🇳 हिन्दी (hi)
- 🇳🇱 Nederlands (nl)
- 🇵🇱 Polski (pl)
- 🇹🇷 Türkçe (tr)

### Provider-specific
- **DeepL** : 30+ langues (toutes principales + variantes régionales)
- **Google Translate** : 100+ langues (le plus complet)
- **LibreTranslate** : 30+ langues (dépend du modèle)
- **MyMemory** : 50+ langues

## ⚠️ Limitations & Considérations

### Rate Limiting

**DeepL** :
- Limite : 500 000 caractères/mois (gratuit)
- ~1000 timecodes/mois (en moyenne)
- Solution : Plan payant si besoin

**Google Translate** :
- Limite : $300 crédits gratuits initiaux
- Ensuite payant (~$20/million de caractères)
- Solution : Très généreux pour usage normal

**MyMemory** :
- Limite : 10 000 caractères/jour (gratuit)
- ~20 timecodes/jour
- Solution : Utiliser DeepL ou Google pour grands projets

**LibreTranslate API publique** :
- Limite : Variable selon plan
- Solution : API key payante ou auto-hébergement

**Protection implémentée** :
```php
// Job TranslateDialogues.php
usleep(100000); // 100ms entre chaque requête
```

### Performance

**Facteurs** :
- Nombre de timecodes
- Longueur des textes
- Provider utilisé
- Connexion réseau

**Temps estimés** :
- 50 timecodes : ~2-3 minutes
- 100 timecodes : ~5-7 minutes
- 500 timecodes : ~20-30 minutes

### Qualité de traduction

**Classement qualité** :
1. ⭐⭐⭐⭐⭐ **DeepL** : Excellente, nuances linguistiques
2. ⭐⭐⭐⭐ **Google** : Très bonne, fiable
3. ⭐⭐⭐ **LibreTranslate** : Bonne, open-source
4. ⭐⭐⭐ **MyMemory** : Correcte, suffisante

**Facteurs d'amélioration** :
- ✅ Utiliser contexte personnages
- ✅ Phrases courtes et simples
- ✅ DeepL recommandé pour qualité professionnelle

**Limitations** :
- ❌ Pas de support contexte vidéo
- ❌ Pas de gestion idiomes/expressions
- ❌ Qualité variable selon paire de langues

## 🐛 Troubleshooting

### Erreur : "Impossible de démarrer la traduction"

**Causes possibles** :
1. Aucun timecode dans le projet
2. Traduction déjà en cours
3. Provider inaccessible

**Solutions** :
```bash
# Vérifier config
php artisan config:cache

# Tester provider
curl https://libretranslate.com/languages

# Vérifier logs
php artisan pail
```

### Erreur : "Translation failed"

**Causes** :
- API indisponible
- Rate limit dépassé
- Langue non supportée

**Solutions** :
```bash
# Vérifier status projet
php artisan tinker
>>> $project = App\Models\Project::find(1);
>>> $project->translation_status;
>>> $project->translation_message;

# Reset status
>>> $project->update(['translation_status' => null]);
```

### Traduction incomplète

**Symptômes** : Job terminé mais certains timecodes non traduits

**Causes** :
- Erreurs API individuelles
- Textes vides

**Debug** :
```bash
# Vérifier message final
>>> $project->translation_message;
// "X timecode(s) traduit(s), Y échec(s) (fr → en)"

# Vérifier logs
tail -f storage/logs/laravel.log | grep Translation
```

### Performance lente

**Optimisations** :
1. Auto-héberger LibreTranslate (serveur local)
2. Réduire timeout si possible
3. Traduire par lots (futur)

## 🔐 Sécurité

### API Keys

**Ne jamais committer les clés dans Git** :
```bash
# .env (OK)
AI_LIBRETRANSLATE_API_KEY=secret-key

# .env.example (NON!)
AI_LIBRETRANSLATE_API_KEY=          # Laisser vide
```

### Validation Backend

**Toujours valider côté serveur** :
```php
// TranslationController.php
$validated = $request->validate([
    'target_language' => 'required|string|min:2|max:5',
    'source_language' => 'nullable|string|min:2|max:5',
]);
```

### Permissions

**Vérifier accès utilisateur** :
```php
if (!$project->canModify(auth()->user())) {
    return response()->json(['message' => 'Accès refusé'], 403);
}
```

## 📈 Futures Améliorations

### Phase 3 (Planifié)

- [ ] **Extraction + Traduction** : Mode combo Whisper → Traduction
- [ ] **Traduction par lots** : Grouper plusieurs timecodes par requête
- [ ] **Cache traductions** : Éviter re-traductions identiques
- [ ] **Plus de providers** :
  - Google Translate API
  - DeepL API
  - OpenAI GPT (contexte vidéo)
- [ ] **UI validation post-traduction** :
  - Comparaison source/cible
  - Édition inline
  - Fusion/split timecodes
- [ ] **Support GPU** : Modèles locaux (mBART, MarianMT)
- [ ] **Mémoire de traduction** : Base de données de segments traduits

## 📚 Ressources

### Documentation APIs
- LibreTranslate : https://libretranslate.com/docs
- MyMemory : https://mymemory.translated.net/doc/spec.php

### Repos GitHub
- LibreTranslate : https://github.com/LibreTranslate/LibreTranslate
- AgfaRythmo : (votre repo)

### Support
- Issues : (GitHub issues de votre projet)
- Email : (votre email de support)

---

**Dernière mise à jour** : 31 octobre 2025
**Version** : 2.2.0-beta
**Auteur** : Martin P. (@ParizetM)
