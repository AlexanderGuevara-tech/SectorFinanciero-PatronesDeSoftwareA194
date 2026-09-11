<?php

namespace App\Infrastructure\Persistence;

use App\Application\Account\UnidadDeTrabajo;
use Illuminate\Support\Facades\DB;

final class UnidadDeTrabajoEloquent implements UnidadDeTrabajo
{
    public function ejecutar(callable $operacion): mixed
    {
        return DB::transaction($operacion);
    }
}
