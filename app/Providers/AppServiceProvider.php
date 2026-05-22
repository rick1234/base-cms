<?php

namespace App\Providers;

use App\Models\Cms\Page;
use App\Models\User;
use App\Policies\PagePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user): bool => $user->is_admin);
        Gate::policy(Page::class, PagePolicy::class);
    }
}
