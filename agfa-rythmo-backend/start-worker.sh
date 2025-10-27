#!/bin/bash

# Script pour lancer le worker de queue Laravel
# À placer dans un cron toutes les minutes

# Chemins
ARTISAN_PATH="/path/to/agfa-rythmo-backend/artisan"
PIDFILE="/tmp/agfaRythmo-worker.pid"

# Vérifier si le worker tourne déjà
if [ -f "$PIDFILE" ] && kill -0 $(cat "$PIDFILE") 2>/dev/null; then
    # Worker déjà en cours
    exit 0
fi

# Lancer le worker en arrière-plan
cd /path/to/agfa-rythmo-backend
php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=600 &

# Sauvegarder le PID
echo $! > "$PIDFILE"
