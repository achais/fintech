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
use Achais\FinTech\Exceptions\InternalException;
use Carbon\Carbon;

/**
 * 金融产品
 *
 * Class Product
 */
class Product
{
    // 兑付方式
    const REPAY_MODE_NATURAL_MONTH = 0;

    const REPAY_MODE_NATURAL_QUARTER = 1;

    const REPAY_MODE_NATURAL_HALF_YEAR = 2;

    const REPAY_MODE_NATURAL_YEAR = 3;

    const REPAY_MODE_MONTH = 10;

    const REPAY_MODE_QUARTER = 11;

    const REPAY_MODE_HALF_YEAR = 12;

    const REPAY_MODE_YEAR = 13;

    const REPAY_MODE_END_DATE = 20;

    const REPAY_MODE_CUSTOM_DATE = 50;

    // 期限类型
    const TERM_TYPE_DAY = 'day';

    const TERM_TYPE_MONTH = 'month';

    const TERM_TYPE_YEAR = 'year';

    // 兑付方式: 描述映射表
    protected $repayModeMap = [
        self::REPAY_MODE_NATURAL_MONTH => '自然月度付息，到期还本',
        self::REPAY_MODE_NATURAL_QUARTER => '自然季度付息，到期还本',
        self::REPAY_MODE_NATURAL_HALF_YEAR => '自然半年度付息，到期还本',
        self::REPAY_MODE_NATURAL_YEAR => '自然年度付息，到期还本',
        self::REPAY_MODE_MONTH => '月度付息，到期还本',
        self::REPAY_MODE_QUARTER => '季度付息，到期还本',
        self::REPAY_MODE_HALF_YEAR => '半年度付息，到期还本',
        self::REPAY_MODE_YEAR => '年度付息，到期还本',
        self::REPAY_MODE_END_DATE => '到期本息',
        self::REPAY_MODE_CUSTOM_DATE => '指定日期付息，到期还本',
    ];

    // 兑付方式: 兑付时间(顺延月份)映射表
    protected $timelineMap = [
        self::REPAY_MODE_NATURAL_MONTH => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        self::REPAY_MODE_NATURAL_QUARTER => [3, 6, 9, 12],
        self::REPAY_MODE_NATURAL_HALF_YEAR => [6, 12],
        self::REPAY_MODE_NATURAL_YEAR => [12],
        self::REPAY_MODE_MONTH => 1,
        self::REPAY_MODE_QUARTER => 3,
        self::REPAY_MODE_HALF_YEAR => 6,
        self::REPAY_MODE_YEAR => 12,
        self::REPAY_MODE_END_DATE => 1200,
        self::REPAY_MODE_CUSTOM_DATE => 12,
    ];

    protected $termTypeMap = [
        self::TERM_TYPE_DAY => '天',
        self::TERM_TYPE_MONTH => '月',
        self::TERM_TYPE_YEAR => '年',
    ];

    // ====== 初始属性 ======

    protected $rate; // 预期年化收益率

    protected $loanTerm; // 贷款期限

    protected $termType; // 期限类型

    protected $repayMode; // 兑付方式

    protected $foundDate; // 成立日期

    protected $advanceInterest = false; // 是否提前 T+N 起息

    protected $delayDays = 1; // T+N 起息

    protected $daysOfYear = 365; // 自然年计算天数

    protected $repayDay; // 指定兑付日(付息日)

    protected $repayMonth; // 指定兑付月(付息月)

    // ====== 计算属性 ======

    protected $endDate;

    protected $loanTermDays;

    protected $repaymentTimeline;

    // ====== 类使用 ======

    protected $initCompleted = false; // 是否初始化完成

