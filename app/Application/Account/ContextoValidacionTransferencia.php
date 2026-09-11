<?php

namespace App\Application\Account;

use App\Domain\Account\Cuenta;
use App\Domain\Account\RepositorioCuentas;

final class ContextoValidacionTransferencia
{
    /** @var list<Cuenta>|null */
    private ?array $cuentas = null;

    public function __construct(
        public readonly TransferirFondosDTO $command,
        private RepositorioCuentas $accounts,
        private AlcanceClientes $scope,
    ) {}

    /** @return list<Cuenta> */
    public function cuentasBloqueadas(): array
    {
        return $this->cuentas ??= $this->accounts->porIdsBloqueadas([
            $this->command->sourceAccountId,
            $this->command->destinationAccountId,
        ]);
    }

    public function scope(): AlcanceClientes
    {
        return $this->scope;
    }

    public function cuentaOrigen(): ?Cuenta
    {
        return $this->cuentaPorId($this->command->sourceAccountId);
    }

    public function cuentaDestino(): ?Cuenta
    {
        return $this->cuentaPorId($this->command->destinationAccountId);
    }

    private function cuentaPorId(int $id): ?Cuenta
    {
        foreach ($this->cuentasBloqueadas() as $account) {
            if ($account->id() === $id) {
                return $account;
            }
        }

        return null;
    }
}
