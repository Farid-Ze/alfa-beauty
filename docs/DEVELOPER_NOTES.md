# Developer Notes - Alfa Beauty

> **Last Updated:** 2026-01-04  
> **Maintained by:** Development Team

---

## 📁 Project Structure

```
app/
├── public/css/
│   ├── main.css          # Source CSS (untuk development)
│   └── main.min.css      # Minified CSS (untuk production)
├── lang/
│   ├── id/               # Indonesian translations
│   └── en/               # English translations
├── resources/views/
│   ├── components/layouts/app.blade.php  # Main layout
│   └── livewire/         # Livewire components
└── ...
```

---

## 🎨 CSS Guidelines

### File Loading Strategy
```blade
<!-- app.blade.php -->
<link rel="stylesheet" href="{{ asset('css/' . (app()->environment('production') ? 'main.min.css' : 'main.css')) }}?v=2.1">
```

- **Development:** Loads `main.css` (readable, easier debugging)
- **Production:** Loads `main.min.css` (minified, 39% smaller)

### Regenerate Minified CSS
Setelah edit `main.css`, jalankan:
```bash
cmd /c "npx -y clean-css-cli -o public/css/main.min.css public/css/main.css"
```

### Breakpoints Standard
| Breakpoint | Target |
|------------|--------|
| 1200px | Large tablet / small desktop |
| 1024px | Tablet landscape |
| 768px | Tablet portrait |
| 480px | Mobile landscape |
| 360px | Mobile portrait (small) |

### CSS Variables (`:root`)
```css
--black: #0A0A0A
--white: #FFFFFF
--gray-100: #F7F7F7
--gray-200: #E5E4E2 (Platinum)
--gray-400: #9CA3AF
--gray-600: #4B5563
--gold: #C9A962 (Loyalty tier)
--green: #10B981 (Success)
--red: #dc2626 (Error)
```

---

## 🌐 Internationalization (i18n)

### Supported Languages
- **Indonesian (ID)** - Default
- **English (EN)** - Fallback

### Language Files
```
lang/
├── id/
│   ├── general.php   # Buttons, status, labels
│   ├── nav.php       # Navigation, footer
│   ├── products.php  # Catalog, filters
│   ├── cart.php      # Cart drawer
│   ├── checkout.php  # Checkout flow
│   ├── auth.php      # Login, register
│   ├── orders.php    # Order history
│   ├── home.php      # Homepage sections
│   └── brand.php     # Brand detail page
└── en/
    └── (same structure)
```

### Switch Language
```
GET /lang/{locale}
```
Example: `/lang/en` → switches to English

### Usage in Blade
```blade
{{ __('nav.products') }}
{{ __('checkout.place_order') }}
{{ __('general.loading') }}
```

### Middleware
File: `app/Http/Middleware/SetLocale.php`  
Registered in: `bootstrap/app.php`

---

## 🔧 Common Tasks

### Clear All Cache
```bash
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

### Run Development Server
```bash
php artisan serve
npm run dev
```

### Run Production Build
```bash
npm run build
cmd /c "npx -y clean-css-cli -o public/css/main.min.css public/css/main.css"
```

### Supabase Schema Ops (Secure)

Use the hardened script to push Supabase migrations.

```powershell
# Recommended: push migrations without linking (no password required if already linked)
powershell -ExecutionPolicy Bypass -File .\scripts\supabase_link_and_push.ps1 -ProjectRef <your_project_ref> -NonInteractive

# If you need to link a project on a new machine, run with -Link.
# You will be prompted securely for the DB password (no echo).
powershell -ExecutionPolicy Bypass -File .\scripts\supabase_link_and_push.ps1 -ProjectRef <your_project_ref> -Link -NonInteractive

# Optional: use POSTGRES_PASSWORD from .env.local (less secure; avoid in shared terminals)
powershell -ExecutionPolicy Bypass -File .\scripts\supabase_link_and_push.ps1 -ProjectRef <your_project_ref> -Link -UseEnvPassword -NonInteractive
```

Security note: if secrets are ever printed in logs or terminals, rotate Supabase DB credentials promptly and update local environment files.

---

## ⚠️ Important Notes

1. **Jangan edit `main.min.css` langsung** - Selalu edit `main.css` lalu regenerate
2. **Version bump** - Update `?v=X.X` di `app.blade.php` setelah CSS changes
3. **Language fallback** - Jika key tidak ada di ID, akan fallback ke EN
4. **Cookie persistence** - Language preference disimpan 1 tahun di cookie
5. **PostgreSQL Boolean** - Gunakan `whereRaw('column = true')` untuk boolean comparisons, bukan `where('column', true)`. PostgreSQL menolak `= 1` untuk boolean.

---

## 📋 Optimization History

| Date | Change | Impact |
|------|--------|--------|
| 2025-12-30 | Removed 5 duplicate CSS selectors | -20 lines |
| 2025-12-30 | Fixed `.nav.scrolled .loyalty-badge` conflict | Bug fix |
| 2025-12-30 | Created minified CSS | 140KB → 85KB (39%) |
| 2025-12-30 | Added conditional CSS loading | Auto prod/dev |
