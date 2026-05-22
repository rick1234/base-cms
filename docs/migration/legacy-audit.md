# Legacy migration audit

Legacy source root: `C:\htdocs\basiswms`

Legacy SQL dump: `C:\htdocs\basiswms\basiswms.sql`

## Observed legacy structure

- `application/Controllers`: public-facing controllers for content, catalog, cart, checkout, FAQ, forms, search, users, vacancies, locations, and other rendered website features.
- `application/Views`: `.phtml` templates for public pages and partials.
- `wms/`: backend/admin modules with folder-based `index.php`, `edit.php`, related edit screens, utility screens, AJAX endpoints, RBAC, uploads, content blocks, catalog, forms, redirects, users, and module management.
- `basiswms.sql`: database export with more than 100 legacy tables.
- `public/`: public assets, JavaScript, images, robots files, and sitemap output.

## Initial database map

- Content: `contentitems`, `contentcategorieen`, `contentitemblock`, `contentitemblockpart`, attachments, and images.
- Navigation and SEO: `url`, `urlverwijzing`, `redirect`, `menucategorieen`, `zoekwoorden`.
- Admin and access: `users`, `role`, `permissions`, `rbaclog`, `rechten`.
- Forms: `form`, `formblock`, `formfield`, `formmessage`, plus older `formulier` tables.
- Commerce: `catalogusartikel`, `cataloguscategorie`, `orders`, `orderregels`, stock, discounts, reviews, brands, and media.
- Site modules: `banner`, `slider`, `faq`, `vacatures`, `vestiging`, `evenement`, `downloads`, `mailingcontacten`, and radio modules.

## Migration decisions

- The new base does not copy legacy PHP files or mixed SQL/PHP/HTML patterns.
- The reusable foundation includes `cms_pages`, `cms_modules`, page-module assignments, redirect rules, translated `wms_*` module tables, and a WMS screen registry for legacy folder pages.
- Legacy Dutch table and field names are translated to English in Laravel.
- Legacy content tables should be migrated through explicit import commands after table-level mapping is reviewed.
- Website-specific legacy modules should move into `app/Site` or into documented reusable modules only when they are useful across future websites.
- Legacy HTML body fields must be sanitized or rendered as escaped text unless a trusted-content policy is added and tested.

## Next migration passes

1. Build an importer that reads the SQL dump into a temporary database.
2. Map `contentitems` and `contentcategorieen` into `cms_pages`.
3. Map `urlverwijzing` and `redirect` into `cms_redirect_rules`.
4. Decide which legacy modules are reusable base modules and which belong to a forked website.
5. Add fixture-backed tests for each importer before importing production content.

See `config/wms.php` and `docs/migration/wms-module-map.md` for the current full WMS module map.
