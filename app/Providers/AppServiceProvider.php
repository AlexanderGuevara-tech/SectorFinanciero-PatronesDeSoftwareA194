<?php

namespace App\Providers;

use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\CatalogoTiposCuentaEstatico;
use App\Domain\Account\FabricaDeCuentas;
use App\Domain\Account\FabricaDeCuentasPorCatalogo;
use App\Domain\Account\RepositorioCuentas;
use App\Infrastructure\Persistence\RepositorioCuentasEloquent;
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
        $this->app->bind(FabricaDeCuentas::class, FabricaDeCuentasPorCatalogo::class);
        $this->app->bind(RepositorioCuentas::class, RepositorioCuentasEloquent::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-users', fn (User $user): bool => $user->hasPermission('manage-users'));
        Gate::define('view-accounts', fn (User $user): bool => $user->hasPermission('view-accounts'));
        Gate::define('manage-accounts', fn (User $user): bool => $user->hasPermission('manage-accounts'));
    }
}
