<?php

namespace common\models\traits;

use common\components\helpers\MoneyHelper;

/**
 * Trait GroupParam
 * @package common\models\traits
 * @property string $schedule
 * @property string[] $scheduleData
 * @property int $lesson_price
 * @property int $lesson_price_discount
 * @property int $priceMonth
 * @property int $price3Month
 * @property int $price4Month
 * @property int $classesPerWeek
 * @property int $classesPerMonth
 */
trait GroupParam
{
    /**
     * @return string[]
     */
    public function getScheduleData(): array
    {
        $data = json_decode($this->getAttribute('schedule'), true);
        if (empty($data)) $data = array_fill(0, 7, '');
        return $data;
    }

    /**
     * @param string[] $value
     */
    public function setScheduleData(array $value)
    {
        $valid = true;
        if (count($value) != 7) $valid = false;
        else {
            foreach ($value as $item) {
                if (!is_string($item) || (!empty($item) && !preg_match('#^\d{2}:\d{2}$#', $item))) $valid = false;
            }
        }
        if (!$valid) $value = array_fill(0, 7, '');
        $this->setAttribute('schedule', json_encode($value));
    }

    public function getPriceMonth(): int
    {
        return MoneyHelper::roundThousand($this->lesson_price * $this->getClassesPerMonth());
    }

    public function getPrice3Month(): int
    {
        return MoneyHelper::roundThousand(($this->lesson_price_discount ?: $this->lesson_price) * $this->getClassesPerMonth() * 3);
    }

    public function getPrice4Month(): int
    {
        return MoneyHelper::roundThousand(($this->lesson_price_discount ?: $this->lesson_price) * $this->getClassesPerMonth() * 4);
    }

    public function getClassesPerWeek(): int
    {
        $count = 0;
        foreach ($this->scheduleData as $elem) {
            if (!empty($elem)) $count++;
        };
        return $count;
    }

    /**
     * @return int
     */
    public function getClassesPerMonth(): int
    {
        return $this->getClassesPerWeek() * 4;
    }

    /**
     * @param \DateTime $day
     * @return string
     */
    public function getLessonTime(\DateTime $day): string
    {
        if (!$this->hasLesson($day)) return '';
        return $this->scheduleData[(7 + intval($day->format('w')) - 1) % 7] . ':00';
    }

    /**
     * @param \DateTime $day
     * @return string
     */
    public function getLessonDateTime(\DateTime $day): string
    {
        if (!$this->hasLesson($day)) return '';
        return $day->format('Y-m-d') . ' ' . $this->getLessonTime($day);
    }

    /**
     * @param \DateTime $day
     * @return bool
     */
    public function hasLesson(\DateTime $day): bool
    {
        return !empty($this->scheduleData[(7 + intval($day->format('w')) - 1) % 7]);
    }
}
