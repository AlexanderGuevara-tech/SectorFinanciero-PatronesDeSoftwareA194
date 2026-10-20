<?php

namespace App\Application\Account;

final class EspecificacionCompuestaTransferencia implements EspecificacionTransferencia
{
    /** @param list<EspecificacionTransferencia> $especificaciones */
    public function __construct(private array $especificaciones) {}

    public function fallo(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
    {
        foreach ($this->especificaciones as $especificacion) {
            $fallo = $especificacion->fallo($contexto);
            if ($fallo !== null) {
                return $fallo;
            }
        }

        return null;
    }
}
