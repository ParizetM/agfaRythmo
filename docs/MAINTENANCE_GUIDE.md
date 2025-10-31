# 🔧 MODE MAINTENANCE - GUIDE RAPIDE

## Comment activer/désactiver la maintenance ?

### Via FTP (MÉTHODE RECOMMANDÉE)

1. **Connectez-vous à votre serveur via FTP**
2. **Naviguez vers** : `agfa-rythmo-backend/storage/framework/`
3. **Pour ACTIVER la maintenance** :
   - Renommez le fichier `RENAME_TO_maintenance_TO_ENABLE` en `maintenance`
4. **Pour DÉSACTIVER la maintenance** :
   - Renommez le fichier `maintenance` en `RENAME_TO_maintenance_TO_ENABLE`

C'est tout ! Aucun redémarrage nécessaire, c'est instantané.

---

## Comment ça fonctionne ?

- ✅ Le backend vérifie l'existence du fichier `storage/framework/maintenance`
- ✅ Si le fichier existe → tous les appels API retournent une erreur 503
- ✅ Le frontend détecte le code 503 et redirige vers la page de maintenance
- ✅ Pas besoin de redémarrer le serveur ou l'application

---

## Exemple d'utilisation

**Avant une mise à jour :**
```
1. Renommer RENAME_TO_maintenance_TO_ENABLE → maintenance
2. L'application affiche la page de maintenance
3. Faire vos mises à jour en toute sécurité
4. Renommer maintenance → RENAME_TO_maintenance_TO_ENABLE
5. L'application est de nouveau accessible
```

---

## Notes importantes

- 📁 Le fichier doit être dans : `storage/framework/`
- 📝 Nom exact pour activer : `maintenance` (sans extension)
- ⚡ Changement immédiat, pas de cache
- 🔒 Tous les utilisateurs sont affectés (même les admins)

---

## En cas de problème

Si la maintenance ne s'active pas :
1. Vérifiez que le fichier s'appelle exactement `maintenance`
2. Vérifiez qu'il est dans `storage/framework/`
3. Vérifiez les permissions du dossier storage/

---

Fait avec ❤️ pour faciliter la maintenance !
