<?php

namespace App\Providers;

use App\Models\PengaturanAplikasi;
use App\Services\Pddikti\Lldikti12PtsDataSource;
use App\Services\Pddikti\PddiktiPtsDataSource;
use App\Services\Pddikti\PtsDataSource;
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
        // Pilih implementasi sumber data PTS berdasarkan driver PDDIKTI yang dikonfigurasi.
        $this->app->bind(PtsDataSource::class, function () {
            $driver = config('pddikti.driver');

            return match ($driver) {
                'lldikti12' => new Lldikti12PtsDataSource(config('pddikti.drivers.lldikti12')),
                'pddikti' => new PddiktiPtsDataSource(),
                default => throw new \InvalidArgumentException(
                    "PDDIKTI_DRIVER \"{$driver}\" tidak dikenal. Gunakan: lldikti12 atau pddikti."
                ),
            };
        });
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

        // Share the current context (role) with the navbar views, so that we can highlight the correct menu item based on the user's role.
        View::composer([
            'admin.layout.navbar',
            'tim-kerja.layout.navbar',
            'validator.layout.navbar',
            'pimpinan.layout.navbar',
        ], ContextBarComposer::class);
        
    }
}
