#!/bin/bash

# Script de déploiement AgfaRythmo
# Usage: ./deploy.sh [--skip-frontend]

set -e  # Arrêter en cas d'erreur

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
BACKEND_DIR="$(pwd)/agfa-rythmo-backend"
FRONTEND_DIR="$(pwd)/agfa-rythmo-frontend"
SKIP_FRONTEND=false

# Parse arguments
if [[ "$1" == "--skip-frontend" ]]; then
    SKIP_FRONTEND=true
fi

echo -e "${GREEN}=== AgfaRythmo Deployment Script ===${NC}"
echo ""

# Fonction pour afficher les messages
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier que nous sommes dans le bon dossier
if [[ ! -d "$BACKEND_DIR" ]] || [[ ! -d "$FRONTEND_DIR" ]]; then
    log_error "Dossiers backend ou frontend introuvables"
    exit 1
fi

# 1. Git Pull
log_info "Pulling latest changes from git..."
git pull origin main || {
    log_error "Git pull failed"
    exit 1
}

# 2. Backend - Composer
log_info "Installing backend dependencies..."
cd "$BACKEND_DIR"
composer install --optimize-autoloader --no-dev --no-interaction || {
    log_error "Composer install failed"
    exit 1
}

# 3. Backend - Migrations
log_info "Running database migrations..."
php artisan migrate --force || {
    log_warning "Migration failed or no new migrations"
}

# 4. Frontend - Build (si pas skip)
if [[ "$SKIP_FRONTEND" == false ]]; then
    log_info "Building frontend..."
    cd "$FRONTEND_DIR"
    npm install || {
        log_error "npm install failed"
        exit 1
    }
    npm run build || {
        log_error "npm build failed"
        exit 1
    }
else
    log_warning "Skipping frontend build"
fi

# 5. Backend - Clear & Cache
log_info "Optimizing Laravel caches..."
cd "$BACKEND_DIR"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Permissions
log_info "Setting storage permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || {
    log_warning "Could not set ownership (may need sudo)"
}

# 7. Redémarrer les workers (détection automatique)
log_info "Restarting queue workers..."

# Signaler aux workers de se recharger
php artisan queue:restart

# Essayer de redémarrer via Supervisor
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart agfaRythmo-worker:* 2>/dev/null && {
        log_info "Workers restarted via Supervisor"
    } || {
        log_warning "Could not restart Supervisor workers"
    }
fi

# Essayer de redémarrer via Systemd
if command -v systemctl &> /dev/null; then
    sudo systemctl restart agfaRythmo-worker 2>/dev/null && {
        log_info "Workers restarted via Systemd"
    } || {
        log_warning "Could not restart Systemd workers"
    }
fi

# 8. Vérifications finales
log_info "Running final checks..."

# Vérifier FFmpeg
if ! command -v ffmpeg &> /dev/null; then
    log_error "FFmpeg is not installed!"
    exit 1
fi

# Vérifier les jobs en attente
PENDING_JOBS=$(sqlite3 database/database.sqlite "SELECT COUNT(*) FROM jobs;" 2>/dev/null || echo "0")
log_info "Pending jobs in queue: $PENDING_JOBS"

# Vérifier les jobs échoués
FAILED_JOBS=$(sqlite3 database/database.sqlite "SELECT COUNT(*) FROM failed_jobs;" 2>/dev/null || echo "0")
if [[ "$FAILED_JOBS" -gt 0 ]]; then
    log_warning "Failed jobs: $FAILED_JOBS (run 'php artisan queue:flush' to clear)"
fi

echo ""
log_info "Deployment completed successfully! 🚀"
echo ""
echo "Next steps:"
echo "  - Check logs: tail -f storage/logs/laravel.log"
echo "  - Monitor workers: sudo supervisorctl status"
echo "  - Test the application"
echo ""
