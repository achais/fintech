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

use Carbon\Carbon;
use Achais\FinTech\Calculator;
use Achais\FinTech\Investment;
use Achais\FinTech\Product;
use PHPUnit\Framework\TestCase;

class ReadmeTest extends TestCase
{
    public function testReadmeRepaymentList()
    {
        // ====== 产品属性 ======

        $rate = 8.0; // 利率
        $loanTerm = 24; // 产品期限
        $termType = Product::TERM_TYPE_MONTH; // 期限类型(月)
        $foundDate = Carbon::create('2019-07-08'); // 产品成立时间
        $repayMode = Product::REPAY_MODE_NATURAL_QUARTER; // 兑付方式(自然季度付息, 到期还本)
        $repayDay = 20; // 指定兑付日 (仅对自然xx兑付方式有效)
        $repayMonth = 6; // 指定兑付月 (仅对自然xx兑付方式有效)
        $advanceInterest = true; // 是否 T+N 日起息 (默认为产品成立日起息)

        $product = new Product(); // 实例化产品对象
        $product->init($rate, $loanTerm, $repayMode, $foundDate, $termType, $repayDay, $repayMonth, $advanceInterest); // 初始化产品属性

        // ====== 认购属性 ======

        $investDateTime = Carbon::create('2019-07-05 12:00:00'); // 认购时间
        $amount = 10000; // 认购金额
        $investment = new Investment($investDateTime, $amount); // 实例化认购对象

        // ====== 业务 ======

        $calculator = new Calculator($product); // 实例化计算器对象(注入产品)
        $repaymentList = $calculator->getRepaymentList($investment); //获取兑付列表(注入认购对象)

        print_r(PHP_EOL);
        print_r('产品成立时间: '.$product->getFoundDate().PHP_EOL);
        print_r('产品到期时间: '.$product->getEndDate().PHP_EOL);
        print_r('产品实际天数: '.$product->getLoanTermDays().PHP_EOL);
        print_r('产品利率: '.$product->getRate().'%'.PHP_EOL);
        print_r('产品兑付方式: '.$product->getRepayModeName().PHP_EOL);
        print_r('是否次日起息: '.($product->getAdvanceInterest() ? '是' : '否').PHP_EOL);
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
}
