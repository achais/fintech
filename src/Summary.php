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

class Summary
{
    protected $product;

    protected $repaymentList;

    protected $endDate;

    protected $totalInterest = 0;

    protected $totalAmount = 0;

    public function __construct(Product $product, $repaymentList)
    {
        $this->product = $product;
        $this->repaymentList = $repaymentList;

        $this->endDate = $this->product->getEndDate();

        foreach ($repaymentList as $repayment) {
            $this->totalInterest += $repayment->getRepaymentInterest() + $repayment->getExtraRepaymentInterest();
            $this->totalAmount = $repayment->getTotalRepaymentAmount();
        }
    }

    public function getEndDate()
    {
        return $this->endDate;
    }

    public function getTotalInterest()
    {
        return $this->totalInterest;
    }

    public function getTotalAmount()
    {
        return $this->totalInterest;
    }
}
