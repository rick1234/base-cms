# Admin Layout Sets

This project uses named admin layout sets as the default blueprint for backend module screens.

The visible backend reference screen is `/admin/base-module-conventions`. It uses fake model data so the base admin module conventions can be styled first, then reused as the target for new module prompts.

Prompts may refer to these sets directly, for example:

```text
Build the submissions export screen using the defined admin.builder.forms layout set.
```

Agents and contributors must treat these sets as the starting point for new admin work. A module may refine content and controls, but it should not create a new visual structure or private CSS system unless a new set is documented here first.

## Base Admin Module

The reusable base admin module owns default admin layout structure, shared partials, and shared styling.

Base admin structure lives in:

- `resources/views/layouts/admin.blade.php`
- `resources/views/admin/partials/`
- `resources/views/components/admin/`
- `app/Livewire/Admin/`

Base admin styling lives in:

- `resources/scss/components/_admin.scss`
- `resources/scss/components/_admin-compat.scss`
- `resources/scss/components/_content-admin.scss`
- `resources/scss/components/_forms.scss`
- `resources/scss/settings/_tokens.scss`

New modules should reuse those pieces first. Add semantic module classes only when the base set cannot express the screen cleanly. Never add inline CSS, Tailwind-like classes, Bootstrap, or one-off view styling.

## Set: `admin.index.content`

Use this for standard module overview/listing screens.

Canonical examples:

- `resources/views/admin/content/index.blade.php`
- `resources/views/admin/events/index.blade.php`
- `resources/views/admin/forms/index.blade.php`

Required shape:

- Extend `layouts.admin`.
- Use `.site-wrapper-container` with `admin.partials.navigation` in the `.left` region.
- Use `.main.has-buttons` when screen-level actions exist.
- Put screen actions in `.buttons-container.align-right`.
- Use translated labels and Material icons in buttons.
- Put the main content in `.main-section`.
- Include the module page header partial.
- Use the shared listing/category picker/tree pattern where categories apply.
- Prefer `livewire:admin.listing-overview` for standard records.

## Set: `admin.edit.content`

Use this for standard module create/edit screens for content-like records.

Canonical examples:

- `resources/views/admin/content/edit.blade.php`
- `resources/views/admin/events/edit.blade.php`
- `resources/views/admin/vacancies/edit.blade.php`

Required shape:

- Extend `layouts.admin`.
- Use the same shell as `admin.index.content`.
- Compute `$isExisting`, `$title`, and active named-route tab state at the top of the Blade view.
- Put save, save-and-stay, duplicate, delete, preview, live-page, and back actions in the top action bar as applicable.
- Use `form="..."` on action-bar submit buttons when the form is inside the main content.
- Use named routes with path segments for tabs, not query-string tab state.
- Include module item tabs immediately before or inside the active `.main-section`.
- Split the editor into semantic `.main-section` blocks.
- Use existing `.grid`, `.grid-row`, `.col-*`, `.form-item`, `.form-item-label`, and `.form-item-input` structure.
- Use `admin.content.partials.field-error` or a shared equivalent for validation messages.
- Use the shared category picker/tree pattern for category selection.
- Use shared attachment/media Livewire managers for attachments and image albums.
- Show author metadata for existing records when applicable.

## Set: `admin.block-builder.content-events`

Use this for block-builder behavior on records that own page/content blocks.

Canonical examples:

- `resources/views/admin/content/edit.blade.php`
- `resources/views/admin/events/edit.blade.php`
- `app/Livewire/Admin/Content/ContentBlockEditor.php`

Required shape:

- Use `livewire:admin.content.content-block-editor`.
- For pages/content items, pass `:content-item-id="$contentItem->id"`.
- For events, pass `owner-type="event"` and `:event-id="$event->id"`.
- Only render the builder for existing records.
- Place the builder in its own `.main-section`.
- Keep block type rendering in the page-block registry, block classes, and Blade previews/components.
- Keep block styling in base/admin SCSS or frontend block component SCSS, not inline in the builder view.

## Set: `admin.builder.forms`

Use this for form builder, response message, and submission inbox screens.

Canonical examples:

- `resources/views/admin/forms/edit.blade.php`
- `resources/views/admin/forms/submissions.blade.php`
- `resources/views/admin/forms/partials/builder.blade.php`
- `app/Livewire/Admin/Forms/FormSubmissionInbox.php`
- `app/Livewire/Admin/Forms/ResponseMailBuilder.php`

Required shape:

- Use the base admin shell and action bar.
- Use named route tabs with path segments, for example `/admin/form/{form}/submissions`.
- Use `admin.forms.partials.tabs` for the form screen tabs.
- Keep the form builder in the form module partials/Livewire classes.
- Keep submissions in the submission inbox Livewire component.
- Use translated empty states when a parent form must be saved or selected first.

## Set: `admin.media.album`

Use this for image album and media management screens.

Canonical examples:

- `resources/views/admin/content/images.blade.php`
- `resources/views/admin/events/edit.blade.php` image tab
- `resources/views/admin/locations/images.blade.php`
- `app/Livewire/Admin/Media/ImageAlbum.php`

Required shape:

- Use the base admin shell and active module tabs.
- Put the media manager in a `.main-section`.
- Use shared media Livewire components and `app/Support/Media/` helpers.
- Keep image captions, alt text, crop/variant settings, and SEO-facing labels translatable where shown.
- Do not create module-specific upload UI unless the shared media component cannot support the behavior.

## Adding A New Set

Only add a new set when the existing sets would make the screen misleading or cumbersome.

When adding one:

- Name it with the pattern `admin.<screen-kind>.<domain>`.
- Document canonical example files.
- Document required Blade structure, route expectations, Livewire/components, and SCSS ownership.
- Add tests for the reachable admin route/menu path and authorization-sensitive behavior when the set introduces new behavior.
