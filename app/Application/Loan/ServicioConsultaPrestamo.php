<?php

namespace App\Application\Loan;

use App\Domain\Loan\Prestamo;
use App\Domain\Loan\RepositorioPrestamos;
use App\Models\User;

final class ServicioConsultaPrestamo implements ConsultaPrestamo
{
    public function __construct(private RepositorioPrestamos $loans) {}

    public function porId(User $actor, int $loanId): ?Prestamo
    {
        return $this->loans->porId($loanId);
    }
}
