<?php

namespace common\models\traits;

use DateTimeImmutable;
use yii\behaviors\TimestampBehavior;

/**
 * Trait InsertedUpdated
 * @package common\models\traits
 * @property string $created_at
 * @property string $updated_at
 * @property DateTimeImmutable|null $createDate
 * @property DateTimeImmutable|null $updateDate
 */
trait InsertedUpdated
{
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
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

    public function getUpdateDate(): ?DateTimeImmutable
    {
        return empty($this->updated_at) ? null : new DateTimeImmutable($this->updated_at);
    }
}