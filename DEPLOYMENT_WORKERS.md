# Guide de Déploiement - Workers de Queue en Production

**Version** : 2.1.0-beta | **Date** : 27 octobre 2025

## 📋 Prérequis

### Types d'Installation Supportés

AgfaRythmo peut être installé sur deux types de serveurs :

1. **Serveur Standard (avec FFmpeg)** : Toutes les fonctionnalités disponibles incluant l'analyse IA
2. **Serveur PHP-only (sans FFmpeg)** : Installation basique sans les fonctionnalités d'analyse IA

### Serveur Standard (Complet)
- PHP 8.2+ avec extensions : PDO, SQLite, GD, MBString
- **FFmpeg installé et accessible dans PATH** (pour analyse IA)
- Composer 2.x
- Node.js 18+ (pour build frontend)
- Serveur web : Nginx ou Apache
- Accès SSH au serveur

### Serveur PHP-only (Basique)
- PHP 8.2+ avec extensions : PDO, SQLite, GD, MBString
- Composer 2.x
- Node.js 18+ (pour build frontend)
- Serveur web : Nginx ou Apache
- **FFmpeg non requis** - L'application désactivera automatiquement les fonctionnalités IA

### Vérifications avant déploiement
```bash
# Vérifier PHP
php -v

# Vérifier FFmpeg (optionnel)
ffmpeg -version
# Si cette commande échoue, l'application fonctionnera en mode basique sans IA

# Vérifier les extensions PHP
php -m | grep -E 'pdo|sqlite|gd|mbstring'
```

### 🔍 Détection Automatique des Capacités

L'application détecte automatiquement les fonctionnalités disponibles au démarrage :
- **FFmpeg disponible** → Bouton "IA" affiché pour l'analyse automatique
- **FFmpeg non disponible** → Badge "IA non disponible" + tooltip explicatif
- Aucune configuration manuelle nécessaire

---

## 🚀 Méthodes de Déploiement des Workers

**⚠️ Important** : Les workers de queue sont **optionnels** et ne sont nécessaires que si FFmpeg est installé pour l'analyse IA. Sur un serveur PHP-only, vous pouvez ignorer cette section.

### ⭐ Option 1 : Supervisor (RECOMMANDÉ)

**Avantages** : Redémarrage automatique, gestion multi-workers, logs centralisés

#### Installation
```bash
# Ubuntu/Debian
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor
```

#### Configuration

1. **Copier le fichier de configuration**
```bash
sudo cp supervisor-agfaRythmo-worker.conf /etc/supervisor/conf.d/agfaRythmo-worker.conf
```

2. **Éditer le fichier** et remplacer `/path/to/` par le vrai chemin
```bash
sudo nano /etc/supervisor/conf.d/agfaRythmo-worker.conf
```

Modifiez :
- `command=php /var/www/agfa-rythmo-backend/artisan ...`
- `stdout_logfile=/var/www/agfa-rythmo-backend/storage/logs/worker.log`
- `user=www-data` (ou votre utilisateur web)

3. **Activer et démarrer**
```bash
# Recharger la configuration
sudo supervisorctl reread
sudo supervisorctl update

# Démarrer les workers
sudo supervisorctl start agfaRythmo-worker:*

# Vérifier le statut
sudo supervisorctl status
```

#### Commandes utiles Supervisor
```bash
# Voir les logs en temps réel
sudo tail -f /var/www/agfa-rythmo-backend/storage/logs/worker.log

# Redémarrer les workers (après mise à jour du code)
sudo supervisorctl restart agfaRythmo-worker:*

# Arrêter les workers
sudo supervisorctl stop agfaRythmo-worker:*

# Statut détaillé
sudo supervisorctl status agfaRythmo-worker:*
```

---

### ⚙️ Option 2 : Systemd

**Avantages** : Intégration native Linux, gestion des dépendances, journalisation systemd

#### Installation

1. **Copier le fichier service**
```bash
sudo cp agfaRythmo-worker.service /etc/systemd/system/
```

2. **Éditer le fichier** et ajuster les chemins
```bash
sudo nano /etc/systemd/system/agfaRythmo-worker.service
```

3. **Activer et démarrer**
```bash
# Recharger systemd
sudo systemctl daemon-reload

# Activer au démarrage
sudo systemctl enable agfaRythmo-worker

# Démarrer le service
sudo systemctl start agfaRythmo-worker

# Vérifier le statut
sudo systemctl status agfaRythmo-worker
```

#### Commandes utiles Systemd
```bash
# Voir les logs
sudo journalctl -u agfaRythmo-worker -f

# Redémarrer
sudo systemctl restart agfaRythmo-worker

# Arrêter
sudo systemctl stop agfaRythmo-worker

# Désactiver
sudo systemctl disable agfaRythmo-worker
```

