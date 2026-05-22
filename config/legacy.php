<?php

return [
    'source_root' => env('LEGACY_SOURCE_ROOT', 'C:\htdocs\basiswms'),
    'sql_dump' => env('LEGACY_SQL_DUMP', 'C:\htdocs\basiswms\basiswms.sql'),

    'module_groups' => [
        'content' => ['contentitems', 'contentcategorieen', 'contentitemblock', 'contentitemblockpart'],
        'navigation' => ['url', 'urlverwijzing', 'redirect', 'menucategorieen'],
        'forms' => ['form', 'formblock', 'formfield', 'formmessage'],
        'commerce' => ['catalogusartikel', 'cataloguscategorie', 'orders', 'orderregels'],
        'users' => ['users', 'role', 'permissions', 'rbaclog'],
        'site_modules' => ['banner', 'slider', 'faq', 'vacatures', 'vestiging', 'evenement'],
    ],
];
