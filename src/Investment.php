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

class Investment
{
    protected $investDateTime;

    protected $amount;

    protected $extra = [];

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

    public function setExtra($key, $value)
    {
        $this->extra[$key] = $value;

        return $this;
    }

    public function getExtra($key = null)
    {
        if (is_null($key)) {
            return $this->extra;
        }

        return array_key_exists($key, $this->extra) ? $this->extra[$key] : null;
    }
}