    public function init($rate, $loanTerm, $repayMode, $foundDate, $termType = 'day', $repayDay = 0, $repayMonth = 0, $advanceInterest = false, $delayDays = 1, $daysOfYear = 365)
    {
        // 关键属性
        $this->rate = $rate;
        $this->loanTerm = $loanTerm;
        $this->repayMode = $repayMode;
        $this->foundDate = $foundDate;
        // 非关键属性
        $this->termType = $termType;
        $this->repayDay = $repayDay;
        $this->repayMonth = $repayMonth;
        $this->advanceInterest = $advanceInterest;
        $this->delayDays = $delayDays;
        $this->daysOfYear = $daysOfYear;

        if ($this->validate(true)) {
            $this->initCompleted = true;
        }

        return $this;
    }

    /**
     * 验证产品关键属性并且计算相关属性.
     *
     * @param bool $force
     *
     * @return bool
     *
     * @throws InternalException
     * @throws InvalidArgumentException
     */
    public function validate($force = false)
    {
        if (!$force) {
            if ($this->initCompleted) {
                return true;
            } else {
                throw new InternalException('未初始化产品参数');
            }
        }

        // ====== 验证关键属性 ======

        if (!($this->rate >= 0 && $this->rate <= 100)) {
            throw new InvalidArgumentException('产品预期年化收益率不符合要求');
        }

        if (!(array_key_exists($this->termType, $this->termTypeMap))) {
            throw new InvalidArgumentException('产品期限类型不符合要求');
        }

        if (!(is_int($this->loanTerm) && $this->loanTerm > 0)) {
            throw new InvalidArgumentException('产品期限不符合要求');
        }

        if (!(array_key_exists($this->repayMode, $this->repayModeMap))) {
            throw new InvalidArgumentException('产品兑付方式不符合要求');
        }

        if (!($this->foundDate instanceof Carbon)) {
            throw new InvalidArgumentException('产品成立时间不符合要求，要求 Carbon\Carbon Object');
        } else {
            $this->foundDate = $this->foundDate->startOfDay();
        }

        if (!($this->repayDay >= 0 && $this->repayDay <= 31)) {
            throw new InvalidArgumentException('产品指定兑付日不符合要求');
        }

        if (self::REPAY_MODE_CUSTOM_DATE === $this->repayMode && !($this->repayMonth >= 1 && $this->repayMonth <= 12)) {
            throw new InvalidArgumentException('产品指定兑付月不符合要求');
        }

        if (!(365 == $this->daysOfYear || 366 == $this->daysOfYear)) {
            throw new InvalidArgumentException('产品自然年计算天数不符合要求');
        }

        // ====== 初始化计算属性 ======

        // 产品到期时间
        switch ($this->termType) {
            case self::TERM_TYPE_DAY:
                $this->endDate = $this->foundDate->copy()->addDays($this->loanTerm);

                break;
            case self::TERM_TYPE_MONTH:
                $this->endDate = $this->foundDate->copy()->addMonths($this->loanTerm);

                break;
            case self::TERM_TYPE_YEAR:
                $this->endDate = $this->foundDate->copy()->addYears($this->loanTerm);

                break;
            default:
                throw new InvalidArgumentException('产品期限类型不符合要求');
        }
        // 实际产品天数
        $this->loanTermDays = $this->endDate->diffInDays($this->foundDate);
        // 指定兑付日
        if (self::REPAY_MODE_CUSTOM_DATE === $this->repayMode || array_key_exists($this->repayMode, array_slice($this->repayModeMap, 0, 4))) {
            // 如果是自然XX兑付方式并且没有指定兑付日, 兑付日为产品成立日
            if (0 === $this->repayDay) {
                $this->repayDay = (int) $this->foundDate->day;
            }
        } else {
            // 其他的兑付方式, 兑付日为产品成立日
            $this->repayDay = (int) $this->foundDate->day;
        }
        // 指定兑付月
        if (self::REPAY_MODE_CUSTOM_DATE != $this->repayMode) {
            $this->repayMonth = 0;
        }

        return true;
    }

    public function getRate()
    {
        $this->validate();

        return $this->rate;
    }

    public function getLoanTerm()
    {
        $this->validate();

        return $this->loanTerm;
    }

