<?php

namespace App\Providers;

use App\Models\User;
use App\Support\AdminPermissions;
use App\Support\Storefront\StorefrontContent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureViewComposers();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure authorization defaults for the admin and storefront domain.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(
            fn (User $user, string $ability): ?bool => $user->hasRole(AdminPermissions::SuperAdmin) ? true : null,
        );

        Gate::guessPolicyNamesUsing(
            fn (string $modelClass): string => str_replace('\\Models\\', '\\Policies\\', $modelClass).'Policy',
        );
    }

    protected function configureViewComposers(): void
    {
        View::composer('components.layouts.storefront', function ($view): void {
            $content = app(StorefrontContent::class);

            $view->with([
                'storefrontAnnouncement' => $content->announcementBar(),
                'storefrontNavigation' => $content->navigationMenu(),
                'storefrontFooterSections' => $content->footerSections(),
                'storefrontSocialLinks' => $content->socialLinks(),
                'storefrontContent' => $content,
            ]);
        });
    }
}
