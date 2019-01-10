<?php
/**
 * Created by PhpStorm.
 * User: achais
 * Date: 2019/1/7
 * Time: 9:30 PM
 */

namespace Achais\FinTech\Tests;

use Achais\FinTech\Exceptions\InternalException;
use Achais\FinTech\Product;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testCreateProduct()
    {
        $rate = 8.0;
        $loanTerm = 12;
        $termType = Product::TERM_TYPE_MONTH;
        $foundDate = Carbon::create('2019-01-01');
        $repayMode = Product::REPAY_MODE_NATURAL_MONTH;

        $product = new Product();
        $product->init($rate, $loanTerm, $repayMode, $foundDate, $termType);

        $this->assertInstanceOf(Product::class, $product);
    }

    public function testValidateProduct()
    {
        $product = new Product();

        $this->expectException(InternalException::class);
        $this->expectExceptionMessage('未初始化产品参数');

        $product->validate();

        $this->fail('Failed to assert testValidateProduct throw exception with internal exception.');
    }

    public function testGenerateRepaymentTimeline()
    {
        $rate = 8.0;
        $loanTerm = 24;
        $termType = Product::TERM_TYPE_MONTH;
        $foundDate = Carbon::create('2019-07-08');
        $repayMode = Product::REPAY_MODE_NATURAL_QUARTER;
        $repayDay = 20;
        $repayMonth = 6;

        $product = new Product();
        $product->init($rate, $loanTerm, $repayMode, $foundDate, $termType, $repayDay, $repayMonth);

        $timeline = $product->generateRepaymentTimeline();

        /*
        printf(PHP_EOL);
        printf('产品成立时间: ' . $product->getFoundDate() . PHP_EOL);
        printf('产品到期时间: ' . $product->getEndDate() . PHP_EOL);
        printf('产品实际天数: ' . $product->getLoanTermDays() . PHP_EOL);
        printf('产品兑付方式: ' . $product->getRepayModeName() . PHP_EOL);
        printf('指定兑付月: ' . $product->getRepayMonth() . PHP_EOL);
        printf('指定兑付日: ' . $product->getRepayDay() . PHP_EOL);
        printf('回款时间列表: ' . PHP_EOL);
        foreach ($timeline as $item) {
            printf($item->toDateString() . PHP_EOL);
        }
        */

        $this->assertCount(9, $timeline);
    }
}