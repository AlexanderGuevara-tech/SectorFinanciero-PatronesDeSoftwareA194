<?php

namespace App\Application\Account;

interface EspecificacionTransferencia
{
    public function fallo(ContextoValidacionTransferencia $contexto): ?TipoFalloOperacion;
}
