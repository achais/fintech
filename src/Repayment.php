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

class Repayment
{
    protected $repaymentDate;

    protected $days;

    protected $repaymentInterest;

    protected $extraDays;

    protected $extraRepaymentInterest;

    protected $repaymentInvestmentAmount;

    protected $totalDays;

    protected $totalRepaymentAmount;

    public function __construct($repaymentDate, $days, $repaymentInterest, $extraDays = 0, $extraRepaymentInterest = 0, $repaymentInvestmentAmount = 0)
    {
        $this->repaymentDate = $repaymentDate;

        $this->days = $days;
        $this->repaymentInterest = $repaymentInterest;

        $this->extraDays = $extraDays;
        $this->extraRepaymentInterest = $extraRepaymentInterest;

        $this->repaymentInvestmentAmount = $repaymentInvestmentAmount;

        $this->totalDays = $this->days + $this->extraDays;
        $this->totalRepaymentAmount = $this->repaymentInvestmentAmount + $this->repaymentInterest + $this->extraRepaymentInterest;
    }

    public function getRepaymentDate()
    {
        return $this->repaymentDate;
    }

    public function getDays()
    {
        return $this->days;
    }

    public function getRepaymentInterest()
    {
        return $this->repaymentInterest;
    }

    public function getExtraDays()
    {
        return $this->extraDays;
    }

    public function getExtraRepaymentInterest()
    {
        return $this->extraRepaymentInterest;
    }

    public function getRepaymentInvestmentAmount()
    {
        return $this->repaymentInvestmentAmount;
    }

    public function getTotalDays()
    {
        return $this->totalDays;
    }

    public function getTotalRepaymentAmount()
    {
        return $this->totalRepaymentAmount;
    }
}
