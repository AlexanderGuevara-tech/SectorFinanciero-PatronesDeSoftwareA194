<?php

namespace Tests\Unit\Domain\Loan;

use App\Domain\Loan\CatalogoProductosPrestamo;
use PHPUnit\Framework\TestCase;

final class CatalogoProductosPrestamoTest extends TestCase
{
    public function test_reuses_immutable_definition_for_the_same_product_and_rejects_unknown_ids(): void
    {
        $catalog = new CatalogoProductosPrestamo([
            'academic' => ['name' => 'Academic example', 'currency' => 'COP', 'annual_rate' => '0.0500', 'term_months' => 12],
        ]);
        $product = $catalog->buscar('academic');

        $this->assertSame($product, $catalog->buscar('academic'));
        $this->assertNull($catalog->buscar('unknown'));
        $this->assertSame('0.0500', $product->annualRate);
        $this->expectException(\Error::class);
        $product->annualRate = '0.9000';
    }
}
