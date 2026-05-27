# Domain Templates

Domains are reusable base configuration, not fork-specific content hard-coded into shared files.

## Runtime lookup

The `ResolveDomain` middleware resolves the active domain from the request host, domain aliases, or a `.test` preview session. Public pages and common public module records may set `domain_id`; runtime lookups prefer the matching domain record and then fall back to shared records where `domain_id` is empty.

The local development domain follows Laravel Herd's folder-name convention. By default `config('cms_domains.local_domain')` resolves to `{project-folder}.test`, for example `base-cms.test`. New domain setup screens show this host, and saves keep it available as the local preview alias when another domain has not claimed it.

The admin domain form is a step wizard. Each step renders its own Blade view under `resources/views/admin/domains/steps/`, and switching steps submits the currently visible form so only that step's fields are validated and saved. The Languages step can activate an existing CMS language from a name/code search, then add that locale to the domain's frontend and/or backend language lists. A domain never treats an empty language list as "all languages"; it falls back to the domain default locale and the step requires explicit frontend/backend selections when saved.

## Templates

Website templates live in `website_templates` and define default CSS-token settings such as colors, fonts, Google Fonts stylesheet links, title style, button style, logo and hero assets, wrapper sizing, USP text, footer copy, social placement, and contact form placement. Domains can override those named settings.

Google Fonts links are stored separately for base/body text and headings. Only `https://fonts.googleapis.com/css` and `https://fonts.googleapis.com/css2` stylesheet URLs are accepted. The frontend loads those stylesheets and infers the first `family=` value as the CSS font-family for that font slot.

The template `handle` is the technical name. It is used for the default paths:

```text
resources/scss/site/templates/{technical-name}/_index.scss
resources/views/site/templates/{technical-name}
public/site/templates/{technical-name}
```

The seeded `default` template is the reusable base frontend. Its assets live in:

```text
public/site/templates/default/assets/
resources/scss/site/templates/default/
resources/views/site/templates/default/
```

The admin template screen can generate this structure:

```text
resources/views/site/templates/{handle}/
resources/scss/site/templates/{handle}/
public/site/templates/{handle}/
```

Keep reusable base views in `resources/views/frontend`, template-specific partials in the generated template folder, and fork-specific behavior under `resources/views/site`.

The default template can optionally show frontend search. Search uses a normal Laravel GET route, validates the query with a Form Request, and searches published pages and content items scoped to the active domain.

## Favicons

Domains accept one logo upload for favicon generation. SVG, PNG, JPG, and WebP inputs are accepted. Raster logos are centered on a transparent square canvas and converted to common favicon PNG sizes, an ICO file, Apple touch icon, web manifest, and browserconfig file. SVG logos are checked for unsafe markup and stored as SVG favicon variants.

RealFaviconGenerator can be added later as an optional integration point if the project needs its platform-specific output and settings.

## Frontend Toolbar

Local and testing environments show a fixed frontend domain toolbar on `.test`, `.localhost`, `localhost`, and `127.0.0.1` requests when domains exist. Use it to switch the active domain through the preview session while staying on the Herd local host.

Run `DomainTemplateSeeder` or the default database seeder to create `base-cms.test`, `www.example.nl`, and `www.example.fr` sample domains with matching pages that share the `default` template.

## Integrations

Public tracking identifiers live in `public_integrations`. Private plugin credentials must be added as named, validated fields before they are exposed in the admin UI, then stored encrypted and never rendered directly into Blade, JavaScript, logs, or frontend assets.
