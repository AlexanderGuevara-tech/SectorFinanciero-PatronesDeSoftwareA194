<?php

namespace App\Application\Account\Especificaciones;

use App\Application\Account\ContextoValidacionTransferencia;
use App\Application\Account\EspecificacionTransferencia;
use App\Application\Account\TipoFalloOperacion;

final class MismaCuenta implements EspecificacionTransferencia
{
    public function fallo(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
    {
        return $contexto->command->sourceAccountId === $contexto->command->destinationAccountId
            ? TipoFalloOperacion::SameAccount
            : null;
    }
}
