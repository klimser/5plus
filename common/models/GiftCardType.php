<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%gift_card_type}}".
 *
 * @property int $id ID
 * @property string $name Название
 * @property int $amount Номинал
 * @property int $active Доступна для покупки
 */
class GiftCardType extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%gift_card_type}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'amount'], 'required'],
            [['amount', 'active'], 'integer'],
            [['name'], 'string', 'max' => 127],
            [['active'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['active'], 'default', 'value' => self::STATUS_ACTIVE],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'amount' => 'Номинал',
            'active' => 'Доступна для покупки',
        ];
    }
}
