#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Support\Admin\Dashboard\DashboardNavigationBuilder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);
$mode = $argv[1] ?? 'basic';
$validModes = ['basic', 'extended', 'schema'];

if (in_array($mode, ['-h', '--help', 'help'], true) || ! in_array($mode, $validModes, true)) {
    fwrite(STDOUT, <<<'HELP'
Base CMS health check

Usage:
  php scripts/health-check.php basic
  php scripts/health-check.php extended
  php scripts/health-check.php schema

Modes:
  basic     Fast CMS/schema residue checks plus focused admin/database tests.
  extended  Full suite, frontend build, and isolated fresh migration/seed check.
  schema    CMS/schema residue checks only; used by the extended isolated DB pass.

HELP);

    exit(in_array($mode, ['-h', '--help', 'help'], true) || in_array($mode, $validModes, true) ? 0 : 1);
}

try {
    if ($mode === 'basic') {
        runBasicHealth($root);
    } elseif ($mode === 'extended') {
        runExtendedHealth($root);
    } else {
        bootstrapLaravel($root);
        runSchemaHealthChecks($root);
    }

    line('');
    line('[OK] '.$mode.' health check passed.');
    exit(0);
} catch (Throwable $throwable) {
    line('');
    line('[FAIL] '.$mode.' health check failed.');
    line($throwable->getMessage());
    exit(1);
}

function runBasicHealth(string $root): void
{
    runCommand($root, 'Composer manifest validation', ['composer', 'validate', '--strict'], 120);
    runCommand($root, 'Clear Laravel config cache', [PHP_BINARY, 'artisan', 'config:clear', '--ansi'], 120);

    bootstrapLaravel($root);
    runSchemaHealthChecks($root);

    runCommand($root, 'Migration status', [PHP_BINARY, 'artisan', 'migrate:status', '--ansi'], 120);
    runCommand($root, 'Focused admin/database tests', [
        PHP_BINARY,
        'artisan',
        'test',
        'tests/Feature/Admin/TranslationModuleTest.php',
        'tests/Feature/Admin/CmsModuleTest.php',
        'tests/Feature/LegacyStructureConventionTest.php',
    ], 600, testingEnvironment(':memory:'));
}

function runExtendedHealth(string $root): void
{
    runBasicHealth($root);

    runCommand($root, 'Full Laravel test suite', [PHP_BINARY, 'artisan', 'test'], 1200, testingEnvironment(':memory:'));
    runCommand($root, 'Frontend production build', ['npm', 'run', 'build'], 600);
    runIsolatedFreshSchemaCheck($root);
}

function bootstrapLaravel(string $root): void
{
    static $booted = false;

    if ($booted) {
        return;
    }

    require $root.'/vendor/autoload.php';

    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    $booted = true;
}

function runSchemaHealthChecks(string $root): void
{
    section('Schema health');

    assertNoPendingMigrations();
    assertNoObsoleteTables();
    assertCmsModelsPointToExistingTables($root);
    assertConfiguredCmsScreenTablesExist();
    assertConfiguredScreensAreReachable();
    assertCmsModuleRegistryIsReachable();
    assertNoObsoletePermissions();
    assertNoObsoleteCodeResidue($root);

    line('[OK] CMS schema/config/code residue checks passed.');
}

function assertNoPendingMigrations(): void
{
    $migrator = app('migrator');
    $repository = $migrator->getRepository();

    if (! $repository->repositoryExists()) {
        throw new RuntimeException('The migrations table does not exist. Run migrations before health checks.');
    }

    $migrationFiles = array_keys($migrator->getMigrationFiles(database_path('migrations')));
    $ranMigrations = $repository->getRan();
    $pending = array_values(array_diff($migrationFiles, $ranMigrations));

    if ($pending !== []) {
        throw new RuntimeException("Pending migrations:\n- ".implode("\n- ", $pending));
    }
}

