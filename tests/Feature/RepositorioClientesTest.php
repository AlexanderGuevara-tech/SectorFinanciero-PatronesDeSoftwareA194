<?php

namespace Tests\Feature;

use App\Domain\Customer\Cliente;
use App\Domain\Customer\RepositorioClientes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepositorioClientesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_round_trips_and_searches_a_customer_by_document(): void
    {
        $this->artisan('migrate');
        $repository = app(RepositorioClientes::class);
        $customer = new Cliente('María Gómez', 'CC', '445566', null, null);

        $repository->guardar($customer);
        $found = $repository->buscarPorDocumento('CC', '445566');

        self::assertNotNull($found);
        self::assertSame($customer->id(), $found->id());
        self::assertSame('María Gómez', $found->nombre());
        self::assertNull($found->email());
    }

    #[Test]
    public function it_rejects_duplicate_document_identity(): void
    {
        $this->artisan('migrate');
        $repository = app(RepositorioClientes::class);
        $repository->guardar(new Cliente('One', 'DNI', '7'));

        $this->expectException(UniqueConstraintViolationException::class);
        $repository->guardar(new Cliente('Two', 'DNI', '7'));
    }
}
