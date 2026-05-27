<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminRouteConventionTest extends TestCase
{
    public function test_canonical_admin_create_routes_use_create_actions(): void
    {
        $violations = collect(Route::getRoutes())
            ->filter(function ($route): bool {
                $name = (string) $route->getName();

                return str_starts_with($name, 'admin.')
                    && str_ends_with($name, '.create')
                    && $name !== 'admin.modules.create';
            })
            ->mapWithKeys(fn ($route): array => [(string) $route->getName() => $route->getActionName()])
            ->reject(fn (string $action): bool => str_ends_with($action, '@create'))
            ->all();

        $this->assertSame([], $violations);
    }
}
