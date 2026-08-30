<?php

namespace App\Providers;

use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\CatalogoTiposCuentaEstatico;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CatalogoTiposCuenta::class, CatalogoTiposCuentaEstatico::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-users', fn (User $user): bool => $user->hasPermission('manage-users'));
        Gate::define('view-accounts', fn (User $user): bool => $user->hasPermission('view-accounts'));
    }
}