---

### 🕐 Option 3 : Cron (Simple)

**Avantages** : Simple, pas de dépendances
**Inconvénients** : Pas de redémarrage automatique en cas de crash

#### Configuration

1. **Rendre le script exécutable**
```bash
chmod +x start-worker.sh
```

2. **Ajouter au crontab**
```bash
crontab -e
```

Ajouter cette ligne :
```cron
* * * * * /var/www/agfa-rythmo-backend/start-worker.sh >> /var/www/agfa-rythmo-backend/storage/logs/cron-worker.log 2>&1
```

---

## 🔧 Configuration Laravel

### .env Production

Assurez-vous d'avoir ces paramètres :

```env
# Queue
QUEUE_CONNECTION=database

# Timeouts
QUEUE_WORKER_TIMEOUT=600
QUEUE_JOB_MAX_TRIES=3

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=info
```

### Optimisations Production

```bash
# Dans le dossier agfa-rythmo-backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📊 Monitoring et Maintenance

### Vérifier l'état des jobs

```bash
# Via tinker
php artisan tinker
>>> DB::table('jobs')->count();
>>> DB::table('failed_jobs')->count();
```

### Nettoyer les jobs échoués

```bash
# Supprimer tous les jobs échoués
php artisan queue:flush

# Relancer les jobs échoués
php artisan queue:retry all
```

### Logs à surveiller

```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs worker
tail -f storage/logs/worker.log

# Logs FFmpeg (temporaires)
ls -lh storage/app/temp_*
```

---

## 🔄 Déploiement des mises à jour

### Workflow recommandé

```bash
# 1. Pull des changements
git pull origin main

# 2. Mise à jour des dépendances
composer install --optimize-autoloader --no-dev

# 3. Migrations si nécessaire
php artisan migrate --force

# 4. Rebuild frontend
cd ../agfa-rythmo-frontend
npm install
npm run build

# 5. Optimisations
cd ../agfa-rythmo-backend
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Redémarrer les workers (choisir selon votre méthode)
# Supervisor:
sudo supervisorctl restart agfaRythmo-worker:*

# Systemd:
sudo systemctl restart agfaRythmo-worker

# 7. Signaler aux workers de se recharger
php artisan queue:restart
```

---

## 🐛 Dépannage

### Le worker ne démarre pas

```bash
# Vérifier les permissions
ls -la storage/
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# Vérifier les logs
tail -100 storage/logs/laravel.log

# Tester manuellement
php artisan queue:work --once --verbose
```

### FFmpeg introuvable

```bash
# Vérifier l'installation
which ffmpeg

# Installer si nécessaire (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install ffmpeg

# Vérifier que PHP peut l'exécuter
php -r "exec('ffmpeg -version', \$output); print_r(\$output);"
```

### Jobs bloqués en "processing"

```bash
# Réinitialiser les jobs
sqlite3 database/database.sqlite "UPDATE jobs SET reserved_at = NULL WHERE reserved_at IS NOT NULL;"

# Redémarrer les workers
sudo supervisorctl restart agfaRythmo-worker:*
```

---

## 📈 Performance et Scalabilité

### Plusieurs workers en parallèle

Dans `supervisor-agfaRythmo-worker.conf`, augmenter :
```ini
numprocs=4  # Pour 4 workers en parallèle
```

### Worker dédié pour l'analyse IA

Créer une queue spécifique :

```php
// Dans DetectSceneChanges.php
public $queue = 'analysis';
```

```bash
# Worker normal
php artisan queue:work --queue=default

# Worker pour analyse (plus de timeout)
php artisan queue:work --queue=analysis --timeout=1800
```

---

## ✅ Checklist de déploiement

- [ ] FFmpeg installé et accessible
- [ ] Extensions PHP requises installées
- [ ] Permissions storage/ correctes (775)
- [ ] .env configuré pour production
- [ ] Worker configuré (Supervisor/Systemd/Cron)
- [ ] Worker démarré et actif
- [ ] Logs accessibles et monitored
- [ ] Frontend buildé et déployé
- [ ] Cache Laravel optimisé
- [ ] Test d'une analyse IA réussie

---

## 📞 Support

Pour plus d'informations :
- Documentation Laravel Queue : https://laravel.com/docs/12.x/queues
- FFmpeg Documentation : https://ffmpeg.org/documentation.html
- Supervisor Documentation : http://supervisord.org/

**Maintainer** : Martin P. (@ParizetM)
**Dernière mise à jour** : 27 octobre 2025
