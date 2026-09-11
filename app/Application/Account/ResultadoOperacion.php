<?php

namespace App\Application\Account;

readonly class ResultadoOperacion
{
    private function __construct(public ?int $transactionId, public ?TipoFalloOperacion $failure) {}

    public static function committed(int $transactionId): self
    {
        return new self($transactionId, null);
    }

    public static function rejected(TipoFalloOperacion $failure): self
    {
        return new self(null, $failure);
    }

    public function isCommitted(): bool
    {
        return $this->transactionId !== null;
    }
}
