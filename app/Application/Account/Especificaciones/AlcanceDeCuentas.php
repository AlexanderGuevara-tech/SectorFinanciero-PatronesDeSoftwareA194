<?php

namespace App\Application\Account\Especificaciones;

use App\Application\Account\ContextoValidacionTransferencia;
use App\Application\Account\EspecificacionTransferencia;
use App\Application\Account\TipoFalloOperacion;

final class AlcanceDeCuentas implements EspecificacionTransferencia
{
    public function fallo(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
    {
        $source = $contexto->cuentaOrigen();
        $destination = $contexto->cuentaDestino();

        return $contexto->scope()->contiene($contexto->command->actorId, $source->customerId())
            && $contexto->scope()->contiene($contexto->command->actorId, $destination->customerId())
            ? null
            : TipoFalloOperacion::OutOfScope;
    }
}
