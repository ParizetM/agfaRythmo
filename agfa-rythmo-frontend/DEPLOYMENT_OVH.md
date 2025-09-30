# Guide de déploiement OVH - AgfaRythmo

## Hébergement Web OVH (mutualisé)

### Étapes :
1. Construire le projet : `npm run build`
2. Uploader le contenu du dossier `dist/` vers le dossier `www/` de votre hébergement
3. Le fichier `.htaccess` sera automatiquement pris en compte

### Fichiers importants :
- `.htaccess` : Gestion des redirections SPA
- `_redirects` : Fallback (non utilisé sur Apache)

## VPS/Serveur dédié OVH

### Si Apache :
- Utiliser le fichier `.htaccess` fourni
- S'assurer que mod_rewrite est activé : `a2enmod rewrite`

### Si Nginx :
- Utiliser la configuration dans `nginx.conf.example`
- Redémarrer Nginx : `sudo systemctl reload nginx`

## Variables d'environnement

Créer un fichier `.env.production` avec :
```
VITE_API_URL=https://votre-api.ovh.com/api
VITE_APP_URL=https://votre-site.ovh.com
```

## Vérification du déploiement

1. Accéder à votre site : `https://votre-domaine.com`
2. Naviguer vers une page : `https://votre-domaine.com/projects`
3. Rafraîchir la page (F5) - Elle ne doit PAS afficher "404 Not Found"
4. Tester différentes routes et rafraîchir chacune

## Troubleshooting

### Erreur 404 lors du rafraîchissement :
- Vérifier que `.htaccess` est bien présent dans le dossier racine
- Vérifier que mod_rewrite est activé
- Vérifier les permissions du fichier `.htaccess`

### Erreurs CORS :
- Configurer correctement VITE_API_URL dans .env.production
- S'assurer que l'API backend autorise votre domaine frontend