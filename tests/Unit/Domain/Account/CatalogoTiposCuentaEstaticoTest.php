<?php

namespace Tests\Unit\Domain\Account;

use App\Domain\Account\CatalogoTiposCuentaEstatico;
use App\Domain\Account\DefinicionTipoCuenta;
use PHPUnit\Framework\TestCase;

class CatalogoTiposCuentaEstaticoTest extends TestCase
{
    public function test_it_exposes_exactly_the_supported_account_types_and_metadata(): void
    {
        $catalogo = new CatalogoTiposCuentaEstatico;

        $definiciones = $catalogo->listar();

        $this->assertCount(2, $definiciones);
        $this->assertSame(['savings', 'checking'], array_map(
            fn (DefinicionTipoCuenta $definicion): string => $definicion->identificador,
            $definiciones,
        ));
        $this->assertSame([
            'savings' => ['Savings Account', ['COP', 'USD'], 'not_allowed'],
            'checking' => ['Checking Account', ['COP', 'USD'], 'allowed'],
        ], array_reduce($definiciones, function (array $metadatos, DefinicionTipoCuenta $definicion): array {
            $metadatos[$definicion->identificador] = [
                $definicion->nombreVisible,
                $definicion->monedasElegibles,
                $definicion->politicaSobregiro,
            ];

            return $metadatos;
        }, []));
    }

    public function test_known_lookups_return_stable_definitions(): void
    {
        $catalogo = new CatalogoTiposCuentaEstatico;

        $ahorros = $catalogo->buscar('savings');
        $corriente = $catalogo->buscar('checking');

        $this->assertSame($ahorros, $catalogo->buscar('savings'));
        $this->assertSame($corriente, $catalogo->buscar('checking'));
        $this->assertSame('Savings Account', $ahorros?->nombreVisible);
        $this->assertSame('Checking Account', $corriente?->nombreVisible);
    }

    public function test_unknown_lookup_returns_null_without_a_fallback_definition(): void
    {
        $catalogo = new CatalogoTiposCuentaEstatico;

        $this->assertNull($catalogo->buscar('business'));
        $this->assertSame(['savings', 'checking'], array_map(
            fn (DefinicionTipoCuenta $definicion): string => $definicion->identificador,
            $catalogo->listar(),
        ));
    }

    public function test_returned_definitions_cannot_mutate_catalog_data(): void
    {
        $catalogo = new CatalogoTiposCuentaEstatico;
        $definiciones = $catalogo->listar();
        $definicion = $catalogo->buscar('savings');
        $mutacionRechazada = false;

        array_pop($definiciones);

        try {
            $definicion->monedasElegibles[] = 'EUR';
        } catch (\Error) {
            $mutacionRechazada = true;
        }

        $this->assertTrue($mutacionRechazada);
        $this->assertSame(['COP', 'USD'], $catalogo->buscar('savings')?->monedasElegibles);
        $this->assertSame(['savings', 'checking'], array_map(
            fn (DefinicionTipoCuenta $definicion): string => $definicion->identificador,
            $catalogo->listar(),
        ));
        $this->assertSame([], array_filter(
            get_class_methods(CatalogoTiposCuentaEstatico::class),
            fn (string $metodo): bool => in_array($metodo, ['create', 'update', 'delete'], true),
        ));
    }

    public function test_catalog_exposes_reference_metadata_without_financial_operations_or_state(): void
    {
        $catalogo = new CatalogoTiposCuentaEstatico;

        $this->assertSame(['__construct', 'listar', 'buscar'], get_class_methods($catalogo));
        $this->assertSame(['savings', 'checking'], array_map(
            fn (DefinicionTipoCuenta $definicion): string => $definicion->identificador,
            $catalogo->listar(),
        ));
        $this->assertNull($catalogo->buscar('account'));
    }
}
