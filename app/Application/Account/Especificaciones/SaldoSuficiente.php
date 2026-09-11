<?php

namespace App\Application\Account\Especificaciones;

use App\Application\Account\ContextoValidacionTransferencia;
use App\Application\Account\EspecificacionTransferencia;
use App\Application\Account\TipoFalloOperacion;

final class SaldoSuficiente implements EspecificacionTransferencia
{
    public function fallo(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
    {
        return bccomp($contexto->cuentaOrigen()->saldo(), $contexto->command->amount, 2) >= 0
            ? null
            : TipoFalloOperacion::InsufficientBalance;
    }
}