function assertNoObsoleteTables(): void
{
    $obsoleteTables = [
        'catalog_coupons',
        'catalog_coupon_product',
        'catalog_promotions',
        'catalog_reviews',
        'catalog_stocks',
        'category_right_specifications',
        'cms_redirect_rules',
        'country_payment_methods',
        'delivery_dates',
        'delivery_days',
        'delivery_settings',
        'domain_roles',
        'geo_locations',
        'guestbook_entries',
        'login_cookies',
        'menu_categories',
        'module_categories',
        'module_category_module',
        'newsletter_base_templates',
        'newsletter_categories',
        'newsletter_category_newsletter',
        'newsletter_template_blocks',
        'newsletters',
        'order_deliveries',
        'order_delivery_lines',
        'order_lines',
        'order_payments',
        'orders',
        'page_permissions',
        'password_reset_records',
        'rights',
        'role_categories',
        'role_category_role',
        'role_content',
        'role_languages',
        'shipping_costs',
        'short_links',
        'slider_categories',
        'slider_category_slider',
        'sliders',
        'user_category_roles',
        'user_rights',
        'user_sessions',
        'user_tokens',
    ];

    $present = currentDatabaseTables()
        ->intersect($obsoleteTables)
        ->values()
        ->all();

    if ($present !== []) {
        throw new RuntimeException("Obsolete tables still exist:\n- ".implode("\n- ", $present));
    }
}

function assertCmsModelsPointToExistingTables(string $root): void
{
    $missing = collect(glob($root.'/app/Models/Cms/*.php') ?: [])
        ->map(function (string $file): ?string {
            $class = 'App\\Models\\Cms\\'.basename($file, '.php');

            if ($class === 'App\\Models\\Cms\\CmsModel') {
                return null;
            }

            if (! class_exists($class)) {
                return $class.' is not autoloadable.';
            }

            if (! is_subclass_of($class, Model::class)) {
                return null;
            }

            /** @var Model $model */
            $model = new $class;

            return Schema::hasTable($model->getTable()) ? null : $class.' -> '.$model->getTable();
        })
        ->filter()
        ->values()
        ->all();

    if ($missing !== []) {
        throw new RuntimeException("CMS models point to missing tables:\n- ".implode("\n- ", $missing));
    }
}

function assertConfiguredCmsScreenTablesExist(): void
{
    $missing = collect(config('cms_modules.screens', []))
        ->flatMap(function (array $screen, string $screenKey): array {
            $tables = [];

            if (isset($screen['table'])) {
                $tables[] = [$screenKey, $screen['table']];
            }

            foreach (($screen['pages'] ?? []) as $pageKey => $page) {
                if (isset($page['table'])) {
                    $tables[] = [$screenKey.'.'.$pageKey, $page['table']];
                }
            }

            return $tables;
        })
        ->map(fn (array $pair): ?string => Schema::hasTable((string) $pair[1]) ? null : $pair[0].' -> '.$pair[1])
        ->filter()
        ->values()
        ->all();

    if ($missing !== []) {
        throw new RuntimeException("Configured CMS screen tables are missing:\n- ".implode("\n- ", $missing));
    }
}

function assertConfiguredScreensAreReachable(): void
{
    $moduleList = app(DashboardNavigationBuilder::class)->moduleList();
    $reachableScreens = $moduleList
        ->flatMap(function (array $module): array {
            $screens = [$module['overview_screen'] ?? null];

            foreach ($module['subitems'] ?? [] as $subitem) {
                $screens[] = $subitem['screen_key'] ?? null;
            }

            return $screens;
        })
        ->filter()
        ->unique()
        ->values();

    $configuredScreens = collect(config('cms_modules.screens', []))->keys();
    $missing = $configuredScreens->diff($reachableScreens)->values()->all();

    if ($missing !== []) {
        throw new RuntimeException("Configured CMS screens are not reachable in admin navigation:\n- ".implode("\n- ", $missing));
    }
}

function assertCmsModuleRegistryIsReachable(): void
{
    if (! Schema::hasTable('cms_modules')) {
        throw new RuntimeException('cms_modules table is missing.');
    }

    $configuredHandles = collect(config('cms_modules.modules', []))->keys();
    $unknown = DB::table('cms_modules')
        ->pluck('handle')
        ->diff($configuredHandles)
        ->values()
        ->all();

    if ($unknown !== []) {
        throw new RuntimeException("cms_modules contains handles that are not configured:\n- ".implode("\n- ", $unknown));
    }

    $removedHandles = [
        'catalog_coupons',
        'catalog_promotions',
        'catalog_reviews',
        'guestbook',
        'module_categories',
        'module_manager',
        'newsletter',
        'newsletters',
        'orders',
        'slider_categories',
        'sliders',
    ];

    $removedNames = [
        'Catalog Coupons',
        'Catalog Promotions',
        'Catalog Reviews',
        'Guestbook',
        'Module Categories',
        'Module Manager',
        'Newsletter',
        'Newsletters',
        'Orders',
        'Slider Categories',
        'Sliders',
    ];

    $stale = DB::table('cms_modules')
        ->whereIn('handle', $removedHandles)
        ->orWhereIn('name', $removedNames)
        ->get(['handle', 'name'])
        ->map(fn (object $row): string => $row->handle.' ('.$row->name.')')
        ->values()
        ->all();

    if ($stale !== []) {
        throw new RuntimeException("cms_modules still contains removed modules:\n- ".implode("\n- ", $stale));
    }
}

