<?php

namespace App\Providers;

use App\Application\Account\AlcanceClientes;
use App\Application\Account\PoliticaAutorizacion;
use App\Application\Account\RepositorioIdempotencia;
use App\Application\Account\RepositorioLedger;
use App\Application\Account\RepositorioOperaciones;
use App\Application\Account\UnidadDeTrabajo;
use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\CatalogoTiposCuentaEstatico;
use App\Domain\Account\FabricaDeCuentas;
use App\Domain\Account\FabricaDeCuentasPorCatalogo;
use App\Domain\Account\FabricaPaqueteEmpresarial;
use App\Domain\Account\FabricaPaquetePersonal;
use App\Domain\Account\FabricaPaquetesCuentas;
use App\Domain\Account\FabricaPaquetesCuentasPorFamilia;
use App\Domain\Account\RepositorioCuentas;
use App\Domain\Customer\RepositorioClientes;
use App\Infrastructure\Persistence\AlcanceClientesOperador;
use App\Infrastructure\Persistence\PoliticaAutorizacionUsuario;
use App\Infrastructure\Persistence\RepositorioClientesEloquent;
use App\Infrastructure\Persistence\RepositorioCuentasEloquent;
use App\Infrastructure\Persistence\RepositorioIdempotenciaEloquent;
use App\Infrastructure\Persistence\RepositorioLedgerEloquent;
use App\Infrastructure\Persistence\RepositorioOperacionesEloquent;
use App\Infrastructure\Persistence\UnidadDeTrabajoEloquent;
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
        $this->app->bind(FabricaPaquetesCuentas::class, function ($app): FabricaPaquetesCuentas {
            $fabrica = $app->make(FabricaDeCuentas::class);

            return new FabricaPaquetesCuentasPorFamilia([
                new FabricaPaquetePersonal($fabrica),
                new FabricaPaqueteEmpresarial($fabrica),
            ]);
        });
        $this->app->bind(RepositorioCuentas::class, RepositorioCuentasEloquent::class);
        $this->app->bind(RepositorioClientes::class, RepositorioClientesEloquent::class);
        $this->app->bind(PoliticaAutorizacion::class, PoliticaAutorizacionUsuario::class);
        $this->app->bind(AlcanceClientes::class, AlcanceClientesOperador::class);
        $this->app->bind(UnidadDeTrabajo::class, UnidadDeTrabajoEloquent::class);
        $this->app->bind(RepositorioOperaciones::class, RepositorioOperacionesEloquent::class);
        $this->app->bind(RepositorioLedger::class, RepositorioLedgerEloquent::class);
        $this->app->bind(RepositorioIdempotencia::class, RepositorioIdempotenciaEloquent::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-users', fn (User $user): bool => $user->hasPermission('manage-users'));
        Gate::define('view-accounts', fn (User $user): bool => $user->hasPermission('view-accounts'));
        Gate::define('manage-accounts', fn (User $user): bool => $user->hasPermission('manage-accounts'));
        Gate::define('manage-customers', fn (User $user): bool => $user->hasPermission('manage-customers'));
    }
}
