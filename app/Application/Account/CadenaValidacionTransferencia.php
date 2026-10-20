<?php

namespace App\Application\Account;

final class CadenaValidacionTransferencia
{
    /** @param list<EspecificacionTransferencia> $especificaciones */
    public function __construct(private array $especificaciones) {}

    public function validar(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion
    {
        return (new EspecificacionCompuestaTransferencia($this->especificaciones))->fallo($contexto);
    }
}
