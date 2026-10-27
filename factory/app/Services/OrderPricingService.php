<?php

namespace App\Services;

class OrderPricingService
{
    /** Pricing formulas are unverified. Authoritative total uses staff-approved manual unit prices. */
    public function lineTotal(string|int $quantity, string|int $unitPrice): string
    {
        return bcmul((string) $quantity, (string) $unitPrice, 2);
    }

    public function orderTotal(array $items): string
    {
        $total = '0.00';
        foreach ($items as $item) $total = bcadd($total, $this->lineTotal($item['quantity'], $item['unit_price']), 2);
        return $total;
    }
}
