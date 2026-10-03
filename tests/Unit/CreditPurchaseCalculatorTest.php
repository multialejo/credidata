<?php

namespace Tests\Unit;

use App\Services\CreditPurchaseCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreditPurchaseCalculatorTest extends TestCase
{
    public function test_calculates_credits_from_cents_without_rounding(): void
    {
        $calculator = new CreditPurchaseCalculator;

        $this->assertSame(100, $calculator->creditsForCents($calculator->amountInCents('10.00'), 10));
        $this->assertSame(35, $calculator->creditsForCents($calculator->amountInCents('5.00'), 7));
        $this->assertSame(1, $calculator->creditsForCents($calculator->amountInCents('0.25'), 4));
    }

    public function test_rejects_amount_when_it_does_not_produce_whole_credits(): void
    {
        $calculator = new CreditPurchaseCalculator;

        $this->assertNull($calculator->creditsForCents($calculator->amountInCents('5.01'), 7));
        $this->assertNull($calculator->creditsForCents($calculator->amountInCents('10.01'), 10));
    }

    #[DataProvider('invalidAmounts')]
    public function test_amounts_must_have_at_most_two_decimal_places(string $amount): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CreditPurchaseCalculator)->amountInCents($amount);
    }

    public static function invalidAmounts(): array
    {
        return [['1.001'], ['-1.00'], ['1,25'], ['01.00']];
    }
}
