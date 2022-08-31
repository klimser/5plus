<?php

namespace common\models\traits;

use DateTimeImmutable;
use yii\behaviors\TimestampBehavior;

/**
 * Trait Inserted
 * @package common\models\traits
 * @property string $created_at
 * @property DateTimeImmutable|null $createDate
 * @property string $createDateString
 */
trait Inserted
{
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

    public function getCreateDate(): ?DateTimeImmutable
    {
        return empty($this->created_at) ? null : new DateTimeImmutable($this->created_at);
    }

    public function getCreateDateString(): string
    {
        $createDate = $this->getCreateDate();
        return $createDate ? $createDate->format('Y-m-d H:i:s') : '';
    }
}