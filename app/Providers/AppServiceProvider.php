<?php

namespace App\Providers;

use App\Cms\PageBlocks\Blocks\AttachmentBlock;
use App\Cms\PageBlocks\Blocks\ButtonBlock;
use App\Cms\PageBlocks\Blocks\GalleryBlock;
use App\Cms\PageBlocks\Blocks\ImageBlock;
use App\Cms\PageBlocks\Blocks\QuoteBlock;
use App\Cms\PageBlocks\Blocks\TextBlock;
use App\Cms\PageBlocks\Blocks\TitleBlock;
use App\Cms\PageBlocks\Blocks\VideoBlock;
use App\Cms\PageBlocks\PageBlockRegistry;
use App\Models\Cms\Page;
use App\Models\User;
use App\Policies\PagePolicy;
use App\Support\Localization\DatabaseTranslationLoader;
use App\Support\Localization\TranslationRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PageBlockRegistry::class, function (): PageBlockRegistry {
            return (new PageBlockRegistry)
                ->register(TitleBlock::class)
                ->register(TextBlock::class)
                ->register(ImageBlock::class)
                ->register(VideoBlock::class)
                ->register(AttachmentBlock::class)
                ->register(ButtonBlock::class)
                ->register(QuoteBlock::class)
                ->register(GalleryBlock::class);
        });

        $this->app->extend('translation.loader', fn ($loader, $app): DatabaseTranslationLoader => new DatabaseTranslationLoader(
            $loader,
            $app->make(TranslationRepository::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user): bool => $user->hasAdminPermission('admin.access'));
        Gate::define('admin-permission', fn (User $user, string $permissionKey): bool => $user->hasAdminPermission($permissionKey));
        Gate::policy(Page::class, PagePolicy::class);
    }
}
