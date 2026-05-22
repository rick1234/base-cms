# Site extension layer

Use this namespace for website-specific code after the base project is forked.

Shared, reusable CMS behavior belongs in `app/`, `resources/views/frontend`, `resources/views/admin`, `resources/scss`, and the base route files.

Project-specific behavior should prefer:

- `app/Site/`
- `resources/views/site/`
- `resources/scss/site/`
- `routes/site.php`

Document whether a change belongs to the reusable base or the forked website before spreading it through shared files.
