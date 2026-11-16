<?php

namespace Tests\Unit;

use App\Services\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PriceCalculator();
    }

    public function test_calculate_total_without_tax()
    {
        $result = $this->calculator->calculateTotal(100, 2, 0);
        $this->assertEquals(200, $result);
    }

    public function test_calculate_total_with_tax()
    {
        $result = $this->calculator->calculateTotal(100, 2, 0.1);
        $this->assertEquals(220, $result);
    }

    public function test_calculate_total_throws_exception_for_negative_price()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price and quantity must be positive');

        $this->calculator->calculateTotal(-100, 2);
    }

    public function test_apply_discount()
    {
        $result = $this->calculator->applyDiscount(1000, 20);
        $this->assertEquals(800, $result);
    }

    public function test_apply_discount_throws_exception_for_invalid_percent()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->applyDiscount(1000, 150);
    }
}
