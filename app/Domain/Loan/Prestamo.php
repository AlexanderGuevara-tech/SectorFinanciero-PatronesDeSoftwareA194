<?php

namespace App\Domain\Loan;

final readonly class Prestamo
{
    public function __construct(
        public int $id,
        public int $borrowerId,
        public int $destinationAccountId,
        public int $applicantId,
        public DefinicionProductoPrestamo $producto,
        public string $principal,
        public string $requestKey,
        public string $status,
        public ?int $approvedBy,
        public ?int $transactionId,
    ) {}
}
