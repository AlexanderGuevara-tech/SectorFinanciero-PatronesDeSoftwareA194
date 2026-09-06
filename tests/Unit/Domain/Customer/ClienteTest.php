<?php

namespace Tests\Unit\Domain\Customer;

use App\Domain\Customer\Cliente;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClienteTest extends TestCase
{
    #[Test]
    public function it_preserves_customer_identity_and_contact_data(): void
    {
        $cliente = new Cliente('Ana López', 'DNI', '12345678', 'ana@example.com', '3001234567');

        self::assertSame('Ana López', $cliente->nombre());
        self::assertSame('DNI', $cliente->tipoDocumento());
        self::assertSame('12345678', $cliente->numeroDocumento());
        self::assertSame('ana@example.com', $cliente->email());
        self::assertSame('3001234567', $cliente->telefono());
    }

    #[Test]
    public function it_accepts_optional_contact_data_and_an_assigned_id(): void
    {
        $cliente = new Cliente('Luis Pérez', 'CC', '9988');
        $cliente->asignarId(12);

        self::assertNull($cliente->email());
        self::assertNull($cliente->telefono());
        self::assertSame(12, $cliente->id());
    }
}
