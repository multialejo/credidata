<?php

namespace App\Services;

use InvalidArgumentException;

class CreditPurchaseCalculator
{
    public function amountInCents(string|int|float $amountUsd): int
    {
        $amount = (string) $amountUsd;

        if (! preg_match('/^(0|[1-9]\d*)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException('The amount must have at most two decimal places.');
        }

        $wholeDollars = (int) $matches[1];
        $cents = isset($matches[2]) ? (int) str_pad($matches[2], 2, '0') : 0;

        return $wholeDollars * 100 + $cents;
    }

    public function creditsForCents(int $amountCents, int $creditsPerUsd): ?int
    {
        if ($amountCents <= 0 || $creditsPerUsd <= 0) {
            return null;
        }

        $creditCents = $amountCents * $creditsPerUsd;

        if ($creditCents % 100 !== 0) {
            return null;
        }

        return intdiv($creditCents, 100);
    }

    public function formatCentsAsUsd(int $amountCents): string
    {
        return intdiv($amountCents, 100).'.'.str_pad((string) ($amountCents % 100), 2, '0', STR_PAD_LEFT);
    }
}
