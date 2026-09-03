<?php

namespace Tests\Unit\Domain\Account;

use App\Domain\Account\Cuenta;
use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\CuentaCorriente;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\Moneda;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CuentaTest extends TestCase
{
    /**
     * Debería crear una cuenta ahorro con saldo cero y estado activa.
     */
    #[Test]
    public function test_creates_savings_account_with_zero_balance(): void
    {
        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            userId: 1,
            producto: new CuentaAhorro,
        );

        $this->assertSame('0', $cuenta->saldo());
        $this->assertSame('COP', $cuenta->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Activa, $cuenta->estado());
        $this->assertSame('savings', $cuenta->tipo());
        $this->assertSame(1, $cuenta->userId());
    }

    /**
     * Debería rechazar saldo negativo en cuenta de ahorros.
     */
    #[Test]
    public function test_savings_account_rejects_negative_balance(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            userId: 1,
            producto: new CuentaAhorro,
        );

        $cuenta->aplicarSaldo('-100.00');
    }

    /**
     * Debería permitir saldo positivo en cuenta de ahorros.
     */
    #[Test]
    public function test_savings_account_allows_positive_balance(): void
    {
        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            userId: 1,
            producto: new CuentaAhorro,
        );

        $cuenta->aplicarSaldo('1500.50');

        $this->assertSame('1500.50', $cuenta->saldo());
    }

    /**
     * Debería permitir saldo negativo en cuenta corriente dentro del límite.
     */
    #[Test]
    public function test_checking_account_allows_negative_balance_within_limit(): void
    {
        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'checking',
            userId: 1,
            producto: new CuentaCorriente,
        );

        $cuenta->aplicarSaldo('-500.00');

        $this->assertSame('-500.00', $cuenta->saldo());
    }

    /**
     * Debería rechazar saldo negativo excesivo en cuenta corriente.
     */
    #[Test]
    public function test_checking_account_rejects_excessive_overdraft(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'checking',
            userId: 1,
            producto: new CuentaCorriente,
        );

        $cuenta->aplicarSaldo('-1500.00');
    }

    /**
     * Debería representar saldo como string, nunca como float.
     */
    #[Test]
    public function test_balance_is_always_a_string_never_float(): void
    {
        $cuenta = new Cuenta(
            saldo: '1500.50',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'checking',
            userId: 1,
            producto: new CuentaCorriente,
        );

        $this->assertIsString($cuenta->saldo());
        $this->assertSame('1500.50', $cuenta->saldo());
    }

    /**
     * Debería transicionar a estado bloqueada.
     */
    #[Test]
    public function test_can_transition_to_blocked_state(): void
    {
        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            userId: 1,
            producto: new CuentaAhorro,
        );

        $cuenta->bloquear();

        $this->assertSame(EstadoCuenta::Bloqueada, $cuenta->estado());
        $this->assertFalse($cuenta->estado()->permiteEscritura());
    }

    /**
     * Debería transicionar a estado activa desde bloqueada.
     */
    #[Test]
    public function test_can_transition_to_active_from_blocked(): void
    {
        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Bloqueada,
            tipo: 'savings',
            userId: 1,
            producto: new CuentaAhorro,
        );

        $cuenta->desbloquear();

        $this->assertSame(EstadoCuenta::Activa, $cuenta->estado());
        $this->assertTrue($cuenta->estado()->permiteEscritura());
    }
}
