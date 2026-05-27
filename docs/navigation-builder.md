# Navigation Builder

The navigation builder is shared base functionality. Website-specific menus should be configured through records, not by hard-coding links into Blade templates.

## Storage

- `navigation_menus` stores reusable menus by `handle`, with optional `domain_id` and `locale` scope.
- `navigation_menu_items` stores nested items with active state, custom URLs, module target type/id, and optional category expansion.
- The frontend currently renders the active global or domain-specific `primary` menu. If no active primary menu exists, it falls back to published CMS pages.

## Link Targets

Items can point to custom URLs or records exposed by `App\Support\Navigation\NavigationLinkRegistry`.

When a category target has `expand_children` enabled, its active child categories are rendered as submenu entries. This is intended for cases such as a `Products` catalog category that should expose its subcategories below the top-level item.

Custom URLs are normalized during rendering. Absolute external HTTP(S) URLs open in a new tab with `rel="noopener"`.

## Extension

Add new selectable module targets by extending `NavigationLinkRegistry::definitions()`. Keep route generation in the registry so the admin builder, frontend renderer, and modal selector agree on labels and URLs.
