<?php

namespace App\Application\Account;

use App\Domain\Account\Cuenta;
use App\Domain\Account\RepositorioCuentas;

final class ListarCuentas
{
    public function __construct(
        private RepositorioCuentas $repositorio,
    ) {}

    /**
     * @return list<Cuenta>
     */
    public function ejecutar(int $userId, bool $esAdministrador): array
    {
        return $esAdministrador
            ? $this->repositorio->todos()
            : $this->repositorio->porUsuario($userId);
    }
}
