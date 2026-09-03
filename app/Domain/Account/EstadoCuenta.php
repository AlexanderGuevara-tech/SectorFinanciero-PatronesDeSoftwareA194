<?php

namespace App\Domain\Account;

enum EstadoCuenta: string
{
    case Activa = 'activa';
    case Bloqueada = 'bloqueada';
    case Cerrada = 'cerrada';

    public function permiteEscritura(): bool
    {
        return $this === self::Activa;
    }
}
