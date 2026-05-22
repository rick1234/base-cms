# AGENTS.md

This repository is a strict modern Laravel base project rebuilt from a legacy PHP website/CMS system.

All AI agents, Codex sessions, automation tools, and human contributors must follow these rules.

## Project purpose

This project is a reusable Laravel base for custom websites.

It must support:

- traditional rendered websites using Laravel, Blade, and vanilla Sass/CSS
- a backend/admin area
- headless API usage
- website-specific modules and extensions
- future updates from the shared base after a website has been forked

This is not a WordPress replacement by copying WordPress patterns.

This is a custom, clean, testable Laravel base for building bespoke websites.

SEO is a high requirement for the frontend part.

## Absolute frontend rules

The following are strictly forbidden:

- inline CSS
- `style=""` attributes in Blade, PHP, HTML, Vue, React, or generated templates
- Bootstrap
- Tailwind CSS
- Tailwind-like utility-first class systems
- external CSS frameworks
- CDN-loaded CSS frameworks
- framework-generated layout classes
- random one-off CSS in views
- styling inside JavaScript unless absolutely required for runtime behavior and approved by tests

Allowed styling:

- vanilla CSS
- Sass/SCSS
- CSS custom properties
- semantic class names
- project-owned design tokens
- project-owned layout utilities, only when documented and not Tailwind-like

Preferred CSS structure:

```text
resources/scss/
  settings/
  tools/
  generic/
  elements/
  objects/
  components/
  utilities/
```

## Absolute backend rules

The following are strictly forbidden:

- mixed SQL/PHP/HTML/CSS legacy patterns
- raw SQL in controllers
- direct `$_GET`, `$_POST`, `$_REQUEST`, `$_FILES`, or `$_SERVER` usage
- unsafe `echo`
- bypassing Laravel validation
- bypassing authorization
- business logic inside Blade views
- business logic-heavy controllers
- hidden global state
- untested helpers
- silent exception swallowing
- suppressing errors with `@`
- disabling static analysis rules just to hide problems
- pretending work is complete when tests or checks fail

## Laravel architecture rules

- Use the latest stable Laravel version supported by the current environment.
- Use the latest stable PHP version supported by that Laravel version.
- Prefer Laravel conventions unless there is a documented project reason not to.
- Keep controllers thin.
- Put validation in Form Request classes.
- Put authorization in policies, gates, middleware, or dedicated authorization services.
- Put serialization in API resources.
- Put reusable domain behavior in services, actions, query objects, value objects, or models where appropriate.
- Never place business logic in Blade views.
- Never place persistence logic directly in Blade views.
- Never place raw SQL in controllers.
- Use Eloquent, query builders, migrations, seeders, factories, and repositories only where they improve clarity.
- Use explicit route names.
- Keep rendered website routes, backend/admin routes, and API routes clearly separated.
- Use versioned API routes.
- API behavior must not depend on Blade rendering.

## Frontend architecture rules

- Use Blade, Blade components, semantic HTML, progressive enhancement, and compiled Sass/CSS.
- Use JavaScript sparingly and only for behavior that requires it.
- Do not style elements from JavaScript unless runtime behavior makes it unavoidable.
- Do not introduce a frontend framework unless a future decision document explicitly allows it.
- Do not introduce Bootstrap, Tailwind, utility-first CSS, or an external CSS framework.
- All frontend styling must live in project-owned CSS or SCSS files.
- Use design tokens through CSS custom properties.
- Use semantic class names based on component purpose, not visual shortcuts.
- Keep accessibility, responsive behavior, and SEO in mind for every public template.

## SEO rules

- Public pages must use semantic HTML landmarks.
- Public pages must provide useful titles and meta descriptions.
- Public pages must support canonical URLs where appropriate.
- Public pages must be ready for Open Graph and structured metadata.
- Navigation, headings, breadcrumbs, slugs, and content hierarchy must be built with SEO in mind.
- Backend/admin pages must not leak private content to public indexing.

## Rendered mode requirements

Rendered website functionality must use:

- Laravel routes
- controllers
- Form Requests where input is accepted
- Blade views
- Blade components
- layouts
- compiled Sass/CSS
- named routes
- policies or gates where authorization is needed
- feature tests for important behavior

## Headless mode requirements

Headless API functionality must use:

- versioned API routes
- JSON responses
- API resources
- Form Requests
- policies or gates where authorization is needed
- API-focused feature tests
- no dependency on Blade views

## Backend/admin requirements

The backend/admin area must:

- be clearly separated from the public frontend
- use its own route group, layout, navigation, controllers, requests, and policies
- require authentication before access
- use authorization for privileged actions
- avoid leaking admin UI concerns into public frontend code
- be reusable across forked websites

## Website-specific extension rules

Website-specific modules, templates, fields, and behavior must be isolated from the shared base as much as possible.

Preferred extension locations:

- `app/Site/`
- `resources/views/site/`
- `resources/scss/site/`
- `routes/site.php`
- `database/migrations/site/` when project policy allows it

Shared base code must avoid hard-coding one website's content model unless it is clearly part of the reusable base.

When adding custom website behavior, document whether it belongs to the reusable base or to the forked site layer.

## Future-update rules

The base must remain updateable after a website has been forked.

- Keep shared base code separated from website-specific code.
- Avoid scattering one-off project changes through core base files.
- Prefer documented extension points over direct edits to reusable internals.
- Record architectural decisions when they affect update strategy.
- Do not remove compatibility paths without a migration note.

## Legacy migration rules

The legacy PHP project is a source of behavior, database structure, content patterns, and visual intent.

It is not a source of code to blindly copy.

- Study legacy behavior before rebuilding it.
- Do not copy unsafe legacy PHP patterns.
- Do not reproduce mixed PHP/SQL/HTML/CSS files.
- Convert legacy database structure into Laravel migrations, models, factories, seeders, and import tooling.
- Preserve meaningful behavior and purpose while modernizing implementation.
- Document important migration assumptions.

## Security rules

- Validate all external input.
- Authorize all protected actions.
- Escape output by default.
- Do not use unsafe raw output unless the content is trusted and intentionally sanitized.
- Protect backend/admin routes with authentication and authorization.
- Use CSRF protection for rendered form submissions.
- Use rate limiting where appropriate.
- Do not expose secrets in code, tests, docs, logs, or frontend assets.
- Do not silently ignore exceptions.

## Testing and quality rules

- Do not claim work is complete when tests or checks fail.
- Add or update tests for important behavior.
- Keep rendered website tests and API tests independent where practical.
- Prefer feature tests for route-level behavior.
- Prefer unit tests for isolated domain behavior.
- Run relevant tests and checks before finalizing work.
- If a check cannot be run, state why.

## Documentation rules

- Document project-specific conventions that future agents must follow.
- Keep documentation close to the code it explains.
- Prefer concise architecture notes over undocumented implicit decisions.
- Update documentation when adding extension points, modules, migration tooling, or non-obvious conventions.

