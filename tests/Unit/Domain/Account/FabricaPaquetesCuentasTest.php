<?php

namespace Tests\Unit\Domain\Account;

use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\CatalogoTiposCuentaEstatico;
use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\CuentaCorriente;
use App\Domain\Account\DefinicionTipoCuenta;
use App\Domain\Account\FabricaDeCuentasPorCatalogo;
use App\Domain\Account\FabricaPaqueteEmpresarial;
use App\Domain\Account\FabricaPaquetePersonal;
use App\Domain\Account\FabricaPaquetesCuentasPorFamilia;
use App\Domain\Account\PaqueteCuenta;
use App\Domain\Account\PoliticaComisionPorFamilia;
use App\Domain\Account\PoliticaSobregiroPorFamilia;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FabricaPaquetesCuentasTest extends TestCase
{
    #[Test]
    public function it_creates_compatible_products_for_each_family_and_type(): void
    {
        $fabrica = new FabricaPaquetesCuentasPorFamilia([
            new FabricaPaquetePersonal(new FabricaDeCuentasPorCatalogo(new CatalogoTiposCuentaEstatico)),
            new FabricaPaqueteEmpresarial(new FabricaDeCuentasPorCatalogo(new CatalogoTiposCuentaEstatico)),
        ]);

        foreach (['personal', 'empresarial'] as $familia) {
            foreach (['savings', 'checking'] as $tipo) {
                $paquete = $fabrica->crear($familia, $tipo);

                $this->assertSame($familia, $paquete->familia);
                $this->assertSame($familia, $paquete->comision->familia());
                $this->assertSame($familia, $paquete->sobregiro->familia());
                $this->assertSame($familia, $paquete->cuenta->familia());
                $this->assertSame($tipo === 'checking', $paquete->sobregiro->permite($paquete->cuenta));
                $this->assertInstanceOf($tipo === 'checking' ? CuentaCorriente::class : CuentaAhorro::class, $paquete->cuenta);
            }
        }
    }

    #[Test]
    public function it_rejects_unknown_families_and_types_before_composition(): void
    {
        $fabrica = new FabricaPaquetesCuentasPorFamilia([
            new FabricaPaquetePersonal(new FabricaDeCuentasPorCatalogo(new CatalogoTiposCuentaEstatico)),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $fabrica->crear('unknown', 'savings');
    }

    #[Test]
    public function it_rejects_cross_family_products(): void
    {
        $producto = new CuentaCorriente('personal');

        $this->expectException(\InvalidArgumentException::class);

        new PaqueteCuenta(
            familia: 'empresarial',
            cuenta: $producto,
            comision: new PoliticaComisionPorFamilia('empresarial'),
            sobregiro: new PoliticaSobregiroPorFamilia('empresarial'),
        );
    }

    #[Test]
    public function it_rejects_unknown_types_without_composing_policies(): void
    {
        $catalogo = new class implements CatalogoTiposCuenta
        {
            public function listar(): array
            {
                return [];
            }

            public function buscar(string $identificador): ?DefinicionTipoCuenta
            {
                return null;
            }
        };
        $fabrica = new FabricaPaquetePersonal(new FabricaDeCuentasPorCatalogo($catalogo));

        $this->expectException(\InvalidArgumentException::class);
        $fabrica->crear('unknown');
    }

    #[Test]
    public function it_delegates_the_type_unchanged_to_the_existing_factory_method(): void
    {
        $tipos = [];
        $catalogo = new class($tipos) implements CatalogoTiposCuenta
        {
            public function __construct(private array &$tipos) {}

            public function listar(): array
            {
                return [];
            }

            public function buscar(string $identificador): ?DefinicionTipoCuenta
            {
                $this->tipos[] = $identificador;

                return new DefinicionTipoCuenta($identificador, $identificador, ['COP'], 'allowed');
            }
        };
        $fabrica = new FabricaPaqueteEmpresarial(new FabricaDeCuentasPorCatalogo($catalogo));

        $fabrica->crear('checking');

        $this->assertSame(['checking'], $tipos);
    }
}
