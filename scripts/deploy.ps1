# =============================================================================
# Production Deployment Script for Windows (PowerShell)
# =============================================================================
# Usage: .\scripts\deploy.ps1 [-NoMigrate] [-Force]
# =============================================================================

param(
    [switch]$NoMigrate,
    [switch]$Force
)

$ErrorActionPreference = "Stop"

Write-Host "============================================" -ForegroundColor Blue
Write-Host "  Alfa Beauty Production Deployment" -ForegroundColor Blue
Write-Host "============================================" -ForegroundColor Blue
Write-Host ""

# Check if we're in the app directory
if (-not (Test-Path "artisan")) {
    Write-Host "Error: artisan file not found. Please run from the app directory." -ForegroundColor Red
    exit 1
}

# Step 1: Put the application into maintenance mode
Write-Host "[1/8] Enabling maintenance mode..." -ForegroundColor Yellow
php artisan down --secret="alfa-beauty-deploy-bypass" 2>$null

# Step 2: Pull latest code (if in git repo)
if (Test-Path ".git") {
    Write-Host "[2/8] Pulling latest code..." -ForegroundColor Yellow
    git pull origin main
} else {
    Write-Host "[2/8] Skipping git pull (not a git repo)" -ForegroundColor Yellow
}

# Step 3: Install/update Composer dependencies
Write-Host "[3/8] Installing Composer dependencies..." -ForegroundColor Yellow
composer install --no-dev --optimize-autoloader --no-interaction

# Step 4: Run database migrations
if (-not $NoMigrate) {
    Write-Host "[4/8] Running database migrations..." -ForegroundColor Yellow
    if ($Force) {
        php artisan migrate --force
    } else {
        php artisan migrate
    }
} else {
    Write-Host "[4/8] Skipping database migrations (--NoMigrate flag)" -ForegroundColor Yellow
}

# Step 5: Clear all caches first
Write-Host "[5/8] Clearing old caches..." -ForegroundColor Yellow
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Step 6: Rebuild optimized caches
Write-Host "[6/8] Building optimized caches..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache 2>$null

# Step 7: Optimize Filament (if installed)
Write-Host "[7/8] Optimizing Filament assets..." -ForegroundColor Yellow
php artisan filament:cache-components 2>$null

# Step 8: Bring the application back up
Write-Host "[8/8] Disabling maintenance mode..." -ForegroundColor Yellow
php artisan up

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  Deployment Complete!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Bypass maintenance URL: your-domain.com?secret=alfa-beauty-deploy-bypass" -ForegroundColor Cyan
