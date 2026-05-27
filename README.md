# Base CMS

Modern Laravel base rebuilt from the legacy `basiscms` PHP/CMS project.

This repository is intentionally strict:

- Laravel 13 with PHP 8.5 requirements
- rendered Blade frontend with project-owned Sass
- authenticated backend/admin area
- rebuilt admin module registry based on the legacy `cms/` folders
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
- Admin module registry: `config/cms.php`
- Rebuilt admin routes: `/admin/modules`, `/admin/content`, `/admin/content/categorieen`, `/admin/catalogus/actiecodes/edit`, `/admin/orders/afleverdata`
- Legacy compatibility routes: `/cms/index.php`, `/cms/content/index.php`, `/cms/content/categorieen/index.php`
- Headless API routes: `routes/api.php`
- Website-specific routes: `routes/site.php`
- Base Sass: `resources/scss`
- Site-specific Sass: `resources/scss/site`
- Site-specific PHP: `app/Site`

## Legacy source

The legacy source is expected at `D:\htdocs\basiscms`, with CMS modules under `D:\htdocs\basiscms\cms`.

See [docs/migration/legacy-audit.md](docs/migration/legacy-audit.md) for the migration map.

The rebuilt admin module/table mapping lives in [docs/migration/cms-module-map.md](docs/migration/cms-module-map.md).
