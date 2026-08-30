<?php

namespace App\Domain\Account;

final readonly class DefinicionTipoCuenta
{
    /**
     * @param  list<string>  $monedasElegibles
     */
    public function __construct(
        public string $identificador,
        public string $nombreVisible,
        public array $monedasElegibles,
        public string $politicaSobregiro,
    ) {}
}
