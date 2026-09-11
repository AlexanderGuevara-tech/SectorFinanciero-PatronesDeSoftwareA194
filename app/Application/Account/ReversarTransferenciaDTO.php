<?php

namespace App\Application\Account;

readonly class ReversarTransferenciaDTO
{
    public function __construct(
        public int $actorId,
        public int $transactionId,
        public string $reason,
        public string $requestKey,
    ) {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Reversal reason is required.');
        }
        if ($requestKey === '') {
            throw new \InvalidArgumentException('Request key is required.');
        }
    }

    public function fingerprint(): string
    {
        return hash('sha256', implode('|', ['reversal', $this->actorId, $this->transactionId, trim($this->reason)]));
    }
}