    public function getRepayMode()
    {
        return $this->repayMode;
    }

    public function getRepayModeName()
    {
        return $this->repayModeMap[$this->repayMode];
    }

    public function getFoundDate()
    {
        $this->validate();

        return $this->foundDate;
    }

    public function getRepayMonth()
    {
        $this->validate();

        return $this->repayMonth;
    }

    public function getRepayDay()
    {
        $this->validate();

        return $this->repayDay;
    }

    public function getAdvanceInterest()
    {
        $this->validate();

        return $this->advanceInterest;
    }

    public function getDelayDays()
    {
        $this->validate();

        return $this->delayDays;
    }

    public function getDaysOfYear($year = 0)
    {
        $this->validate();
        if (empty($year)) {
            return $this->daysOfYear;
        }

        if (Carbon::create($year)->isLeapYear()) {
            return 366;
        } else {
            return $this->daysOfYear;
        }
    }

    public function getEndDate()
    {
        $this->validate();

        return $this->endDate;
    }

    public function getLoanTermDays()
    {
        $this->validate();

        return $this->loanTermDays;
    }

    public function generateRepaymentTimeline()
    {
        if ($this->repaymentTimeline) {
            return $this->repaymentTimeline;
        }

        $this->validate();

        $repaymentTimeline = [];
        switch ($this->repayMode) {
            // 自然
            case self::REPAY_MODE_NATURAL_MONTH:
            case self::REPAY_MODE_NATURAL_QUARTER:
            case self::REPAY_MODE_NATURAL_HALF_YEAR:
            case self::REPAY_MODE_NATURAL_YEAR:
                $repayTimelineMap = $this->timelineMap[$this->repayMode];
                $cursorDate = Carbon::make($this->foundDate)->copy()->startOfDay();
                while ($cursorDate->lte($this->endDate)) {
                    if ((in_array($cursorDate->month, $repayTimelineMap) && $cursorDate->day === $this->repayDay) || $cursorDate->eq($this->endDate)) {
                        array_push($repaymentTimeline, $cursorDate->copy());
                    }

                    $cursorDate->addDay(1);
                }

                break;
            // 顺延
            case self::REPAY_MODE_MONTH:
            case self::REPAY_MODE_QUARTER:
            case self::REPAY_MODE_HALF_YEAR:
            case self::REPAY_MODE_YEAR:
                $stepMonth = $this->timelineMap[$this->repayMode];
                $cursorDate = Carbon::make($this->foundDate)->copy()->addMonth($stepMonth)->startOfDay();
                while ($cursorDate->lt($this->endDate)) {
                    array_push($repaymentTimeline, $cursorDate->copy());

                    $cursorDate->addMonth($stepMonth);

                    if ($cursorDate->gte($this->endDate)) {
                        array_push($repaymentTimeline, $this->endDate);

                        break;
                    }
                }

                break;
            // 到期本息
            case self::REPAY_MODE_END_DATE:
                array_push($repaymentTimeline, $this->endDate);

                break;
            // 年度付息, 指定日期
            case self::REPAY_MODE_CUSTOM_DATE:
                if (Carbon::create($this->foundDate->year, $this->repayMonth, $this->repayDay)->gt($this->foundDate)) {
                    $cursorDate = Carbon::create($this->foundDate->year, $this->repayMonth, $this->repayDay)->startOfDay();
                } else {
                    $cursorDate = Carbon::create($this->foundDate->year + 1, $this->repayMonth, $this->repayDay)->startOfDay();
                }

                while ($cursorDate->lte($this->endDate)) {
                    if (($cursorDate->month === $this->repayMonth && $cursorDate->day === $this->repayDay) || $cursorDate->eq($this->endDate)) {
                        array_push($repaymentTimeline, $cursorDate->copy());
                    }

                    $cursorDate->addDays(1);
                }

                break;
            default:
                break;
        }

        $this->repaymentTimeline = $repaymentTimeline;

        return $this->repaymentTimeline;
    }
}
