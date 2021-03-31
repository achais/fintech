<?php

/*
 * This file is part of the achais/fintech.
 *
 * (c) achais.zheng <achais.zheng@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Achais\FinTech;

use Achais\FinTech\Exceptions\InvalidArgumentException;
use Carbon\Carbon;

class Calculator
{
    protected $product;

    protected $repaymentTimeline;

    protected $cacheRepaymentLists = [];

    protected $cacheRepaymentSummaries = [];

    public function __construct(Product $product)
    {
        if ($product->validate()) {
            $this->product = $product;
            $this->repaymentTimeline = $product->generateRepaymentTimeline();
        }
    }

    protected function getInvestmentMapKey(Investment $investment)
    {
        return $investment->getInvestDateTime()->toDateString().'-'.$investment->getAmount();
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getRepaymentTimeline()
    {
        return $this->repaymentTimeline;
    }

    public function calcInterest($days, $amount)
    {
        $rate = $this->product->getRate();
        $daysOfYear = $this->product->getDaysOfYear();

        return round(($amount * $rate / 100 / $daysOfYear) * $days, 2);
    }

    /**
     * @return array|mixed
     *
     * @throws InvalidArgumentException
     */
    public function getRepaymentList(Investment $investment)
    {
        $mapKey = $this->getInvestmentMapKey($investment);

        if (array_key_exists($mapKey, $this->cacheRepaymentLists)) {
            return $this->cacheRepaymentLists[$mapKey];
        }

        $repaymentList = [];

        $investDateTime = $investment->getInvestDateTime();
        $foundDate = $this->getProduct()->getFoundDate();

        if ($investDateTime->gte($foundDate)) {
            throw new InvalidArgumentException('投资时间不能大于产品成立时间');
        }

        $cursorDate = Carbon::make($foundDate)->copy();

        foreach ($this->getRepaymentTimeline() as $index => $timePoint) {
            // T+N 起息
            $extraDays = 0;
            $extraRepaymentInterest = 0;
            if (0 === $index && $this->product->getAdvanceInterest()) {
                $extraDays = $this->getAdvanceFoundDays($investment);
                $extraRepaymentInterest = $this->calcInterest($extraDays, $investment->getAmount());
            }

            // 兑付本金
            $repaymentInvestmentAmount = 0;
            if (1 === (count($this->getRepaymentTimeline()) - $index)) {
                $repaymentInvestmentAmount = $investment->getAmount();
            }

            // 兑付利息
            $days = $cursorDate->diffInDays($timePoint);
            $repaymentInterest = $this->calcInterest($days, $investment->getAmount());

            // 拼装还款信息
            $repayment = new Repayment($timePoint, $days, $repaymentInterest, $extraDays, $extraRepaymentInterest, $repaymentInvestmentAmount);
            array_push($repaymentList, $repayment);

            $cursorDate = $timePoint;
        }

        $this->cacheRepaymentLists[$mapKey] = $repaymentList;

        return $repaymentList;
    }

    public function getRepaymentSummary(Investment $investment)
    {
        $mapKey = $this->getInvestmentMapKey($investment);

        if (array_key_exists($mapKey, $this->cacheRepaymentSummaries)) {
            return $this->cacheRepaymentSummaries[$mapKey];
        }

        $repaymentList = $this->getRepaymentList($investment);

        $summary = new Summary($this->product, $repaymentList);
        $this->cacheRepaymentSummaries[$mapKey] = $summary;

        return $summary;
    }

    public function getAdvanceFoundDate(Investment $investment)
    {
        $investDateTime = $investment->getInvestDateTime();
        $foundDate = $this->product->getFoundDate();
        $investDate = $investDateTime->copy()->startOfDay()->addDays($this->product->getDelayDays());

        if (Product::ADVANCE_INTEREST_TYPE_SKIP_HOLIDAY == $this->product->getAdvanceInterestType()) {
            while (in_array($investDate, $this->product->getHolidays()) && $investDate->lte($foundDate)) {
                $investDate = $investDate->addDays(1);
            }
        }

        return $investDate;
    }

    public function getAdvanceFoundDays(Investment $investment)
    {
        return $this->getAdvanceFoundDate($investment)->diffInDays($this->product->getFoundDate());
    }
}
