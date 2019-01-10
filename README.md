<h1 align="center"> FinTech </h1>

<p align="center"> 一个帮助你计算金融产品兑付明细的扩展包 </p>

<p align="center">
[![Build Status](https://travis-ci.org/achais/fintech.svg?branch=master)](https://travis-ci.org/achais/fintech)
</p>

## 介绍

#### 目前支持的兑付方式
- 自然月度付息，到期还本
- 自然季度付息，到期还本
- 自然半年度付息，到期还本
- 自然年度付息，到期还本
- 月度付息，到期还本
- 季度付息，到期还本
- 半年度付息，到期还本
- 年度付息，到期还本
- 到期本息
- 指定日期付息，到期还本 (年度付息)

## 安装

```shell
$ composer require achais/fintech -vvv
```

## 使用

#### 获取产品兑付时间列表

```php
use Carbon\Carbon;
use Achais\FinTech\Product;

// ====== 产品属性 ======

$rate = 8.0; // 利率
$loanTerm = 24; // 产品期限
$termType = Product::TERM_TYPE_MONTH; // 期限类型(月)
$foundDate = Carbon::create('2019-07-08'); // 产品成立时间
$repayMode = Product::REPAY_MODE_NATURAL_QUARTER; // 兑付方式(自然季度付息, 到期还本)
$repayDay = 20; // 指定兑付日

$product = new Product(); // 实例化产品
$product->init($rate, $loanTerm, $repayMode, $foundDate, $termType, $repayDay); // 初始化属性

// ====== 业务 ======

$timeline = $product->generateRepaymentTimeline(); // 生成兑付时间表

foreach ($timeline as $pointTime) {
    printf($pointTime->toDateString() . PHP_EOL);
}
```

输出内容

```text
2019-09-20 00:00:00
2019-12-20 00:00:00
2020-03-20 00:00:00
2020-06-20 00:00:00
2020-09-20 00:00:00
2020-12-20 00:00:00
2021-03-20 00:00:00
2021-06-20 00:00:00
2021-07-08 00:00:00
```

#### 获取认购产品兑付明细

```php
use Carbon\Carbon;
use Achais\FinTech\Calculator;
use Achais\FinTech\Investment;
use Achais\FinTech\Product;

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
print_r('产品成立时间: ' . $product->getFoundDate() . PHP_EOL);
print_r('产品到期时间: ' . $product->getEndDate() . PHP_EOL);
print_r('产品实际天数: ' . $product->getLoanTermDays() . PHP_EOL);
print_r('产品利率: ' . $product->getRate() . '%' . PHP_EOL);
print_r('产品兑付方式: ' . $product->getRepayModeName() . PHP_EOL);
print_r('是否次日起息: ' . ($product->getAdvanceInterest() ? '是' : '否')  . PHP_EOL);
print_r('指定兑付月: ' . $product->getRepayMonth() . PHP_EOL);
print_r('指定兑付日: ' . $product->getRepayDay() . PHP_EOL);

printf(PHP_EOL);
printf('认购金额: %s' . PHP_EOL, $investment->getAmount());
printf('认购时间: %s' . PHP_EOL, $investment->getInvestDateTime()->toDateTimeString());
printf(PHP_EOL);

foreach ($repaymentList as $repayment) {
    printf('兑付时间点: %s | 计息天数: %s | 兑付利息: %s | 加息天数: %s | 加息金额: %s | 本金: %s | 总金额: %s' . PHP_EOL,
        $repayment->getRepaymentDate(),
        $repayment->getDays(),
        $repayment->getRepaymentInterest(),
        $repayment->getExtraDays(),
        $repayment->getExtraRepaymentInterest(),
        $repayment->getRepaymentInvestmentAmount(),
        $repayment->getTotalRepaymentAmount()
    );
}
```

输出内容

```text
产品成立时间: 2019-07-08 00:00:00
产品到期时间: 2021-07-08 00:00:00
产品实际天数: 731
产品利率: 8%
产品兑付方式: 自然季度付息，到期还本
是否次日起息: 是
指定兑付月: 0
指定兑付日: 20

认购金额: 10000
认购时间: 2019-07-05 12:00:00

兑付时间点: 2019-09-20 00:00:00 | 计息天数: 74 | 兑付利息: 162.19 | 加息天数: 2 | 加息金额: 4.38 | 本金: 0 | 总金额: 166.57
兑付时间点: 2019-12-20 00:00:00 | 计息天数: 91 | 兑付利息: 199.45 | 加息天数: 0 | 加息金额: 0 | 本金: 0 | 总金额: 199.45
兑付时间点: 2020-03-20 00:00:00 | 计息天数: 91 | 兑付利息: 199.45 | 加息天数: 0 | 加息金额: 0 | 本金: 0 | 总金额: 199.45
兑付时间点: 2020-06-20 00:00:00 | 计息天数: 92 | 兑付利息: 201.64 | 加息天数: 0 | 加息金额: 0 | 本金: 0 | 总金额: 201.64
兑付时间点: 2020-09-20 00:00:00 | 计息天数: 92 | 兑付利息: 201.64 | 加息天数: 0 | 加息金额: 0 | 本金: 0 | 总金额: 201.64
兑付时间点: 2020-12-20 00:00:00 | 计息天数: 91 | 兑付利息: 199.45 | 加息天数: 0 | 加息金额: 0 | 本金: 0 | 总金额: 199.45
兑付时间点: 2021-03-20 00:00:00 | 计息天数: 90 | 兑付利息: 197.26 | 加息天数: 0 | 加息金额: 0 | 本金: 0 | 总金额: 197.26
兑付时间点: 2021-06-20 00:00:00 | 计息天数: 92 | 兑付利息: 201.64 | 加息天数: 0 | 加息金额: 0 | 本金: 0 | 总金额: 201.64
兑付时间点: 2021-07-08 00:00:00 | 计息天数: 18 | 兑付利息: 39.45 | 加息天数: 0 | 加息金额: 0 | 本金: 10000 | 总金额: 10039.45
```

## License

MIT