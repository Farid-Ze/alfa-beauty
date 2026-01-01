# Production Deployment Checklist
## Alfa Beauty B2B E-Commerce

Last Updated: January 2026

---

## ⚡ Pre-Deployment Checklist

### 🔐 Security (CRITICAL)

- [ ] **Environment Variables**
  - [ ] `APP_ENV=production`
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_KEY` is generated (`php artisan key:generate`)
  - [ ] All `.env.*` files are in `.gitignore` (except `.env.example`)

- [ ] **Session Security**
  - [ ] `SESSION_ENCRYPT=true`
  - [ ] `SESSION_SECURE_COOKIE=true`
  - [ ] `SESSION_SAME_SITE=strict`
  - [ ] `SESSION_LIFETIME=60` (or appropriate value)

- [ ] **SSL/HTTPS**
  - [ ] HTTPS enabled on all domains
  - [ ] HTTP redirects to HTTPS
  - [ ] HSTS headers configured (automatic via SecurityHeaders middleware)

- [ ] **API Security**
  - [ ] `SANCTUM_STATEFUL_DOMAINS` set to production domain
  - [ ] `SANCTUM_TOKEN_EXPIRATION=60` (1 hour)
  - [ ] `CORS_ALLOWED_ORIGINS` set to production domains

### 🗄️ Database

- [ ] **Connection**
  - [ ] `DB_CONNECTION=pgsql`
  - [ ] PostgreSQL connection configured
  - [ ] Connection pooling enabled (if using Neon/Supabase)

- [ ] **Migrations**
  - [ ] Run `php artisan migrate --force`
  - [ ] Verify all indexes exist
  - [ ] Backup created before migration

- [ ] **Seeders**
  - [ ] Run `php artisan db:seed` if needed
  - [ ] Verify loyalty tiers exist
  - [ ] Verify admin user created

### 📧 Email

- [ ] **SMTP/SES Configuration**
  - [ ] `MAIL_MAILER` configured (smtp/ses)
  - [ ] `MAIL_FROM_ADDRESS=noreply@alfabeauty.id`
  - [ ] `MAIL_FROM_NAME="Alfa Beauty"`
  - [ ] Test email sending works

### 📱 WhatsApp

- [ ] **Business Number**
  - [ ] `WHATSAPP_BUSINESS_NUMBER` set to REAL number
  - [ ] WhatsApp Business API configured
  - [ ] Test checkout flow works

### 📊 Monitoring

- [ ] **Logging**
  - [ ] `LOG_CHANNEL=production` (or `daily`)
  - [ ] `LOG_LEVEL=warning`
  - [ ] `LOG_SLACK_WEBHOOK_URL` configured for alerts
  
- [ ] **Health Checks**
  - [ ] `/api/v1/health` endpoint accessible
  - [ ] `/api/v1/health/detailed` returns healthy status
  - [ ] Load balancer configured to use health endpoint

### 🚀 Performance

- [ ] **Caching**
  - [ ] `php artisan config:cache`
  - [ ] `php artisan route:cache`
  - [ ] `php artisan view:cache`
  - [ ] `php artisan event:cache`
  - [ ] Redis/Memcached configured for production

- [ ] **Assets**
  - [ ] `npm run build` completed
  - [ ] All assets minified and cache-busted
  - [ ] CDN configured (if applicable)

- [ ] **Database**
  - [ ] Query cache enabled
  - [ ] Slow query logging configured

### 🌐 Domain & DNS

- [ ] **DNS Configuration**
  - [ ] A/CNAME records pointing to server
  - [ ] SSL certificate valid and auto-renewing
  - [ ] `APP_URL` set correctly

---

## 📋 Deployment Steps

### 1. Pre-Deployment

```bash
# Local testing
php artisan test
npm run build

# Create backup
pg_dump -U postgres -d alfabeauty > backup_$(date +%Y%m%d).sql
```

### 2. Deploy Code

```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# Build assets
npm run build
```

### 3. Post-Deployment

```bash
# Clear and cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Restart queue workers
php artisan queue:restart
```

### 4. Verification

```bash
# Health check
curl https://alfabeauty.id/api/v1/health

# Detailed health check
curl https://alfabeauty.id/api/v1/health/detailed

# Test critical flows
# - Homepage loads
# - Product listing works
# - Add to cart works
# - WhatsApp checkout works
```

---

## 🔥 Rollback Plan

### Quick Rollback

```bash
# Revert to previous commit
git revert HEAD --no-commit
git commit -m "Rollback: [reason]"

# Or reset to specific commit
git reset --hard <commit-hash>
```

### Database Rollback

```bash
# Rollback last migration
php artisan migrate:rollback

# Restore from backup
psql -U postgres -d alfabeauty < backup_YYYYMMDD.sql
```

---

## 📞 Emergency Contacts

| Role | Contact |
|------|---------|
| DevOps Lead | [TBD] |
| Backend Lead | [TBD] |
| Database Admin | [TBD] |
| On-Call Support | [TBD] |

---

## ✅ Final Sign-Off

| Item | Verified By | Date |
|------|-------------|------|
| Security checklist complete | | |
| Performance testing passed | | |
| Staging environment tested | | |
| Rollback plan documented | | |
| Team notified | | |

---

*This checklist should be reviewed and updated before each production deployment.*
