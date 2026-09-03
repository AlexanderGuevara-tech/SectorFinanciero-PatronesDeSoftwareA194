<?php

namespace App\Domain\Account;

final readonly class Moneda
{
    private const ISO_4217_VALID = ['COP', 'USD'];

    public function __construct(
        private string $codigo,
    ) {
        if (! in_array($codigo, self::ISO_4217_VALID, true)) {
            throw new \InvalidArgumentException("Invalid ISO 4217 currency code: {$codigo}");
        }
    }

    public static function COP(): self
    {
        return new self('COP');
    }

    public static function default(): self
    {
        return self::COP();
    }

    public function codigo(): string
    {
        return $this->codigo;
    }
}
