<?php

namespace Tests\Unit\Application\Account;

use App\Application\Account\ReversarTransferenciaDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReversarTransferenciaTest extends TestCase
{
    #[Test]
    public function it_requires_a_reason_and_has_a_stable_fingerprint(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ReversarTransferenciaDTO(7, 44, '', 'reverse-1');
    }

    #[Test]
    public function it_fingerprints_reason_and_original_operation_without_request_key(): void
    {
        $first = new ReversarTransferenciaDTO(7, 44, 'Customer request', 'reverse-1');
        $second = new ReversarTransferenciaDTO(7, 44, 'Customer request', 'other');

        $this->assertSame($first->fingerprint(), $second->fingerprint());
    }
}
