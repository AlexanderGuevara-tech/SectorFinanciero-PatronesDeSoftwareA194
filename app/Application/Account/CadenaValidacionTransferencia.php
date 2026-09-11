<?php

namespace App\Application\Account;

final class CadenaValidacionTransferencia
{
    /** @param list<EspecificacionTransferencia> $especificaciones */
    public function __construct(private array $especificaciones) {}

    public function validar(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
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
