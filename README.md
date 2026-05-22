# Base CMS

Modern Laravel base rebuilt from the legacy `basiswms` PHP/CMS project.

This repository is intentionally strict:

- Laravel 13 with PHP 8.3+ requirements
- rendered Blade frontend with project-owned Sass
- authenticated backend/admin area
- translated WMS module registry based on the legacy `wms/` folders and `basiswms.sql`
- versioned JSON API
- isolated site extension layer
- no Bootstrap, Tailwind, inline CSS, or copied legacy PHP patterns

Read [AGENTS.md](AGENTS.md) before changing the project.

## Setup

Use Node.js 20.19 or newer for Vite assets.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

The seeded admin account is `admin@example.com` with password `password`.

## Main areas

- Public rendered routes: `routes/web.php`
- Backend/admin routes: `routes/admin.php`
- WMS module registry: `config/wms.php`
- Legacy-style WMS routes: `/wms/index.php`, `/wms/content/index.php`, `/wms/content/categorieen/index.php`, `/wms/catalogus/actiecodes/edit.php`, `/wms/orders/afleverdata/index.php`
- Headless API routes: `routes/api.php`
- Website-specific routes: `routes/site.php`
- Base Sass: `resources/scss`
- Site-specific Sass: `resources/scss/site`
- Site-specific PHP: `app/Site`

## Legacy source

The legacy source is expected at `C:\htdocs\basiswms`, with the SQL dump at `C:\htdocs\basiswms\basiswms.sql`.

See [docs/migration/legacy-audit.md](docs/migration/legacy-audit.md) for the migration map.

The translated WMS module/table mapping lives in [docs/migration/wms-module-map.md](docs/migration/wms-module-map.md).
