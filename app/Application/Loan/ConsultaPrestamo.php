<?php

namespace App\Application\Loan;

use App\Domain\Loan\Prestamo;
use App\Models\User;

interface ConsultaPrestamo
{
    public function porId(User $actor, int $loanId): ?Prestamo;
}
