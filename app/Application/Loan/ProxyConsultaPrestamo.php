<?php

namespace App\Application\Loan;

use App\Application\Account\AlcanceClientes;
use App\Domain\Loan\Prestamo;
use App\Domain\Loan\RepositorioPrestamos;
use App\Models\User;

final class ProxyConsultaPrestamo implements ConsultaPrestamo
{
    public function __construct(
        private ConsultaPrestamo $subject,
        private RepositorioPrestamos $loans,
        private AlcanceClientes $scope,
    ) {}

    public function porId(User $actor, int $loanId): ?Prestamo
    {
        $borrowerId = $this->loans->borrowerIdPorId($loanId);
        if ($borrowerId === null) {
            return null;
        }

        $ownsLoan = $actor->customer_id !== null && (int) $actor->customer_id === $borrowerId;
        if (! $ownsLoan && (! $actor->can('view-loans') || ! $this->scope->contiene((int) $actor->id, $borrowerId))) {
            return null;
        }

        return $this->subject->porId($actor, $loanId);
    }
}
