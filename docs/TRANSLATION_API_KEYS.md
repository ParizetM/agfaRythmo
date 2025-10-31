# 🔑 Guide rapide : Obtenir vos clés API de traduction

## DeepL (Recommandé - Meilleure qualité)

### Gratuit : 500 000 caractères/mois

1. **Créer un compte gratuit** : https://www.deepl.com/pro-api?cta=header-pro-api/
2. Cliquer sur **"Sign up for free"**
3. Remplir le formulaire (email, mot de passe)
4. **Confirmer l'email**
5. Se connecter au compte
6. Aller dans **"Account"** → **"API Keys"**
7. Copier votre **Authentication Key**

### Configuration dans AgfaRythmo

```bash
# Dans agfa-rythmo-backend/.env
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=deepl
AI_DEEPL_API_KEY=97246706-61d5-4f71-b0b9-57656eae43af:fx
```

---

## Google Translate

### Crédits gratuits : $300 (dure plusieurs mois)

1. **Créer un compte Google Cloud** : https://cloud.google.com/
2. Activer l'**essai gratuit** (carte bancaire requise, non débitée)
3. Créer un nouveau projet
4. Activer **Cloud Translation API** : https://console.cloud.google.com/apis/library/translate.googleapis.com
5. Aller dans **"APIs & Services"** → **"Credentials"**
6. Cliquer **"Create Credentials"** → **"API Key"**
7. Copier la clé générée

### Configuration dans AgfaRythmo

```bash
# Dans agfa-rythmo-backend/.env
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=google
AI_GOOGLE_API_KEY=votre-cle-ici
```

---

## MyMemory (Le plus simple)

### Gratuit : 10 000 caractères/jour

**Aucune inscription requise !**

### Configuration dans AgfaRythmo

```bash
# Dans agfa-rythmo-backend/.env
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=mymemory
# C'est tout ! Aucune clé API nécessaire
```

---

## LibreTranslate

### Option 1 : API publique gratuite (avec limites)

**Aucune clé requise pour usage basique**

```bash
# Dans agfa-rythmo-backend/.env
AI_TRANSLATION_ENABLED=true
AI_TRANSLATION_PROVIDER=libretranslate
AI_LIBRETRANSLATE_API_URL=https://libretranslate.com
```

### Option 2 : Obtenir une clé API (optionnel)

1. Aller sur : https://portal.libretranslate.com/
2. S'inscrire gratuitement
3. Copier votre clé API

```bash
AI_LIBRETRANSLATE_API_KEY=votre-cle-ici
```

### Option 3 : Auto-hébergement (illimité, nécessite Docker)

```bash
# Lancer LibreTranslate en local
docker run -ti --rm -p 5000:5000 libretranslate/libretranslate

# Dans .env
AI_LIBRETRANSLATE_API_URL=http://localhost:5000
```

---

## 🧪 Tester votre configuration

Après avoir configuré `.env`, rechargez la config Laravel :

```bash
cd agfa-rythmo-backend
php artisan config:cache
```

Puis testez dans l'interface :
1. Ouvrir un projet avec des timecodes
2. Cliquer sur le bouton **"IA"**
3. Vérifier que **"Traduction automatique"** est disponible
4. Lancer une traduction test

---

## ❓ FAQ

**Q: Quelle clé API recommandez-vous ?**
A: **DeepL gratuit** (500k/mois) pour la meilleure qualité, sinon **MyMemory** (aucune config).

**Q: La carte bancaire est-elle nécessaire ?**
A: 
- DeepL : Non ❌
- Google : Oui ⚠️ (mais jamais débitée si vous restez dans les crédits gratuits)
- MyMemory : Non ❌
- LibreTranslate : Non ❌

**Q: Puis-je changer de provider ?**
A: Oui ! Changez juste `AI_TRANSLATION_PROVIDER` dans `.env` et relancez `php artisan config:cache`.

**Q: Combien coûte après la période gratuite ?**
A:
- DeepL : $5.49/million de caractères
- Google : $20/million de caractères
- MyMemory : Toujours gratuit (10k/jour)
- LibreTranslate : Toujours gratuit (auto-hébergé)

---

**Guide complet** : Voir `TRANSLATION_GUIDE.md` pour documentation complète.