function assertNoObsoletePermissions(): void
{
    if (! Schema::hasTable('permissions')) {
        return;
    }

    $obsoletePermissionPattern = '/orders|sliders|slider_categories|guestbook|newsletter|catalog_coupons|catalog_promotions|catalog_reviews|module_categories/i';
    $stale = DB::table('permissions')
        ->pluck('name')
        ->filter(fn (string $name): bool => preg_match($obsoletePermissionPattern, $name) === 1)
        ->values()
        ->all();

    if ($stale !== []) {
        throw new RuntimeException("Obsolete permissions still exist:\n- ".implode("\n- ", $stale));
    }
}

function assertNoObsoleteCodeResidue(string $root): void
{
    $patterns = [
        'CatalogCoupon',
        'CatalogPromotion',
        'CatalogReview',
        'CatalogStock',
        'Newsletter',
        'OrderController',
        'SliderController',
        'catalog_coupon',
        'catalog_promotion',
        'catalog_review',
        'catalog_stock',
        'delivery_dates',
        'delivery_days',
        'delivery_settings',
        'domain_roles',
        'geo_locations',
        'guestbook_entries',
        'guestbook',
        'login_cookies',
        'menu_categories',
        'module_categories',
        'module_category_module',
        'newsletter',
        'page_permissions',
        'password_reset_records',
        'role_categories',
        'role_category_role',
        'role_content',
        'role_languages',
        'shipping_costs',
        'short_links',
        'slider_categories',
        'sliders',
        'user_category_roles',
        'user_rights',
        'user_sessions',
        'user_tokens',
        'Action code',
        'Actiecode',
        'actiecode',
        'Catalog Promotions',
        'Catalog Reviews',
        'Catalog promotion',
        'Catalog review',
        'CatalogusPromotie',
        'Download invoice',
        'Download packing slip',
        'Generate order export',
        'Promotie',
        'Promoties',
        'Review created',
        'Review deleted',
        'Review saved',
        'Reviewer',
        'Reviews overzicht',
    ];

    $regex = '/'.implode('|', array_map(fn (string $pattern): string => str_replace('\ ', ' ', preg_quote($pattern, '/')), $patterns)).'|\borders\b/i';
    $directories = [
        $root.'/app',
        $root.'/config',
        $root.'/routes',
        $root.'/resources',
        $root.'/database/seeders',
    ];
    $extensions = ['php', 'blade.php'];
    $matches = [];

    foreach ($directories as $directory) {
        if (! is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            $matchesExtension = collect($extensions)
                ->contains(fn (string $extension): bool => str_ends_with($path, '.'.$extension));

            if (! $matchesExtension) {
                continue;
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];

            foreach ($lines as $lineNumber => $line) {
                if (preg_match($regex, $line) === 1) {
                    $matches[] = relativePath($root, $path).':'.($lineNumber + 1);
                }
            }
        }
    }

    if ($matches !== []) {
        throw new RuntimeException("Obsolete module/table residue found in live code/config/seeders:\n- ".implode("\n- ", array_slice($matches, 0, 50)));
    }
}

function currentDatabaseTables(): Illuminate\Support\Collection
{
    $connection = DB::connection();

    if ($connection->getDriverName() === 'mysql') {
        return collect(DB::select(
            'select table_name as name from information_schema.tables where table_schema = database() order by table_name',
        ))->pluck('name');
    }

    return collect(Schema::getTableListing())
        ->map(fn (string $table): string => str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table)
        ->sort()
        ->values();
}

