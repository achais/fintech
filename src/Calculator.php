<?php
/**
 * Created by PhpStorm.
 * User: achais
 * Date: 2019/1/9
 * Time: 5:19 PM
 */

namespace Achais\FinTech;

use Achais\FinTech\Product;
use Carbon\Carbon;

class Calculator
{
    protected $product;

    protected $repaymentTimeline;

    public function __construct(Product $product)
    {
        if ($product->validate()) {
            $this->product = $product;
            $this->repaymentTimeline = $product->generateRepaymentTimeline();
        }
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
     * @param Investment $investment
     * @return array
     */
    public function getRepaymentList(Investment $investment)
    {
        $repaymentList = [];

        $foundDate = $this->getProduct()->getFoundDate();
        $cursorDate = Carbon::make($foundDate)->copy();

        foreach ($this->getRepaymentTimeline() as $index => $timePoint) {
            // T+N 起息
            $extraDays = 0;
            $extraRepaymentInterest = 0;
            if ($index === 0 && $this->product->getAdvanceInterest()) {
                $investDateTime = Carbon::make($investment->getInvestDateTime());
                $investDate = $investDateTime->copy()->startOfDay()->addDay($this->product->getDelayDays());

                $extraDays = Carbon::make($this->product->getFoundDate())->diffInDays($investDate);
                $extraRepaymentInterest = $this->calcInterest($extraDays, $investment->getAmount());
            }

            // 兑付本金
            $repaymentInvestmentAmount = 0;
            if ((count($this->getRepaymentTimeline()) - $index) === 1) {
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

        return $repaymentList;
    }
}