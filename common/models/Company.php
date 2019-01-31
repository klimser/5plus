<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%company}}".
 *
 * @property int $id ID компании
 * @property string $first_name Официальное название
 * @property string $second_name Формальное название
 * @property string $licence Лицензия
 * @property string $head_name Имя директора
 * @property string $head_name_short Имя директора кратко
 * @property string $zip Индекс
 * @property string $city Город
 * @property string $address Адрес
 * @property string $phone Телефон
 * @property string $tin ИНН
 * @property string $bank_data Банковские реквизиты
 * @property string $oked ОКЭД
 * @property string $mfo МФО
 */
class Company extends ActiveRecord
{
    const COMPANY_EXCLUSIVE_ID = 1;
    const COMPANY_SUPER_ID = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%company}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name', 'second_name', 'licence', 'head_name', 'head_name_short', 'zip', 'city', 'address', 'phone', 'tin', 'bank_data', 'oked', 'mfo'], 'required'],
            [['first_name', 'second_name', 'licence', 'head_name', 'head_name_short', 'address', 'bank_data'], 'string', 'max' => 255],
            [['zip'], 'string', 'max' => 15],
            [['city', 'phone'], 'string', 'max' => 100],
            [['tin', 'oked', 'mfo'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID компании',
            'first_name' => 'Официальное название',
            'second_name' => 'Формальное название',
            'licence' => 'Лицензия',
            'head_name' => 'Имя директора',
            'head_name_short' => 'Имя директора кратко',
            'zip' => 'Индекс',
            'city' => 'Город',
            'address' => 'Адрес',
            'phone' => 'Телефон',
            'tin' => 'ИНН',
            'bank_data' => 'Банковские реквизиты',
            'oked' => 'ОКЭД',
            'mfo' => 'МФО',
        ];
    }
}
