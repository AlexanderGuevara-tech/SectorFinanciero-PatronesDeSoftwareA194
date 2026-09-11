<?php

namespace App\Infrastructure\Persistence;

use App\Application\Account\PoliticaAutorizacion;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class PoliticaAutorizacionUsuario implements PoliticaAutorizacion
{
    public function autorizar(int $actorId): void
    {
        $usuario = User::find($actorId);

        if ($usuario === null) {
            throw new \RuntimeException('Officer not found.');
        }

        Gate::forUser($usuario)->authorize('manage-accounts');
    }
}
