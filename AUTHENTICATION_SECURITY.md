# Sécurité de l'authentification - AgfaRythmo

## Vue d'ensemble

Le système d'authentification de l'application vérifie maintenant systématiquement la validité de la session utilisateur à plusieurs niveaux pour garantir une sécurité optimale.

## Mécanismes de vérification

### 1. Vérification au chargement de chaque route (Router Guard)

**Fichier**: `agfa-rythmo-frontend/src/router/index.ts`

Pour chaque navigation vers une route protégée (`requiresAuth: true`), le système :
- Vérifie la présence d'un token dans le localStorage
- Appelle le backend pour valider le token via `authStore.checkAuth()`
- Redirige vers `/login` si le token est invalide ou expiré
- Préserve l'URL de destination pour redirection après login

```typescript
if (to.meta.requiresAuth) {
  if (!authStore.token) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }
  
  const isValid = await authStore.checkAuth()
  if (!isValid) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }
}
```

### 2. Intercepteur Axios pour les erreurs 401/403

**Fichier**: `agfa-rythmo-frontend/src/api/axios.ts`

Toutes les requêtes API sont surveillées :
- **401 Unauthorized** : Token invalide/expiré → déconnexion automatique + redirection login
- **403 Forbidden** : Permissions insuffisantes → log d'avertissement
- Sauvegarde de l'URL actuelle pour redirection post-login

```typescript
if (error.response?.status === 401) {
  localStorage.removeItem('auth_token')
  delete api.defaults.headers.common['Authorization']
  
  const redirectUrl = encodeURIComponent(window.location.pathname + window.location.search)
  window.location.href = `/login?redirect=${redirectUrl}`
}
```

### 3. Vérification périodique en arrière-plan

**Fichier**: `agfa-rythmo-frontend/src/App.vue`

L'application vérifie automatiquement la validité du token :
- **Au montage de l'application** : vérification immédiate
- **Toutes les 5 minutes** : vérification périodique pendant l'utilisation
- Déconnexion automatique si le token devient invalide

```typescript
authCheckInterval = window.setInterval(async () => {
  if (authStore.isAuthenticated) {
    const isValid = await authStore.checkAuth()
    if (!isValid && router.currentRoute.value.meta.requiresAuth) {
      router.push('/login')
    }
  }
}, 5 * 60 * 1000) // 5 minutes
```

### 4. Méthode de vérification dans le store

**Fichier**: `agfa-rythmo-frontend/src/stores/auth.ts`

Nouvelle méthode `checkAuth()` :
- Appelle l'API `/user` pour récupérer le profil
- Met à jour les données utilisateur si valide
- Nettoie la session si invalide
- Retourne `true/false` pour indiquer la validité

```typescript
const checkAuth = async (): Promise<boolean> => {
  if (!token.value) {
    clearAuth()
    return false
  }

  try {
    await fetchProfile()
    return true
  } catch {
    clearAuth()
    return false
  }
}
```

## Scénarios couverts

### ✅ Token expiré
- Détection lors de la navigation
- Détection lors d'une requête API
- Détection lors de la vérification périodique
→ **Déconnexion automatique + redirection login**

### ✅ Token supprimé côté serveur
- Détection immédiate lors de la prochaine requête API (401)
→ **Déconnexion automatique + redirection login**

### ✅ Utilisateur désactivé/supprimé
- Détection lors de la vérification périodique
- Détection lors de toute requête API
→ **Déconnexion automatique + redirection login**

### ✅ Permissions révoquées
- Détection via erreur 403 sur routes protégées
- Log d'avertissement pour debugging
→ **Message d'erreur approprié**

### ✅ Token modifié/corrompu
- Détection immédiate lors de la validation backend
→ **Déconnexion automatique + redirection login**

## Configuration

### Intervalle de vérification périodique
Par défaut : **5 minutes**

Pour modifier l'intervalle, éditer `App.vue` :
```typescript
}, 5 * 60 * 1000) // Modifier ici (en millisecondes)
```

### Routes protégées
Pour protéger une route, ajouter `requiresAuth: true` dans les métadonnées :
```typescript
{
  path: '/ma-route',
  component: MonComposant,
  meta: { requiresAuth: true }
}
```

### Routes admin
Pour les routes nécessitant les droits admin :
```typescript
{
  path: '/admin',
  component: AdminView,
  meta: { requiresAuth: true, requiresAdmin: true }
}
```

## Bonnes pratiques

1. **Ne jamais stocker d'informations sensibles** dans le localStorage en plus du token
2. **Toujours utiliser HTTPS** en production
3. **Configurer une durée d'expiration appropriée** pour les tokens côté backend
4. **Surveiller les logs** pour détecter les tentatives d'accès non autorisées
5. **Implémenter un système de refresh token** pour améliorer l'UX (future amélioration)

## Améliorations futures possibles

- [ ] Système de refresh token pour éviter les déconnexions inopinées
- [ ] Détection d'activité utilisateur pour ne vérifier que quand actif
- [ ] Notification visuelle avant expiration du token
- [ ] Support de l'authentification multi-facteurs (2FA)
- [ ] Session partagée entre onglets (BroadcastChannel API)
