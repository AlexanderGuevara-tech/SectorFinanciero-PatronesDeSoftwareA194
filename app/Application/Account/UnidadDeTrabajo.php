<?php

namespace App\Application\Account;

interface UnidadDeTrabajo
{
    public function ejecutar(callable $operacion): mixed;
}
