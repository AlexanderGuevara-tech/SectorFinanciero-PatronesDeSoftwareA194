<?php

namespace Tests\Unit\Domain\Account;

use App\Domain\Account\Cuenta;
use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\CuentaCorriente;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\Moneda;
use App\Domain\Account\PaqueteCuenta;
use App\Domain\Account\PoliticaComisionPorFamilia;
use App\Domain\Account\PoliticaSobregiroPorFamilia;
use App\Domain\Customer\Cliente;
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
            customerId: 1,
            operadoPorId: 2,
            producto: new CuentaAhorro,
        );

        $this->assertSame('0', $cuenta->saldo());
        $this->assertSame('COP', $cuenta->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Activa, $cuenta->estado());
        $this->assertSame('savings', $cuenta->tipo());
        $this->assertSame(1, $cuenta->customerId());
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
            customerId: 1,
            operadoPorId: 2,
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
            customerId: 1,
            operadoPorId: 2,
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
            customerId: 1,
            operadoPorId: 2,
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
            customerId: 1,
            operadoPorId: 2,
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
            customerId: 1,
            operadoPorId: 2,
            producto: new CuentaCorriente,
        );

        $this->assertIsString($cuenta->saldo());
        $this->assertSame('1500.50', $cuenta->saldo());
    }

    #[Test]
    public function test_carries_the_selected_family_package(): void
    {
        $producto = new CuentaCorriente('empresarial');
        $paquete = new PaqueteCuenta(
            familia: 'empresarial',
            cuenta: $producto,
            comision: new PoliticaComisionPorFamilia('empresarial'),
            sobregiro: new PoliticaSobregiroPorFamilia('empresarial'),
        );

        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'checking',
            customerId: 1,
            operadoPorId: 2,
            producto: $producto,
            familia: 'empresarial',
            paquete: $paquete,
        );

        $this->assertSame('empresarial', $cuenta->familia());
        $this->assertSame($paquete, $cuenta->paquete());
    }

    #[Test]
    public function test_legacy_constructor_defaults_to_personal_family(): void
    {
        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            customerId: 1,
            operadoPorId: 2,
            producto: new CuentaAhorro,
        );

        $this->assertSame('personal', $cuenta->familia());
        $this->assertSame('personal', $cuenta->paquete()->familia);
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
            customerId: 1,
            operadoPorId: 2,
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
            customerId: 1,
            operadoPorId: 2,
            producto: new CuentaAhorro,
        );

        $cuenta->desbloquear();

        $this->assertSame(EstadoCuenta::Activa, $cuenta->estado());
        $this->assertTrue($cuenta->estado()->permiteEscritura());
    }

    #[Test]
    public function test_clones_an_account_with_its_values_and_independent_mutable_state(): void
    {
        $producto = new CuentaCorriente('empresarial');
        $cliente = new Cliente('Ada Lovelace', 'CC', '123', 'ada@example.com');
        $cuenta = new Cuenta(
            saldo: '125.50',
            moneda: new Moneda('USD'),
            estado: EstadoCuenta::Activa,
            tipo: 'checking',
            customerId: 7,
            operadoPorId: 11,
            producto: $producto,
            familia: 'empresarial',
            cliente: $cliente,
        );

        $cuenta->asignarId(19);
        $clon = $cuenta->clonarParaNuevaCuenta();

        $clon->aplicarSaldo('10.00');
        $clon->bloquear();
        $clon->cliente()?->asignarId(20);

        $this->assertSame('125.50', $cuenta->saldo());
        $this->assertSame('135.50', $clon->saldo());
        $this->assertSame(EstadoCuenta::Activa, $cuenta->estado());
        $this->assertSame(EstadoCuenta::Bloqueada, $clon->estado());
        $this->assertSame('USD', $clon->moneda()->codigo());
        $this->assertSame('checking', $clon->tipo());
        $this->assertSame(7, $clon->customerId());
        $this->assertSame(11, $clon->operadoPorId());
        $this->assertSame('empresarial', $clon->familia());
        $this->assertNotSame($cuenta->paquete(), $clon->paquete());
        $this->assertNotSame($cuenta->producto(), $clon->producto());
        $this->assertNotSame($cuenta->cliente(), $clon->cliente());
        $this->assertSame(null, $cuenta->cliente()?->id());
        $this->assertSame(20, $clon->cliente()?->id());
    }

    #[Test]
    public function test_clones_an_account_without_reusing_its_identity(): void
    {
        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            customerId: 1,
            operadoPorId: null,
            producto: new CuentaAhorro,
        );

        $cuenta->asignarId(19);
        $clon = $cuenta->clonarParaNuevaCuenta();
        $clon->asignarId(20);

        $this->assertSame(19, $cuenta->id());
        $this->assertSame(20, $clon->id());
    }
}
