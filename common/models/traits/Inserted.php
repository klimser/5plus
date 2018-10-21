<?php

namespace common\models\traits;

use yii\behaviors\TimestampBehavior;

/**
 * Trait Inserted
 * @package common\models\traits
 * @property string $created_at
 * @property \DateTime|null $createDate
 * @property string $createDateString
 */
trait Inserted
{
    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => function () {
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
    }

    /**
     * @return \DateTime|null
     */
    public function getCreateDate(): ?\DateTime
    {
        return empty($this->created_at) ? null : new \DateTime($this->created_at);
    }

    /**
     * @return string
     */
    public function getCreateDateString(): string
    {
        $createDate = $this->getCreateDate();
        return $createDate ? $createDate->format('Y-m-d H:i:s') : '';
    }
}