<?php

namespace common\models\traits;

/**
 * Trait GroupParam
 * @package common\models\traits
 * @property int $lesson_price
 * @property int $lesson_price_discount
 * @property string $weekday
 * @property int $priceMonth
 * @property int $price3Month
 * @property int $classesPerWeek
 * @property int $classesPerMonth
 */
trait GroupParam
{
    /**
     * @return int
     */
    public function getPriceMonth(): int
    {
        return $this->lesson_price * $this->getClassesPerMonth();
    }

    /**
     * @return int
     */
    public function getPrice3Month(): int
    {
        return ($this->lesson_price_discount ?: $this->lesson_price) * $this->getClassesPerMonth() * 3;
    }

    /**
     * @return int
     */
    public function getClassesPerWeek(): int
    {
        if (!preg_match('#^[01]{6}$#', $this->weekday)) throw new \InvalidArgumentException('Wrong schedule string');

        return substr_count($this->weekday, '1');
    }

    /**
     * @return int
     */
    public function getClassesPerMonth(): int
    {
        return $this->getClassesPerWeek() * 4;
    }
}