function runIsolatedFreshSchemaCheck(string $root): void
{
    section('Isolated fresh migration/seed');

    $directory = rtrim(sys_get_temp_dir(), "\\/").DIRECTORY_SEPARATOR.'base-cms-health-checks';

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $database = $directory.'/health-'.date('YmdHis').'-'.bin2hex(random_bytes(4)).'.sqlite';
    touch($database);

    $env = testingEnvironment($database);

    try {
        runCommand($root, 'Fresh migrate and seed on temporary SQLite database', [
            PHP_BINARY,
            'artisan',
            'migrate:fresh',
            '--seed',
            '--force',
            '--ansi',
        ], 600, $env);

        runCommand($root, 'Fresh schema health checks', [
            PHP_BINARY,
            'scripts/health-check.php',
            'schema',
        ], 300, $env);
    } finally {
        foreach ([$database, $database.'-journal', $database.'-wal', $database.'-shm'] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

function testingEnvironment(string $database): array
{
    return [
        'APP_ENV' => 'testing',
        'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        'BCRYPT_ROUNDS' => '4',
        'BROADCAST_CONNECTION' => 'null',
        'CACHE_STORE' => 'array',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $database,
        'DB_URL' => '',
        'MAIL_MAILER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER' => 'array',
    ];
}

function runCommand(string $root, string $label, array $command, int $timeout, array $environment = []): void
{
    section($label);

    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open(commandLine($command), $descriptorSpec, $pipes, $root, $environment === [] ? null : processEnvironment($environment));

    if (! is_resource($process)) {
        throw new RuntimeException($label.' could not be started.');
    }

    foreach ($pipes as $pipe) {
        stream_set_blocking($pipe, false);
    }

    $startedAt = microtime(true);
    $exitCode = null;

    while (true) {
        foreach ([1 => STDOUT, 2 => STDERR] as $index => $target) {
            $buffer = stream_get_contents($pipes[$index]);

            if ($buffer !== false && $buffer !== '') {
                fwrite($target, $buffer);
            }
        }

        $status = proc_get_status($process);

        if (! $status['running']) {
            $exitCode = $status['exitcode'];
            break;
        }

        if ((microtime(true) - $startedAt) > $timeout) {
            proc_terminate($process);
            throw new RuntimeException($label.' timed out after '.$timeout.' seconds.');
        }

        usleep(100000);
    }

    foreach ([1 => STDOUT, 2 => STDERR] as $index => $target) {
        $buffer = stream_get_contents($pipes[$index]);

        if ($buffer !== false && $buffer !== '') {
            fwrite($target, $buffer);
        }

        fclose($pipes[$index]);
    }

    $closeCode = proc_close($process);

    if ($exitCode === -1) {
        $exitCode = $closeCode;
    }

    if ($exitCode !== 0) {
        throw new RuntimeException($label.' failed with exit code '.$exitCode.'.');
    }
}

function commandLine(array $command): string
{
    if (PHP_OS_FAMILY === 'Windows' && isset($command[0])) {
        $command[0] = match ($command[0]) {
            'composer' => resolveWindowsExecutable(['composer.bat', 'composer.cmd', 'composer']),
            'npm' => resolveWindowsExecutable(['npm.cmd', 'npm.bat', 'npm']),
            default => $command[0],
        };
    }

    return implode(' ', array_map('escapeCommandArgument', $command));
}

function resolveWindowsExecutable(array $candidates): string
{
    $paths = explode(PATH_SEPARATOR, (string) getenv('PATH'));

    foreach ($paths as $path) {
        foreach ($candidates as $candidate) {
            $executable = rtrim($path, "\\/").DIRECTORY_SEPARATOR.$candidate;

            if (is_file($executable)) {
                return $executable;
            }
        }
    }

    return $candidates[0];
}

function escapeCommandArgument(string $argument): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        return '"'.str_replace('"', '\\"', $argument).'"';
    }

    return escapeshellarg($argument);
}

function processEnvironment(array $overrides): array
{
    $environment = [];

    foreach ([$_SERVER, $_ENV] as $source) {
        foreach ($source as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $environment[(string) $key] = (string) $value;
            }
        }
    }

    foreach ($overrides as $key => $value) {
        $environment[(string) $key] = (string) $value;
    }

    return $environment;
}

function section(string $title): void
{
    line('');
    line('== '.$title.' ==');
}

function line(string $message): void
{
    fwrite(STDOUT, $message.PHP_EOL);
    fflush(STDOUT);
}

function relativePath(string $root, string $path): string
{
    $root = rtrim(str_replace('\\', '/', $root), '/').'/';
    $path = str_replace('\\', '/', $path);

    return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
}
