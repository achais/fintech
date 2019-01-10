<?php

/*
 * This file is part of the achais/fintech.
 *
 * (c) achais.zheng <achais.zheng@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Achais\FinTech\Tests;

use Achais\FinTech\Calculator;
use Achais\FinTech\Investment;
use Achais\FinTech\Product;
use Achais\FinTech\Summary;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    protected $product;

    protected $investment;

    protected function getProduct()
    {
        if (!$this->product) {
            $rate = 8.2;
            $loanTerm = 24;
            $termType = Product::TERM_TYPE_MONTH;
            $foundDate = Carbon::make('2019-01-04');
            $repayMode = Product::REPAY_MODE_NATURAL_QUARTER;
            $repayDay = 10;
            $repayMonth = 6;
            $advanceInterest = false;

            $product = new Product();
            $product->init($rate, $loanTerm, $repayMode, $foundDate, $termType, $repayDay, $repayMonth, $advanceInterest);

            $this->product = $product;
        }

        return $this->product;
    }

    protected function getInvestment()
    {
        if (!$this->investment) {
            $investDateTime = Carbon::make('2019-12-28 12:00:00');
            $amount = 50000;

            $investment = new Investment($investDateTime, $amount);

            $this->investment = $investment;
        }

        return $this->investment;
    }

    public function testCreateCalculator()
    {
        $product = $this->getProduct();
        $calculator = new Calculator($product);

        $this->assertInstanceOf(Calculator::class, $calculator);
        $this->assertInstanceOf(Product::class, $calculator->getProduct());
    }

    public function testCalcRepayment()
    {
        $product = $this->getProduct();
        $investment = $this->getInvestment();

        $calculator = new Calculator($product);
        $repaymentList = $calculator->getRepaymentList($investment);

        print_r(PHP_EOL);
        print_r('产品成立时间: '.$product->getFoundDate().PHP_EOL);
        print_r('产品到期时间: '.$product->getEndDate().PHP_EOL);
        print_r('产品实际天数: '.$product->getLoanTermDays().PHP_EOL);
        print_r('产品利率: '.$product->getRate().'%'.PHP_EOL);
        print_r('产品兑付方式: '.$product->getRepayModeName().PHP_EOL);
        print_r('指定兑付月: '.$product->getRepayMonth().PHP_EOL);
        print_r('指定兑付日: '.$product->getRepayDay().PHP_EOL);

        printf(PHP_EOL);
        printf('认购金额: %s'.PHP_EOL, $investment->getAmount());
        printf('认购时间: %s'.PHP_EOL, $investment->getInvestDateTime()->toDateTimeString());
        printf(PHP_EOL);

        foreach ($repaymentList as $repayment) {
            printf('兑付时间点: %s | 计息天数: %s | 兑付利息: %s | 加息天数: %s | 加息金额: %s | 本金: %s | 总金额: %s'.PHP_EOL,
                $repayment->getRepaymentDate(),
                $repayment->getDays(),
                $repayment->getRepaymentInterest(),
                $repayment->getExtraDays(),
                $repayment->getExtraRepaymentInterest(),
                $repayment->getRepaymentInvestmentAmount(),
                $repayment->getTotalRepaymentAmount()
            );
        }

        $this->assertCount(9, $repaymentList);
    }

    public function testCalcSummary()
    {
        $product = $this->getProduct();
        $investment = $this->getInvestment();

        $calculator = new Calculator($product);
        $summary = $calculator->getRepaymentSummary($investment);

        /*
        print_r(PHP_EOL);
        printf('产品到期时间: %s | 赚取金额: %s | 合计: %s'.PHP_EOL,
            $summary->getEndDate(),
            $summary->getTotalInterest(),
            $summary->getTotalAmount()
        );
        */

        $this->assertInstanceOf(Summary::class, $summary);
    }
}
