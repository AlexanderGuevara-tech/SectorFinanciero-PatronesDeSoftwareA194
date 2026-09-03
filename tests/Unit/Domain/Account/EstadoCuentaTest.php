<?php

namespace Tests\Unit\Domain\Account;

use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\Moneda;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EstadoCuentaTest extends TestCase
{
    /**
     * Debería exponer exactamente los tres estados válidos de cuenta.
     */
    #[Test]
    public function test_it_exposes_exactly_the_three_valid_account_states(): void
    {
        $estados = EstadoCuenta::cases();

        $this->assertCount(3, $estados);
        $this->assertSame(['activa', 'bloqueada', 'cerrada'], array_map(
            fn (EstadoCuenta $estado): string => $estado->value,
            $estados,
        ));
    }

    /**
     * Debería permitir escritura solo cuando el estado es activa.
     */
    #[Test]
    public function test_permits_writing_only_when_state_is_activa(): void
    {
        $this->assertTrue(EstadoCuenta::Activa->permiteEscritura());
        $this->assertFalse(EstadoCuenta::Bloqueada->permiteEscritura());
        $this->assertFalse(EstadoCuenta::Cerrada->permiteEscritura());
    }

    /**
     * Debería canonicar COP como moneda por defecto ISO 4217.
     */
    #[Test]
    public function test_canonical_cop_is_the_default_iso_4217_currency(): void
    {
        $cop = Moneda::default();

        $this->assertSame('COP', $cop->codigo());
        $this->assertSame('COP', Moneda::COP()->codigo());
    }

    /**
     * Debería rechazar monedas que no cumplan ISO 4217.
     */
    #[Test]
    public function test_rejects_non_iso_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Moneda('INVALID');
    }

    /**
     * Debería aceptar USD como moneda válida ISO 4217.
     */
    #[Test]
    public function test_accepts_usd_as_valid_iso_4217_currency(): void
    {
        $usd = new Moneda('USD');

        $this->assertSame('USD', $usd->codigo());
    }
}
