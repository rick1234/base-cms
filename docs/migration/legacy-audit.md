# Legacy migration audit

Legacy source root: `D:\htdocs\basiscms`

Legacy CMS modules: `D:\htdocs\basiscms\cms`

## Observed legacy structure

- `application/Controllers`: public-facing controllers for content, catalog, cart, checkout, FAQ, forms, search, users, vacancies, locations, and other rendered website features.
- `application/Views`: `.phtml` templates for public pages and partials.
- `cms/`: backend/admin modules with folder-based `index.php`, `edit.php`, related edit screens, utility screens, AJAX endpoints, role permissions, uploads, content blocks, catalog, forms, redirects, users, and module management.
- `basiscms.sql`: database export with more than 100 legacy tables.
- `public/`: public assets, JavaScript, images, robots files, and sitemap output.

## Initial database map

- Content: `contentitems`, `contentcategorieen`, `contentitemblock`, `contentitemblockpart`, attachments, and images.
- Navigation and SEO: `url`, `urlverwijzing`, `redirect`, and `menucategorieen`.
- Admin and access: `users`, `role`, `permissions`, `rechten`.
- Forms: `form`, `formblock`, `formfield`, `formmessage`, plus older `formulier` tables.
- Commerce: `catalogusartikel`, `cataloguscategorie`, `orders`, `orderregels`, stock, discounts, reviews, brands, and media.
- Site modules: `banner`, `slider`, `faq`, `vacatures`, `vestiging`, `evenement`, and `downloads`.

## Migration decisions

- The new base does not copy legacy PHP files or mixed SQL/PHP/HTML patterns.
- The reusable foundation includes `cms_pages`, `cms_modules`, page-module assignments, redirect rules, translated Laravel-named module tables, and a screen registry for legacy folder pages.
- Legacy Dutch table and field names are translated to English in Laravel.
- Legacy content tables should be migrated through explicit import commands after table-level mapping is reviewed.
- Website-specific legacy modules should move into `app/Site` or into documented reusable modules only when they are useful across future websites.
- The legacy search keyword module (`zoekwoorden`) is intentionally excluded from the Laravel base.
- Legacy mailing contact and mailing-send modules are intentionally excluded from the Laravel base. Sites that need newsletter or campaign integrations should add a dedicated site-layer integration with explicit consent and privacy rules.
- Legacy HTML body fields must be sanitized or rendered as escaped text unless a trusted-content policy is added and tested.

## Structural cleanup status

- Admin create URLs now point at explicit `create()` controller actions. Some actions still share the current edit Blade view internally while the forms are split further.
- Generated admin UI should use canonical route names and should not link to `legacy-*`, `.php`, or query-string edit aliases.
- Scans for direct PHP superglobals, inline Blade styles, and raw SQL in controllers did not find new violations in the rebuilt app structure.
- Remaining migration debt is intentionally visible: transitional `save` routes, compatibility `ajax/*` names, and the temporary `App\Models\Cms` namespace should be retired in staged passes once each module has complete `store`, `update`, `destroy`, Livewire, or member-route coverage.

## Next migration passes

1. Replace module edit forms that still submit to transitional `save` routes with `store` and `update` route targets.
2. Replace compatibility `ajax/*` endpoints with named member routes or Livewire components.
3. Move rebuilt model namespaces away from `App\Models\Cms` once the active modules are stable.
4. Build an importer that reads the SQL dump into a temporary database.
5. Map `contentitems`, `contentcategorieen`, `urlverwijzing`, and `redirect` into their Laravel tables.
6. Decide which remaining legacy modules are reusable base modules and which belong to a forked website.
7. Add fixture-backed tests for each importer before importing production content.

See `config/cms.php` and `docs/migration/cms-module-map.md` for the current full CMS module map.
