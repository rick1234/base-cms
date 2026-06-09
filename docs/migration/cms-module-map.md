# CMS module map

The legacy CMS folder structure is now represented as Laravel admin modules and screens under `config/cms.php`.
The primary admin routes use Laravel-style URLs without PHP filenames, for example `/admin/content`, `/admin/content/create`, `/admin/content/12/edit`, and `/admin/content/12/images`.
Legacy `/cms/*.php` routes and old query-string edit URLs remain as compatibility aliases only.
Most reachable module tables have a generic create/edit/delete admin workflow. Sensitive structural modules such as Laravel users remain guarded by explicit authorization and import rules.

## Folder-based admin routes

The old CMS was navigated through folders and PHP files. Laravel now resolves those screens through clean admin URLs without copying the old PHP implementation:

- `/admin/content`: content item overview
- `/admin/content/categorieen`: content category overview
- `/admin/catalogus`: catalog product overview
- `/admin/catalogus/categorieen`: catalog category overview
- `/admin/catalogus/merken`: catalog brand overview
- `/admin/{legacy-folder}/create`: create screen
- `/admin/{legacy-folder}/{record}/edit`: edit screen
- `/admin/{legacy-folder}/{page}`: configured legacy page variant

The route layer accepts legacy Dutch module folder names where needed for operator familiarity, while the Laravel internals use English module handles and English table names.
Database tables must not use the old `cms_` prefix; table names should follow Laravel conventions such as `content_items`, `content_categories`, `catalog_products`, and `forms`.
The `screens` registry is the source of truth for all folder-based CMS pages. A screen can expose an index page, an edit page, related edit pages such as `editAfbeeldingen.php`, and safe utility pages such as exports, imports, sync pages, and log views.

Generated admin UI must use canonical route names, not `legacy-*` route names. Legacy route names are reserved for inbound compatibility redirects or adapters. Current transitional `save` and `ajax/*` endpoints should be replaced by `store`, `update`, `destroy`, member routes, Form Requests, or Livewire components when each module is touched.

The schema and screen source for this map is the legacy `D:\htdocs\basiscms\cms` module folder tree plus any reviewed legacy SQL exports.

## Module groups

### Website

- Content: `contentitems`, `contentcategorieen`, content blocks, block parts, images, attachments, and category links became `content_*` tables.
- Banners: `banner`, `bannercategorie`, `bannerbannercategorie`, and `bannertranslation` became `banner_*` tables.
- Legacy sliders are intentionally excluded from the base CMS; reusable banner placements use the banner module instead.
- Forms: `form`, form builder tables, messages, submissions, and older `formulier*` tables became `form_*` tables.
- Events: `evenement`, categories, parts, images, and attachments became `event_*` tables.
- FAQ: `faq`, categories, images, attachments, and videos became `faq_*` tables.
- Downloads: `downloads` and download categories became `download_*` tables. Download files are stored on a private Laravel disk and are served only through generated hashed access-token URLs, with optional password protection and optional expiry.
- Locations: `vestiging`, categories, images, opening hours, and special opening hours became `location_*` tables.
- Vacancies: `vacatures`, categories, category links, and attachments became `vacancy_*` tables.
- Guestbook is intentionally excluded from the base CMS.

### Commerce

- Catalog: `catalogusartikel`, categories, product images, attachments, options, combinations, brands, translations, and videos became catalog tables.
- Webshop functionality such as orders, order lines, payment methods, action codes, promotions, reviews, delivery addresses, order exports, delivery settings, and shipping costs is intentionally deferred.

### Communication

- Mailing contacts and mailing-send tables are intentionally not part of this base.
- Newsletters are intentionally excluded from the base CMS. Sites that need campaigns or subscriptions should add a site-layer integration with explicit consent and privacy rules.
- Radio legacy modules are intentionally not part of this base.

### Platform

- Redirects and URLs: `redirect`, `urlverwijzing`, and `url` became `redirects`, `url_references`, and `urls`.
- Domains: `domein` became `domains`; legacy domain-role bridge data is intentionally excluded until a reachable role/domain workflow needs it.
- Users: the Laravel `users` table was extended with translated legacy profile fields; user categories and login tracking remain as reachable user-management support tables.
- Roles and permissions: `role` and `permissions` became Laravel-managed `roles`, `permissions`, `role_permissions`, and `role_user`. The rebuilt role editor stores permissions per module, module field, and module block, for example content attachments and event agenda blocks.
- Module registry: legacy `module` rows are represented only by `cms_modules` for reachable screens. Legacy module category bridge tables are removed.
- Search keywords: legacy `zoekwoorden` is intentionally excluded from the base.
- Short links and geolocation are intentionally excluded from the base CMS until they have reachable admin screens.

### Localization

- Languages and countries: legacy country and language tables became `languages`, `iso_languages`, `countries`, and `country_codes`.
- Translations: `translatekey`, `translatecontent`, and `translation` became `translation_keys`, `translation_values`, and `translations`.

## Import status

The Laravel schema and admin module registry are in place. Data import is intentionally separate: each importer should read from the SQL dump or a temporary legacy database, sanitize legacy HTML, translate fields, and write into English Laravel-named tables with fixture-backed tests.
