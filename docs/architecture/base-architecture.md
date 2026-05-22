# Base architecture

This project is a reusable Laravel base for bespoke websites. It keeps shared base code separate from website-specific extensions so forked projects can continue receiving base updates.

## Route layers

- `routes/web.php`: public rendered website routes.
- `routes/admin.php`: authenticated backend/admin routes.
- `routes/wms.php`: authenticated legacy WMS folder routes such as `/wms/content/categorieen/index.php`.
- `routes/api.php`: versioned headless API routes.
- `routes/site.php`: website-specific rendered routes added by forks.

## Application layers

- Controllers stay thin and delegate validation, authorization, persistence, and serialization.
- Form Requests validate input.
- Policies and gates protect admin behavior.
- API Resources serialize JSON output.
- Actions hold reusable write workflows.
- Blade views render data and do not contain business logic.
- `config/wms.php` owns the translated WMS module and screen registry. Add or change WMS folder pages there before touching routes.

## Styling

All styling lives in `resources/scss`. The base uses design tokens, semantic classes, and component/object layers. Website-specific Sass belongs in `resources/scss/site`.

Bootstrap, Tailwind, utility-first CSS, external CSS frameworks, inline CSS, and style attributes are forbidden.

## Extension strategy

Forked websites should isolate project-specific modules in `app/Site`, `resources/views/site`, `resources/scss/site`, and `routes/site.php`. Shared base code should only change when the behavior is reusable across websites.
