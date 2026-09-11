<?php

namespace Tests\Unit\Application\Account;

use App\Application\Account\TipoFalloOperacion;
use App\Application\Account\TransferirFondosDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransferirFondosTest extends TestCase
{
    #[Test]
    public function it_normalizes_a_decimal_command_and_builds_a_stable_fingerprint(): void
    {
        $command = new TransferirFondosDTO(7, 10, 20, 'COP', '0010.50', 'transfer-1');

        $this->assertSame('10.50', $command->amount);
        $this->assertSame($command->fingerprint(), (new TransferirFondosDTO(7, 10, 20, 'COP', '10.50', 'other'))->fingerprint());
    }

    #[Test]
    public function it_rejects_invalid_amounts_and_exposes_failure_codes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TransferirFondosDTO(7, 10, 20, 'COP', '0', 'transfer-1');
    }

    #[Test]
    public function it_rejects_a_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TransferirFondosDTO(7, 10, 20, 'COP', '-1.00', 'transfer-negative');
    }

    #[Test]
    public function it_defines_business_failure_codes_for_command_mapping(): void
    {
        $this->assertSame('insufficient_balance', TipoFalloOperacion::InsufficientBalance->value);
        $this->assertSame('idempotency_conflict', TipoFalloOperacion::IdempotencyConflict->value);
    }
}
