#!/bin/bash

# Script de vérification pré-déploiement AgfaRythmo
# Usage: ./check-deployment.sh

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

ERRORS=0
WARNINGS=0

echo -e "${BLUE}=== AgfaRythmo Deployment Checker ===${NC}"
echo ""

check_ok() {
    echo -e "${GREEN}✓${NC} $1"
}

check_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
    ((WARNINGS++))
}

check_error() {
    echo -e "${RED}✗${NC} $1"
    ((ERRORS++))
}

# 1. Vérifier PHP
echo "Checking PHP..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    if (( $(echo "$PHP_VERSION >= 8.2" | bc -l) )); then
        check_ok "PHP version: $PHP_VERSION"
    else
        check_error "PHP version $PHP_VERSION is too old (need 8.2+)"
    fi
else
    check_error "PHP is not installed"
fi

# 2. Vérifier les extensions PHP
echo ""
echo "Checking PHP extensions..."
REQUIRED_EXTENSIONS=("pdo" "pdo_sqlite" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        check_ok "Extension $ext"
    else
        check_error "Extension $ext is missing"
    fi
done

# 3. Vérifier FFmpeg
echo ""
echo "Checking FFmpeg..."
if command -v ffmpeg &> /dev/null; then
    FFMPEG_VERSION=$(ffmpeg -version | head -n 1 | cut -d " " -f 3)
    check_ok "FFmpeg version: $FFMPEG_VERSION"
else
    check_error "FFmpeg is not installed (required for AI scene detection)"
fi

# 4. Vérifier Composer
echo ""
echo "Checking Composer..."
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | cut -d " " -f 3)
    check_ok "Composer version: $COMPOSER_VERSION"
else
    check_error "Composer is not installed"
fi

# 5. Vérifier Node.js
echo ""
echo "Checking Node.js..."
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v)
    check_ok "Node.js version: $NODE_VERSION"
else
    check_warning "Node.js is not installed (needed for frontend build)"
fi

# 6. Vérifier NPM
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm -v)
    check_ok "NPM version: $NPM_VERSION"
fi

# 7. Vérifier les dossiers du projet
echo ""
echo "Checking project structure..."
if [[ -d "agfa-rythmo-backend" ]]; then
    check_ok "Backend directory exists"
else
    check_error "Backend directory not found"
fi

if [[ -d "agfa-rythmo-frontend" ]]; then
    check_ok "Frontend directory exists"
else
    check_error "Frontend directory not found"
fi

# 8. Vérifier le fichier .env backend
echo ""
echo "Checking backend configuration..."
if [[ -f "agfa-rythmo-backend/.env" ]]; then
    check_ok ".env file exists"
    
    # Vérifier les variables importantes
    if grep -q "^APP_KEY=" agfa-rythmo-backend/.env && ! grep -q "^APP_KEY=$" agfa-rythmo-backend/.env; then
        check_ok "APP_KEY is set"
    else
        check_error "APP_KEY is not set (run: php artisan key:generate)"
    fi
    
    if grep -q "^QUEUE_CONNECTION=database" agfa-rythmo-backend/.env; then
        check_ok "Queue is configured (database)"
    else
        check_warning "Queue may not be configured correctly"
    fi
else
    check_error ".env file not found (copy .env.example)"
fi

# 9. Vérifier les permissions
echo ""
echo "Checking permissions..."
if [[ -w "agfa-rythmo-backend/storage" ]]; then
    check_ok "Storage directory is writable"
else
    check_error "Storage directory is not writable (run: chmod -R 775 storage)"
fi

if [[ -w "agfa-rythmo-backend/bootstrap/cache" ]]; then
    check_ok "Bootstrap cache is writable"
else
    check_warning "Bootstrap cache directory is not writable"
fi

# 10. Vérifier la base de données
echo ""
echo "Checking database..."
if [[ -f "agfa-rythmo-backend/database/database.sqlite" ]]; then
    check_ok "SQLite database exists"
    
    # Vérifier les tables
    TABLES_COUNT=$(sqlite3 agfa-rythmo-backend/database/database.sqlite "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null || echo "0")
    if [[ "$TABLES_COUNT" -gt 0 ]]; then
        check_ok "Database has $TABLES_COUNT tables"
    else
        check_warning "Database seems empty (run: php artisan migrate)"
    fi
else
    check_warning "SQLite database not found (will be created on first migration)"
fi

# 11. Vérifier les dépendances backend
echo ""
echo "Checking backend dependencies..."
if [[ -d "agfa-rythmo-backend/vendor" ]]; then
    check_ok "Composer dependencies installed"
else
    check_warning "Composer dependencies not installed (run: composer install)"
fi

# 12. Vérifier les dépendances frontend
echo ""
echo "Checking frontend dependencies..."
if [[ -d "agfa-rythmo-frontend/node_modules" ]]; then
    check_ok "NPM dependencies installed"
else
    check_warning "NPM dependencies not installed (run: npm install)"
fi

# 13. Vérifier le build frontend
if [[ -d "agfa-rythmo-frontend/dist" ]]; then
    check_ok "Frontend build exists"
else
    check_warning "Frontend not built (run: npm run build)"
fi

# 14. Vérifier les workers (production uniquement)
echo ""
echo "Checking queue workers..."
if command -v supervisorctl &> /dev/null; then
    if sudo supervisorctl status agfaRythmo-worker 2>/dev/null | grep -q "RUNNING"; then
        check_ok "Supervisor workers are running"
    else
        check_warning "Supervisor workers not running or not configured"
    fi
else
    check_warning "Supervisor not installed (optional for production)"
fi

if command -v systemctl &> /dev/null; then
    if sudo systemctl is-active --quiet agfaRythmo-worker 2>/dev/null; then
        check_ok "Systemd worker is running"
    else
        check_warning "Systemd worker not running or not configured"
    fi
fi

# 15. Vérifier l'espace disque
echo ""
echo "Checking disk space..."
AVAILABLE_SPACE=$(df -h . | tail -1 | awk '{print $4}')
check_ok "Available disk space: $AVAILABLE_SPACE"

# Résumé
echo ""
echo "============================================"
if [[ $ERRORS -eq 0 ]] && [[ $WARNINGS -eq 0 ]]; then
    echo -e "${GREEN}✓ All checks passed! Ready for deployment.${NC}"
    exit 0
elif [[ $ERRORS -eq 0 ]]; then
    echo -e "${YELLOW}⚠ $WARNINGS warning(s) found. Review and proceed with caution.${NC}"
    exit 0
else
    echo -e "${RED}✗ $ERRORS error(s) and $WARNINGS warning(s) found. Fix errors before deployment.${NC}"
    exit 1
fi
