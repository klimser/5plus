<?php

namespace common\models;

use common\models\traits\Inserted;
use \common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%module_page}}".
 *
 * @property int $id
 * @property string $title
 * @property string $content
 * @property int $webpage_id
 * @property int $active
 *
 * @property Webpage $webpage
 */
class Page extends ActiveRecord
{
    use Inserted;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%module_page}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'content'], 'required'],
            [['content'], 'string'],
            [['webpage_id', 'active'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['active'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['active', 'default', 'value' => self::STATUS_ACTIVE],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Заголовок',
            'content' => 'Содержимое',
            'webpage_id' => 'ID webpage',
            'active' => 'Опубликована',
            'created_at' => 'дата добавления',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWebpage()
    {
        return $this->hasOne(Webpage::class, ['id' => 'webpage_id']);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active == 1;
    }

    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            if ($this->webpage && !$this->webpage->delete()) {
                $this->webpage->moveErrorsToFlash();
                return false;
            }
            return true;
        } else {
            return false;
        }
    }
}
