<?php

namespace Tests\Unit\Domain\Account;

use App\Domain\Account\CatalogoTiposCuentaEstatico;
use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\CuentaCorriente;
use App\Domain\Account\FabricaDeCuentasPorCatalogo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FabricaDeCuentasTest extends TestCase
{
    /**
     * Debería crear CuentaAhorro (sin sobregiro) para tipo savings.
     */
    #[Test]
    public function test_creates_savings_account_with_no_overdraft(): void
    {
        $fabrica = new FabricaDeCuentasPorCatalogo(new CatalogoTiposCuentaEstatico);

        $producto = $fabrica->crear('savings');

        $this->assertInstanceOf(CuentaAhorro::class, $producto);
        $this->assertFalse($producto->permiteSobregiro());
    }

    /**
     * Debería crear CuentaCorriente (con sobregiro permitido) para tipo checking.
     */
    #[Test]
    public function test_creates_checking_account_with_overdraft_allowed(): void
    {
        $fabrica = new FabricaDeCuentasPorCatalogo(new CatalogoTiposCuentaEstatico);

        $producto = $fabrica->crear('checking');

        $this->assertInstanceOf(CuentaCorriente::class, $producto);
        $this->assertTrue($producto->permiteSobregiro());
    }

    /**
     * Debería devolver nulo para un tipo desconocido sin crear producto.
     */
    #[Test]
    public function test_returns_null_for_unknown_type(): void
    {
        $fabrica = new FabricaDeCuentasPorCatalogo(new CatalogoTiposCuentaEstatico);

        $producto = $fabrica->crear('business');

        $this->assertNull($producto);
    }
}
