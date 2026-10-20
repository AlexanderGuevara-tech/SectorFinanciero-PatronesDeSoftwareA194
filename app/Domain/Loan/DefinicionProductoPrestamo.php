<?php

namespace App\Domain\Loan;

final readonly class DefinicionProductoPrestamo
{
    public function __construct(
        public string $id,
        public string $name,
        public string $currency,
        public string $annualRate,
        public int $termMonths,
    ) {
        if ($id === '' || $name === '' || ! in_array($currency, ['COP', 'USD'], true)
            || ! preg_match('/^\d+\.\d{4}$/', $annualRate) || $termMonths < 1) {
            throw new \InvalidArgumentException('Invalid academic loan product definition.');
        }
    }
}
