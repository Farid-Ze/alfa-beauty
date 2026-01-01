#!/bin/bash
# =============================================================================
# Production Deployment Script for Alfa Beauty B2B E-commerce
# =============================================================================
# Usage: ./scripts/deploy.sh [--no-migrate] [--force]
#
# Options:
#   --no-migrate    Skip database migrations
#   --force         Force operations without confirmation
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse arguments
SKIP_MIGRATE=false
FORCE=false

for arg in "$@"; do
    case $arg in
        --no-migrate)
            SKIP_MIGRATE=true
            shift
            ;;
        --force)
            FORCE=true
            shift
            ;;
    esac
done

echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}  Alfa Beauty Production Deployment${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""

# Check if we're in the app directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Please run from the app directory.${NC}"
    exit 1
fi

# Step 1: Put the application into maintenance mode
echo -e "${YELLOW}[1/8] Enabling maintenance mode...${NC}"
php artisan down --secret="alfa-beauty-deploy-bypass" || true

# Step 2: Pull latest code (if in git repo)
if [ -d ".git" ]; then
    echo -e "${YELLOW}[2/8] Pulling latest code...${NC}"
    git pull origin main
else
    echo -e "${YELLOW}[2/8] Skipping git pull (not a git repo)${NC}"
fi

# Step 3: Install/update Composer dependencies
echo -e "${YELLOW}[3/8] Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# Step 4: Run database migrations
if [ "$SKIP_MIGRATE" = false ]; then
    echo -e "${YELLOW}[4/8] Running database migrations...${NC}"
    if [ "$FORCE" = true ]; then
        php artisan migrate --force
    else
        php artisan migrate
    fi
else
    echo -e "${YELLOW}[4/8] Skipping database migrations (--no-migrate flag)${NC}"
fi

# Step 5: Clear all caches first
echo -e "${YELLOW}[5/8] Clearing old caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Step 6: Rebuild optimized caches
echo -e "${YELLOW}[6/8] Building optimized caches...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache 2>/dev/null || true

# Step 7: Optimize Filament (if installed)
echo -e "${YELLOW}[7/8] Optimizing Filament assets...${NC}"
php artisan filament:cache-components 2>/dev/null || true

# Step 8: Bring the application back up
echo -e "${YELLOW}[8/8] Disabling maintenance mode...${NC}"
php artisan up

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Deployment Complete!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "Bypass maintenance URL: ${BLUE}your-domain.com?secret=alfa-beauty-deploy-bypass${NC}"
echo ""

# Show current status
echo -e "${BLUE}Application Status:${NC}"
php artisan about --only=environment 2>/dev/null || echo "  Environment: $(php artisan env)"
