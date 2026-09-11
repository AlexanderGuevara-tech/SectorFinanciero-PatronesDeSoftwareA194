<?php

namespace App\Application\Account\Especificaciones;

use App\Application\Account\ContextoValidacionTransferencia;
use App\Application\Account\EspecificacionTransferencia;
use App\Application\Account\TipoFalloOperacion;

final class CuentasExistentes implements EspecificacionTransferencia
{
    public function fallo(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
    {
        return count($contexto->cuentasBloqueadas()) === 2
            ? null
            : TipoFalloOperacion::AccountNotFound;
    }
}
