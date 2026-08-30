<?php

namespace Tests\Feature;

use App\Domain\Account\CatalogoTiposCuenta;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class CatalogoTiposCuentaContenedorTest extends TestCase
{
    public function test_the_catalog_contract_resolves_as_a_singleton(): void
    {
        $primeraResolucion = $this->app->make(CatalogoTiposCuenta::class);
        $segundaResolucion = $this->app->make(CatalogoTiposCuenta::class);

        $this->assertSame($primeraResolucion, $segundaResolucion);
        $this->assertSame(
            ['savings', 'checking'],
            array_map(fn ($definicion): string => $definicion->identificador, $primeraResolucion->listar()),
        );
        $this->assertSame($primeraResolucion->buscar('checking'), $segundaResolucion->buscar('checking'));
    }

    public function test_reference_metadata_is_independent_of_user_and_request_context(): void
    {
        $this->app->instance('request', Request::create('/accounts?tenant=one'));
        $this->be(User::factory()->make(['email' => 'first@example.com']));
        $primeraResolucion = $this->app->make(CatalogoTiposCuenta::class)->buscar('savings');

        $this->app->instance('request', Request::create('/accounts?tenant=two'));
        $this->be(User::factory()->make(['email' => 'second@example.com']));
        $segundaResolucion = $this->app->make(CatalogoTiposCuenta::class)->buscar('savings');

        $this->assertSame($primeraResolucion?->identificador, $segundaResolucion?->identificador);
        $this->assertSame($primeraResolucion?->nombreVisible, $segundaResolucion?->nombreVisible);
        $this->assertSame($primeraResolucion?->monedasElegibles, $segundaResolucion?->monedasElegibles);
        $this->assertSame($primeraResolucion?->politicaSobregiro, $segundaResolucion?->politicaSobregiro);
    }
}
