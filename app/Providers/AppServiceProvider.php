<?php

namespace App\Providers;

use App\Models\PengaturanAplikasi;
use App\View\Composers\ContextBarComposer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Share the current PengaturanAplikasi instance with all views, so that we can access it in the sidebar and other places.
        View::composer('*', function ($view) {
            $view->with('pengaturanAplikasi', Cache::rememberForever(
                PengaturanAplikasi::CACHE_KEY,
                fn () => PengaturanAplikasi::current()
            ));
        });

        View::composer([
            'admin.layout.navbar',
            'tim-kerja.layout.navbar',
            'validator.layout.navbar',
        ], ContextBarComposer::class);
        
    }
}
