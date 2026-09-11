<?php

namespace Tests\Unit\Application\Account;

use App\Application\Account\AlcanceClientes;
use App\Application\Account\CadenaValidacionTransferencia;
use App\Application\Account\ContextoValidacionTransferencia;
use App\Application\Account\Especificaciones\AlcanceDeCuentas;
use App\Application\Account\Especificaciones\CuentasExistentes;
use App\Application\Account\Especificaciones\MismaCuenta;
use App\Application\Account\Especificaciones\MonedaCoincidente;
use App\Application\Account\Especificaciones\SaldoSuficiente;
use App\Application\Account\EspecificacionTransferencia;
use App\Application\Account\TipoFalloOperacion;
use App\Application\Account\TransferirFondosDTO;
use App\Domain\Account\Cuenta;
use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\Moneda;
use App\Domain\Account\RepositorioCuentas;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransferenciaValidationTest extends TestCase
{
    #[Test]
    public function it_rejects_the_same_account_without_loading_accounts(): void
    {
        $loaded = false;
        $context = $this->context($this->command(10, 10), $loaded);

        $failure = (new MismaCuenta)->fallo($context);

        $this->assertSame(TipoFalloOperacion::SameAccount, $failure);
        $this->assertFalse($loaded);
    }

    #[Test]
    public function it_rejects_missing_accounts_after_the_locked_load(): void
    {
        $loaded = false;
        $context = $this->context($this->command(), $loaded, []);

        $failure = (new CuentasExistentes)->fallo($context);

        $this->assertSame(TipoFalloOperacion::AccountNotFound, $failure);
        $this->assertTrue($loaded);
    }

    #[Test]
    public function it_rejects_accounts_outside_the_actor_scope(): void
    {
        $context = $this->context($this->command(), $loaded, scope: false);

        $failure = (new AlcanceDeCuentas)->fallo($context);

        $this->assertSame(TipoFalloOperacion::OutOfScope, $failure);
    }

    #[Test]
    public function it_rejects_currency_mismatches(): void
    {
        $context = $this->context($this->command(currency: 'USD'));

        $failure = (new MonedaCoincidente)->fallo($context);

        $this->assertSame(TipoFalloOperacion::CurrencyMismatch, $failure);
    }

    #[Test]
    public function it_rejects_transfers_that_exceed_the_source_balance(): void
    {
        $context = $this->context($this->command(amount: '101.00'));

        $failure = (new SaldoSuficiente)->fallo($context);

        $this->assertSame(TipoFalloOperacion::InsufficientBalance, $failure);
    }

    #[Test]
    public function it_short_circuits_specifications_in_declared_order(): void
    {
        $calls = [];
        $first = new class($calls) implements EspecificacionTransferencia
        {
            public function __construct(private array &$calls) {}

            public function fallo(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
            {
                $this->calls[] = 'first';

                return TipoFalloOperacion::SameAccount;
            }
        };
        $second = new class($calls) implements EspecificacionTransferencia
        {
            public function __construct(private array &$calls) {}

            public function fallo(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
            {
                $this->calls[] = 'second';

                return null;
            }
        };

        $failure = (new CadenaValidacionTransferencia([$first, $second]))->validar(
            $this->context($this->command()),
        );

        $this->assertSame(TipoFalloOperacion::SameAccount, $failure);
        $this->assertSame(['first'], $calls);
    }

    private function context(
        TransferirFondosDTO $command,
        ?bool &$loaded = null,
        ?array $accounts = null,
        bool $scope = true,
    ): ContextoValidacionTransferencia {
        $loaded ??= false;
        $accounts ??= [$this->account(10, '100.00'), $this->account(20, '0.00')];

        return new ContextoValidacionTransferencia(
            $command,
            new class($loaded, $accounts) implements RepositorioCuentas
            {
                public function __construct(private bool &$loaded, private array $accounts) {}

                public function guardar(Cuenta $cuenta): void {}

                public function porId(int $id): ?Cuenta
                {
                    return null;
                }

                public function porCliente(int $customerId): array
                {
                    return [];
                }

                public function todos(): array
                {
                    return [];
                }

                public function porIdYCliente(int $id, int $customerId): ?Cuenta
                {
                    return null;
                }

                public function porIdsBloqueadas(array $ids): array
                {
                    $this->loaded = true;

                    return $this->accounts;
                }
            },
            new class($scope) implements AlcanceClientes
            {
                public function __construct(private bool $allowed) {}

                public function contiene(int $actorId, int $customerId): bool
                {
                    return $this->allowed;
                }
            },
        );
    }

    private function command(
        int $sourceAccountId = 10,
        int $destinationAccountId = 20,
        string $currency = 'COP',
        string $amount = '25.00',
    ): TransferirFondosDTO {
        return new TransferirFondosDTO(7, $sourceAccountId, $destinationAccountId, $currency, $amount, 'test-key');
    }

    private function account(int $id, string $balance): Cuenta
    {
        return new Cuenta(
            saldo: $balance,
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            customerId: $id,
            operadoPorId: 7,
            producto: new CuentaAhorro('personal'),
            id: $id,
        );
    }
}
