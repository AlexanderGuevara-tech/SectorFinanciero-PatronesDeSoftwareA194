<?php

namespace App\Application\Account;

interface AlcanceClientes
{
    public function contiene(int $actorId, int $customerId): bool;
}
