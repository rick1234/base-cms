# CMS module map

The legacy CMS folder structure is now represented as Laravel admin modules and screens under `config/cms.php`.
The primary admin routes use Laravel-style URLs without PHP filenames, for example `/admin/content`, `/admin/content/create`, `/admin/content/12/edit`, and `/admin/content/12/images`.
Legacy `/cms/*.php` routes and old query-string edit URLs remain as compatibility aliases only.
Most module tables have a generic create/edit/delete admin workflow. Sensitive structural modules such as Laravel users and the module manager are intentionally read-only until their authorization and import rules are implemented explicitly.

## Folder-based admin routes

The old CMS was navigated through folders and PHP files. Laravel now resolves those screens through clean admin URLs without copying the old PHP implementation:

- `/admin/modules`: admin module overview
- `/admin/content`: content item overview
- `/admin/content/categorieen`: content category overview
- `/admin/catalogus/actiecodes/create`: action-code create screen
- `/admin/catalogus/actiecodes/{id}/edit`: action-code edit screen
- `/admin/orders/afleverdata`: delivery-date overview
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
- Sliders: `slider`, `slidercategorie`, and `sliderslidercategorie` became `slider_*` tables.
- Forms: `form`, form builder tables, messages, submissions, and older `formulier*` tables became `form_*` tables.
- Events: `evenement`, categories, parts, images, and attachments became `event_*` tables.
- FAQ: `faq`, categories, images, attachments, and videos became `faq_*` tables.
- Downloads: `downloads` and download categories became `download_*` tables. Download files are stored on a private Laravel disk and are served only through generated hashed access-token URLs, with optional password protection and optional expiry.
- Locations: `vestiging`, categories, images, opening hours, and special opening hours became `location_*` tables.
- Vacancies: `vacatures`, categories, category links, and attachments became `vacancy_*` tables.
- Guestbook: `gastenboek` became `guestbook_entries`.

### Commerce

- Catalog: `catalogusartikel`, categories, product images, attachments, options, combinations, discounts, brands, promotions, reviews, translations, videos, stock, `actiecode`, and shipping costs became `catalog_*` plus `shipping_costs`.
- Orders: `orders`, `orderregels`, and delivery-date tables became `orders`, `order_items`, and `delivery_*`.

### Communication

- Mailing contacts and mailing-send tables are intentionally not part of this base.
- Newsletters: `nieuwsbrief`, newsletter categories, base templates, and template blocks became `newsletter_*`.
- Radio legacy modules are intentionally not part of this base.

### Platform

- Redirects and URLs: `redirect`, `urlverwijzing`, `url`, and `menucategorieen` became `redirects`, `url_references`, `urls`, and `menu_categories`.
- Domains: `domein` and `domeinrole` became `domains` and `domain_roles`.
- Users: the Laravel `users` table was extended with translated legacy profile fields; user categories, login tracking, rights, sessions, cookies, reset tokens, and tokens became `user_*`.
- Roles and permissions: `role`, `permissions`, `rechten`, role-category tables, and category-specific rights became Laravel-managed `roles`, `permissions`, `role_permissions`, `role_user`, and `page_permissions`. The rebuilt role editor stores permissions per module, module field, and module block, for example content attachments and event agenda blocks.
- Module manager: legacy `module`, `modulecategorie`, and `modulemodulecategorie` are represented by `cms_modules`, `module_categories`, `module_category_module`, and the CMS registry.
- Search keywords: legacy `zoekwoorden` is intentionally excluded from the base.
- Short links and geolocation: `bitlylinks` and `geolocatie` became `short_links` and `geo_locations`.

### Localization

- Languages and countries: legacy country and language tables became `languages`, `iso_languages`, `countries`, `country_codes`, and `country_payment_methods`.
- Translations: `translatekey`, `translatecontent`, and `translation` became `translation_keys`, `translation_values`, and `translations`.

## Import status

The Laravel schema and admin module registry are in place. Data import is intentionally separate: each importer should read from the SQL dump or a temporary legacy database, sanitize legacy HTML, translate fields, and write into English Laravel-named tables with fixture-backed tests.
