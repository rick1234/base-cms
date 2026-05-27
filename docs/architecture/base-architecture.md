# Base architecture

This project is a reusable Laravel base for bespoke websites. It keeps shared base code separate from website-specific extensions so forked projects can continue receiving base updates.

## Route layers

- `routes/web.php`: public rendered website routes.
- `routes/admin.php`: authenticated backend/admin routes, including rebuilt legacy module screens such as `/admin/content`, `/admin/content/create`, `/admin/content/{id}/edit`, and `/admin/content/{id}/images`.
- `routes/cms.php`: authenticated legacy CMS compatibility routes such as `/cms/content/categorieen/index.php`.
- `routes/api.php`: versioned headless API routes.
- `routes/site.php`: website-specific rendered routes added by forks.

## Application layers

- Controllers stay thin and delegate validation, authorization, persistence, and serialization.
- Form Requests validate input.
- Policies and gates protect admin behavior.
- API Resources serialize JSON output.
- Actions hold reusable write workflows.
- Blade views render data and do not contain business logic.
- `config/cms.php` owns the rebuilt admin module and screen registry. Add or change legacy folder pages there before touching routes.

## Legacy compatibility boundary

Canonical admin URLs must use Laravel route conventions: collection indexes, `create` screens, `/{id}/edit` edit screens, and named member routes such as `/{id}/images`. Legacy PHP filenames, query-string IDs, `ajax/*` endpoint names, and `legacy-*` route names may remain only as compatibility aliases while migration work is in progress.

New admin modules must expose explicit controller actions for their canonical routes. A create route must call `create()`, not `edit()`, even when a transitional controller shares the existing edit Blade view internally.

## Styling

Styling lives in project-owned CSS/SCSS. The public frontend uses `resources/scss`; the rebuilt admin also carries reviewed legacy CMS visual assets under `public/admin/cms` so the admin screens can preserve the original layout while Laravel owns the implementation. Website-specific Sass belongs in `resources/scss/site`.

Bootstrap, Tailwind, utility-first CSS, external CSS frameworks, inline CSS, and style attributes are forbidden.

## Extension strategy

Forked websites should isolate project-specific modules in `app/Site`, `resources/views/site`, `resources/scss/site`, and `routes/site.php`. Shared base code should only change when the behavior is reusable across websites.
