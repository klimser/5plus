<?php

namespace common\models\traits;

use yii\behaviors\TimestampBehavior;

/**
 * Trait InsertedUpdated
 * @package common\models\traits
 * @property string $created_at
 * @property string $updated_at
 * @property \DateTime|null $createDate
 * @property \DateTime|null $updateDate
 */
trait InsertedUpdated
{
    /**
     * @return array
     */
    public function behaviors()
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

    /**
     * @return \DateTime|null
     */
    public function getCreateDate(): ?\DateTime
    {
        return empty($this->created_at) ? null : new \DateTime($this->created_at);
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdateDate(): ?\DateTime
    {
        return empty($this->updated_at) ? null : new \DateTime($this->updated_at);
    }
}