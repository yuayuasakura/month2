<?php

namespace Tests\Unit;

use App\Services\PriceCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// PriceCalculator のエッジケース（0・端数・大きな数・不正値）
class PriceCalculatorEdgeCaseTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PriceCalculator();
    }

    public function test_default_tax_rate_is_10_percent()
    {
        $this->assertSame(110, $this->calculator->calculateTotal(100, 1));
    }

    public function test_zero_quantity_returns_zero()
    {
        $this->assertSame(0, $this->calculator->calculateTotal(1000, 0));
    }

    public function test_zero_price_returns_zero()
    {
        $this->assertSame(0, $this->calculator->calculateTotal(0, 5));
    }

    public function test_negative_quantity_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price and quantity must be positive');

        $this->calculator->calculateTotal(100, -1);
    }

    public function test_tax_fraction_is_truncated()
    {
        // 99円 × 1個 × 1.1 = 108.9 → 端数切り捨てで 108
        $this->assertSame(108, $this->calculator->calculateTotal(99, 1));
    }

    public function test_custom_tax_rate_8_percent()
    {
        // 軽減税率 8%：1000 × 1.08 = 1080
        $this->assertSame(1080, $this->calculator->calculateTotal(1000, 1, 0.08));
    }

    public function test_large_values_do_not_lose_precision()
    {
        // 100万円 × 1000個 = 10億円 + 税 = 11億円
        $this->assertSame(1_100_000_000, $this->calculator->calculateTotal(1_000_000, 1000));
    }

    public function test_discount_fraction_is_truncated()
    {
        // 999円の 33%引き = 669.33 → 669
        $this->assertSame(669, $this->calculator->applyDiscount(999, 33));
    }

    public function test_discount_on_zero_price_returns_zero()
    {
        $this->assertSame(0, $this->calculator->applyDiscount(0, 50));
    }

    public function test_discount_then_total()
    {
        // 割引してから税込合計を出す組み合わせ：1000円の20%引き=800円 × 2個 × 1.1 = 1760
        $discounted = $this->calculator->applyDiscount(1000, 20);
        $this->assertSame(1760, $this->calculator->calculateTotal($discounted, 2));
    }

    public static function validDiscountProvider(): array
    {
        return [
            '1%'  => [1000, 1, 990],
            '50%' => [1000, 50, 500],
            '99%' => [1000, 99, 10],
        ];
    }

    #[DataProvider('validDiscountProvider')]
    public function test_various_discounts(int $price, int $percent, int $expected)
    {
        $this->assertSame($expected, $this->calculator->applyDiscount($price, $percent));
    }
}
