<?php

namespace App\Support\Admin\Roles;

use App\Models\Cms\CmsPermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ModulePermissionRegistry
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function modules(): Collection
    {
        return collect(config('cms_modules.screens'))
            ->reject(fn (array $screen): bool => (bool) ($screen['read_only'] ?? false))
            ->map(function (array $screen, string $screenKey): array {
                return [
                    'key' => $screenKey,
                    'name' => (string) $screen['name'],
                    'group' => (string) $screen['group'],
                    'path' => Str::after((string) $screen['legacy_path'], 'cms/'),
                    'actions' => $this->actionsFor($screen),
                    'fields' => $this->fieldsFor($screenKey),
                    'blocks' => $this->blocksFor($screenKey),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function groupedModules(): Collection
    {
        $groups = collect(config('cms_modules.groups'));

        return $this->modules()
            ->groupBy('group')
            ->map(fn (Collection $modules, string $group): array => [
                'key' => $group,
                'name' => __($groups->get($group, Str::headline($group))),
                'modules' => $modules
                    ->map(fn (array $module): array => [
                        ...$module,
                        'name' => __($module['name']),
                        'actions' => collect($module['actions'])
                            ->map(fn (array $action): array => [
                                ...$action,
                                'label' => __($action['label']),
                            ])
                            ->values()
                            ->all(),
                        'fields' => collect($module['fields'])
                            ->map(fn (array $field): array => [
                                ...$field,
                                'label' => __($field['label']),
                            ])
                            ->values()
                            ->all(),
                        'blocks' => collect($module['blocks'])
                            ->map(fn (array $block): array => [
                                ...$block,
                                'label' => __($block['label']),
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->values(),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function permissionDefinitions(): Collection
    {
        $global = collect([
            [
                'permission_key' => 'admin.access',
                'module_key' => null,
                'permission_type' => 'global',
                'target' => 'admin',
                'action' => 'access',
                'name' => 'Admin access',
                'label' => 'Backend toegang',
                'description' => 'Allows a user to enter the admin area.',
                'sort_order' => 1,
            ],
        ]);

        $modulePermissions = $this->modules()
            ->flatMap(function (array $module): array {
                $permissions = [];

                foreach ($module['actions'] as $index => $action) {
                    $permissions[] = [
                        'permission_key' => $this->modulePermissionKey($module['key'], $action['key']),
                        'module_key' => $module['key'],
                        'permission_type' => 'module',
                        'target' => $module['key'],
                        'action' => $action['key'],
                        'name' => $module['name'].' '.$action['label'],
                        'label' => $action['label'],
                        'description' => 'Allows '.$action['label'].' for '.$module['name'].'.',
                        'sort_order' => ($index + 1) * 10,
                    ];
                }

                foreach ($module['fields'] as $index => $field) {
                    $permissions[] = [
                        'permission_key' => $this->fieldPermissionKey($module['key'], $field['key']),
                        'module_key' => $module['key'],
                        'permission_type' => 'field',
                        'target' => $field['key'],
                        'action' => 'manage',
                        'name' => $module['name'].' field '.$field['label'],
                        'label' => $field['label'],
                        'description' => 'Allows editing the '.$field['label'].' field for '.$module['name'].'.',
                        'sort_order' => 1000 + (($index + 1) * 10),
                    ];
                }

                foreach ($module['blocks'] as $index => $block) {
                    $permissions[] = [
                        'permission_key' => $this->blockPermissionKey($module['key'], $block['key']),
                        'module_key' => $module['key'],
                        'permission_type' => 'block',
                        'target' => $block['key'],
                        'action' => 'manage',
                        'name' => $module['name'].' block '.$block['label'],
                        'label' => $block['label'],
                        'description' => 'Allows managing the '.$block['label'].' block for '.$module['name'].'.',
                        'sort_order' => 2000 + (($index + 1) * 10),
                    ];
                }

                return $permissions;
            });

        return $global
            ->merge($modulePermissions)
            ->values();
    }

    /**
     * @return Collection<string, CmsPermission>
     */
    public function syncPermissions(): Collection
    {
        return $this->permissionDefinitions()
            ->mapWithKeys(function (array $definition): array {
                $permission = CmsPermission::query()->updateOrCreate(
                    ['permission_key' => $definition['permission_key']],
                    [
                        'name' => $definition['name'],
                        'slug' => Str::slug($definition['permission_key']),
                        'module_key' => $definition['module_key'],
                        'permission_type' => $definition['permission_type'],
                        'target' => $definition['target'],
                        'action' => $definition['action'],
                        'label' => $definition['label'],
                        'description' => $definition['description'],
                        'status' => 'active',
                        'is_system' => true,
                        'sort_order' => $definition['sort_order'],
                    ],
                );

                return [$definition['permission_key'] => $permission];
            });
    }

    public function modulePermissionKey(string $moduleKey, string $action): string
    {
        return "admin.module.{$moduleKey}.{$action}";
    }

    public function fieldPermissionKey(string $moduleKey, string $field): string
    {
        return "admin.field.{$moduleKey}.{$field}";
    }

    public function blockPermissionKey(string $moduleKey, string $block): string
    {
        return "admin.block.{$moduleKey}.{$block}";
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function actionsFor(array $screen): array
    {
        $actions = [
            ['key' => 'view', 'label' => 'Bekijken'],
        ];

        $pages = array_keys((array) ($screen['pages'] ?? []));

        if (in_array('edit', $pages, true)) {
            $actions[] = ['key' => 'create', 'label' => 'Toevoegen'];
            $actions[] = ['key' => 'update', 'label' => 'Bewerken'];
        }

        if (! (bool) ($screen['read_only'] ?? false)) {
            $actions[] = ['key' => 'delete', 'label' => 'Verwijderen'];
        }

        return $actions;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function fieldsFor(string $moduleKey): array
    {
        $fields = [
            'content_items' => ['title', 'slug', 'meta_description', 'status', 'active_window'],
            'content_categories' => ['name', 'slug', 'description', 'meta_description', 'status'],
            'events' => ['title', 'slug', 'intro', 'body', 'date_range', 'location', 'status'],
            'event_categories' => ['name', 'slug', 'description', 'status'],
            'faq_items' => ['question', 'answer', 'more_info', 'status'],
            'faq_categories' => ['name', 'slug', 'description', 'status'],
            'vacancies' => ['title', 'slug', 'locale', 'intro', 'body', 'job_details', 'meta_description', 'status', 'active_window'],
            'vacancy_categories' => ['name', 'slug', 'description', 'status'],
            'forms' => ['name', 'slug', 'description', 'submit_text', 'success_message', 'status'],
            'form_categories' => ['name', 'description', 'status'],
            'downloads' => ['name', 'slug', 'description', 'file', 'security', 'status'],
            'download_categories' => ['name', 'slug', 'description', 'status'],
            'banners' => ['title', 'image', 'link_url', 'button_text', 'text', 'status'],
            'banner_categories' => ['name', 'slug', 'description', 'status'],
            'catalog_products' => ['name', 'slug', 'description', 'pricing', 'stock', 'status'],
            'catalog_categories' => ['name', 'slug', 'description', 'status'],
            'catalog_brands' => ['name', 'slug', 'description', 'status'],
            'catalog_promotions' => ['name', 'slug', 'description', 'status'],
            'catalog_reviews' => ['title', 'rating', 'status'],
            'catalog_coupons' => ['name', 'code', 'discount', 'date_range', 'status'],
            'locations' => ['name', 'address', 'contact', 'map', 'description', 'status'],
            'location_categories' => ['name', 'slug', 'description', 'status'],
            'users' => ['name', 'email', 'password', 'active_window', 'role_assignment', 'profile'],
            'user_categories' => ['name', 'slug', 'description', 'status'],
            'redirects' => ['source_path', 'target_url', 'status_code', 'querystring', 'status'],
            'urls' => ['path', 'target', 'locale', 'restriction'],
            'url_references' => ['source_path', 'target_path', 'status'],
            'roles' => ['name', 'slug', 'description', 'permissions', 'status'],
            'translations' => ['area', 'group', 'key', 'source_text', 'values', 'status'],
        ];

        return collect($fields[$moduleKey] ?? ['name', 'slug', 'description', 'status'])
            ->map(fn (string $field): array => ['key' => $field, 'label' => Str::headline($field)])
            ->all();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function blocksFor(string $moduleKey): array
    {
        $blocks = [
            'content_items' => [
                'structured_blocks' => 'Content blocks',
                'images' => 'Afbeeldingen',
                'attachments' => 'Bijlagen',
                'slider' => 'Slider',
                'forms' => 'Formulieren',
            ],
            'content_categories' => [
                'images' => 'Afbeeldingen',
                'slider' => 'Slider',
            ],
            'events' => [
                'agenda' => 'Agenda',
                'images' => 'Afbeeldingen',
                'attachments' => 'Bijlagen',
                'parts' => 'Onderdelen',
            ],
            'faq_items' => [],
            'forms' => [
                'builder' => 'Formulier',
                'recipients' => 'Ontvangers',
                'mail_layouts' => 'Bevestigingsmail',
                'submissions' => 'Ontvangen berichten',
            ],
            'catalog_products' => [
                'images' => 'Afbeeldingen',
                'attachments' => 'Bijlagen',
                'options' => 'Opties',
                'translations' => 'Vertalingen',
                'videos' => 'Videos',
                'stock' => 'Voorraad',
            ],
            'locations' => [
                'images' => 'Afbeeldingen',
                'opening_hours' => 'Openingstijden',
                'special_hours' => 'Afwijkende openingstijden',
            ],
            'downloads' => [
                'file' => 'Bestand',
                'passwords' => 'Wachtwoorden',
                'unique_links' => 'Unieke URLs',
            ],
            'users' => [
                'categories' => 'Categorieen',
                'roles' => 'Rollen',
                'profile_image' => 'Profielafbeelding',
            ],
        ];

        return collect($blocks[$moduleKey] ?? [])
            ->map(fn (string $label, string $key): array => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }
}
