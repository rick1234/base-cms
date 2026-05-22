# WMS module map

The legacy WMS is now represented as English Laravel modules and folder screens under `config/wms.php`, with admin routes under `/admin/wms` and legacy-style folder routes under `/wms`.
Most module tables have a generic create/edit/delete admin workflow. Sensitive structural modules such as Laravel users and the module manager are intentionally read-only until their authorization and import rules are implemented explicitly.

## Folder-based route compatibility

The old WMS was navigated through folders and PHP files. Laravel now resolves those routes without copying the old PHP implementation:

- `/wms/index.php`: WMS module overview
- `/wms/content/index.php`: content item overview
- `/wms/content/categorieen/index.php`: content category overview
- `/wms/catalogus/actiecodes/edit.php`: action-code editor
- `/wms/orders/afleverdata/index.php`: delivery-date overview
- `/wms/radio/gidsen/schedule.php`: radio schedule utility/editor screen
- `/wms/{legacy-folder}/edit.php`: create screen
- `/wms/{legacy-folder}/edit.php?id={record}`: edit screen
- `/wms/{legacy-folder}/{page}.php`: configured legacy page variant

The route layer accepts the legacy Dutch folder names, while the Laravel internals use English module handles and English table names.
The `screens` registry is the source of truth for all folder-based WMS pages. A screen can expose an index page, an edit page, related edit pages such as `editAfbeeldingen.php`, and safe utility pages such as exports, imports, sync pages, and log views.

The schema source for this map is `C:\htdocs\basiswms\basiswms.sql` plus the legacy `C:\htdocs\basiswms\wms` module folders.

## Module groups

### Website

- Content: `contentitems`, `contentcategorieen`, content blocks, block parts, images, attachments, and category links became `wms_content_*` tables.
- Banners: `banner`, `bannercategorie`, `bannerbannercategorie`, and `bannertranslation` became `wms_banner_*` tables.
- Sliders: `slider`, `slidercategorie`, and `sliderslidercategorie` became `wms_slider_*` tables.
- Forms: `form`, form builder tables, messages, submissions, and older `formulier*` tables became `wms_form_*` tables.
- Events: `evenement`, categories, parts, images, and attachments became `wms_event_*` tables.
- FAQ: `faq`, categories, images, attachments, and videos became `wms_faq_*` tables.
- Downloads: `downloads` and download categories became `wms_download_*` tables.
- Locations: `vestiging`, categories, images, opening hours, and special opening hours became `wms_location_*` tables.
- Vacancies: `vacatures`, categories, category links, and attachments became `wms_vacancy_*` tables.
- Guestbook: `gastenboek` became `wms_guestbook_entries`.

### Commerce

- Catalog: `catalogusartikel`, categories, product images, attachments, options, combinations, discounts, brands, promotions, reviews, translations, videos, stock, `actiecode`, and shipping costs became `wms_catalog_*` plus `wms_shipping_costs`.
- Orders: `orders`, `orderregels`, and delivery-date tables became `wms_orders`, `wms_order_items`, and `wms_delivery_*`.

### Communication

- Mailing contacts: `mailingcontacten`, contact categories, groups, blacklist, tokens, and opened-mail records became `wms_mailing_*`.
- Newsletters: `nieuwsbrief`, newsletter categories, base templates, template blocks, mail data, mailings, and mailing items became `wms_newsletter_*`, `wms_mailings`, and `wms_mailing_items`.
- Radio: `radiogids`, `radioprogramma`, and `radioschedule` became `wms_radio_*`.

### Platform

- Redirects and URLs: `redirect`, `urlverwijzing`, `url`, and `menucategorieen` became `wms_redirects`, `wms_url_references`, `wms_urls`, and `wms_menu_categories`.
- Domains: `domein` and `domeinrole` became `wms_domains` and `wms_domain_roles`.
- Users: the Laravel `users` table was extended with translated legacy profile fields; user categories, login tracking, rights, sessions, cookies, reset tokens, and tokens became `wms_user_*`.
- Roles and permissions: `role`, `permissions`, `rechten`, role-category tables, category-specific rights, `wms_permissions`, and `rbaclog` became `wms_roles`, `wms_permissions`, `wms_rights`, `wms_role_*`, `wms_page_permissions`, and `wms_rbac_logs`.
- Module manager: legacy `module`, `modulecategorie`, and `modulemodulecategorie` are represented by `cms_modules`, `wms_module_categories`, `wms_module_category_module`, and the WMS registry.
- Search keywords: `zoekwoorden` became `wms_search_keywords`.
- Short links and geolocation: `bitlylinks` and `geolocatie` became `wms_short_links` and `wms_geo_locations`.

### Localization

- Languages and countries: `talen`, `isotalen`, `isolanden`, `isolandcode`, `isolandbetalingswijze`, and `landen` became `wms_languages`, `wms_iso_languages`, `wms_countries`, `wms_country_codes`, and `wms_country_payment_methods`.
- Translations: `translatekey`, `translatecontent`, and `translation` became `wms_translation_keys`, `wms_translation_values`, and `wms_translations`.

## Import status

The Laravel schema and admin module registry are in place. Data import is intentionally separate: each importer should read from the SQL dump or a temporary legacy database, sanitize legacy HTML, translate fields, and write into the English `wms_*` tables with fixture-backed tests.
