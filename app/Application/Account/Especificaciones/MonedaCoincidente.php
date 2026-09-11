<?php

namespace App\Application\Account\Especificaciones;

use App\Application\Account\ContextoValidacionTransferencia;
use App\Application\Account\EspecificacionTransferencia;
use App\Application\Account\TipoFalloOperacion;

final class MonedaCoincidente implements EspecificacionTransferencia
{
    public function fallo(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
    {
        $currency = $contexto->command->currency;

        return $contexto->cuentaOrigen()->moneda()->codigo() === $currency
            && $contexto->cuentaDestino()->moneda()->codigo() === $currency
            ? null
            : TipoFalloOperacion::CurrencyMismatch;
    }
}
