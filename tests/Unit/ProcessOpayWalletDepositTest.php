<?php

namespace Tests\Unit;

use App\Jobs\ProcessOpayWalletDeposit;
use PHPUnit\Framework\TestCase;

class ProcessOpayWalletDepositTest extends TestCase
{
    /** @dataProvider amountProvider */
    public function test_it_normalizes_opay_amounts($input, float $expected): void
    {
        $this->assertSame($expected, ProcessOpayWalletDeposit::parseAmount($input));
    }

    public function amountProvider(): array
    {
        return [
            'comma-separated amount' => ['1,000.00', 1000.00],
            'plain decimal amount' => ['1000.00', 1000.00],
            'numeric amount' => [1000.0, 1000.00],
            'currency-decorated amount' => ['NGN 1,000.00', 1000.00],
            'invalid amount' => ['not-an-amount', 0.00],
        ];
    }
}
