<?php

namespace App\Infrastructure\Persistence;

use App\Application\Account\AlcanceClientes;
use Illuminate\Support\Facades\DB;

final class AlcanceClientesOperador implements AlcanceClientes
{
    public function contiene(int $actorId, int $customerId): bool
    {
        return DB::table('accounts')
            ->where('operado_por', $actorId)
            ->where('customer_id', $customerId)
            ->exists();
    }
}
