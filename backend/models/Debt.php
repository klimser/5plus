<?php

namespace backend\models;

use \common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%debt}}".
 *
 * @property int $user_id
 * @property double $amount
 * @property string $comment
 * @property string $danger_date
 * @property \DateTime $dangerDate
 * @property string $dangerDateString
 *
 * @property User $user
 */
class Debt extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%debt}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'amount'], 'required'],
            [['user_id'], 'integer'],
            [['amount'], 'number'],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'Должник',
            'amount' => 'Сумма задолженности',
            'comment' => 'Комментарий',
            'danger_date' => 'Дата недопуска',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return \DateTime
     */
    public function getDangerDate(): ?\DateTime
    {
        return empty($this->danger_date) ? null : new \DateTime($this->danger_date);
    }

    /**
     * @return string
     */
    public function getDangerDateString(): string
    {
        $dangerDate = $this->getDangerDate();
        return $dangerDate ? $dangerDate->format('Y-m-d') : '';
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ($insert && !$this->danger_date) {
            $dangerDate = new \DateTime('+7 day midnight');
            $this->danger_date = $dangerDate->format('Y-m-d H:i:s');
        }
        return parent::beforeSave($insert);
    }
}