<?php

namespace App\Domain\Loan;

interface RepositorioPrestamos
{
    public function porClave(string $requestKey): ?Prestamo;

    public function porIdBloqueado(int $id): ?Prestamo;

    public function solicitar(int $borrowerId, int $destinationAccountId, int $applicantId, DefinicionProductoPrestamo $product, string $principal, string $requestKey): Prestamo;

    public function actualizarEstado(int $id, string $status, ?int $approvedBy = null, ?int $transactionId = null): void;
}
