<?php

namespace App\Application\Account;

interface PoliticaAutorizacion
{
    public function autorizar(int $actorId): void;
}
