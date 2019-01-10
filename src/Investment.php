<?php
/**
 * Created by PhpStorm.
 * User: achais
 * Date: 2019/1/9
 * Time: 5:24 PM
 */

namespace Achais\FinTech;

use Achais\FinTech\Exceptions\InvalidArgumentException;
use Carbon\Carbon;

class Investment
{
    protected $investDateTime;

    protected $amount;

    public function __construct($investDateTime, $amount)
    {
        if (!($investDateTime instanceof Carbon)) {
            throw new InvalidArgumentException('认购时间不符合要求，要求 Carbon\Carbon Object');
        }

        if (!(is_int($amount) && $amount > 0)) {
            throw new InvalidArgumentException('认购金额不符合要求');
        }

        $this->investDateTime = $investDateTime;
        $this->amount = $amount;
    }

    public function getInvestDateTime()
    {
        return $this->investDateTime;
    }

    public function getAmount()
    {
        return $this->amount;
    }
}