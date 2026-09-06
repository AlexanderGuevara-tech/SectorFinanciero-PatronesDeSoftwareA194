<?php

namespace App\Domain\Account;

final readonly class PoliticaComisionPorFamilia implements PoliticaComision
{
    public function __construct(private string $identificadorFamilia) {}

    public function familia(): string
    {
        return $this->identificadorFamilia;
    }
}
