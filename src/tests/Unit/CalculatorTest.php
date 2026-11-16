<?php

namespace Tests\Unit;           // tests/Unit/ フォルダに置くときの名前空間

use PHPUnit\Framework\TestCase; // PHPUnitの基底クラスを読み込む（必須）

class CalculatorTest extends TestCase  // クラス名は「テスト対象名 + Test」
{
    // テストメソッドは必ず「public function test_」で始める
    public function test_addition()
    {
        $result = 2 + 2;
        $this->assertEquals(4, $result); // 期待値=4、実際の値=$result
    }

    public function test_subtraction()
    {
        $result = 5 - 3;
        $this->assertEquals(2, $result);
    }
}
