<?php

namespace App\Domain\Loan;

final class CatalogoProductosPrestamo
{
    /** @var array<string, DefinicionProductoPrestamo> */
    private array $definitions = [];

    /** @param array<string, array{name: string, currency: string, annual_rate: string, term_months: int}> $products */
    public function __construct(array $products)
    {
        foreach ($products as $id => $data) {
            $this->definitions[$id] = new DefinicionProductoPrestamo(
                $id, $data['name'], $data['currency'], $data['annual_rate'], $data['term_months'],
            );
        }
    }

    public function buscar(string $id): ?DefinicionProductoPrestamo
    {
        return $this->definitions[$id] ?? null;
    }
}
