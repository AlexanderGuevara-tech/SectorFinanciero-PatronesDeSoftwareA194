<?php

namespace App\Application\Account;

readonly class TransferirFondosDTO
{
    public string $amount;

    public function __construct(
        public int $actorId,
        public int $sourceAccountId,
        public int $destinationAccountId,
        public string $currency,
        string $amount,
        public string $requestKey,
    ) {
        if (! preg_match('/^\d+\.\d{2}$/', $amount) || bccomp($amount, '0.00', 2) <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be a positive decimal with two places.');
        }
        if (! in_array($currency, ['COP', 'USD'], true)) {
            throw new \InvalidArgumentException('Transfer currency is not supported.');
        }
        if ($requestKey === '') {
            throw new \InvalidArgumentException('Request key is required.');
        }

        $this->amount = bcadd($amount, '0.00', 2);
    }

    public function fingerprint(): string
    {
        return hash('sha256', implode('|', [
            'transfer', $this->actorId, $this->sourceAccountId, $this->destinationAccountId,
            $this->currency, $this->amount,
        ]));
    }
}
