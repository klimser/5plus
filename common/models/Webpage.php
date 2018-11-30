<?php

namespace common\models;

use \common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%webpage}}".
 *
 * @property string $id
 * @property string $url
 * @property int $main
 * @property string $title
 * @property string $description
 * @property string $keywords
 * @property int $active
 * @property int $module_id
 * @property string $record_id
 * @property int $inrobots
 *
 * @property string $shortUrl
 *
 * @property Module $module
 */
class Webpage extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%webpage}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url', 'module_id'], 'required'],
            [['main', 'active', 'module_id', 'record_id', 'inrobots'], 'integer'],
            [['description', 'keywords'], 'string'],
            [['url', 'title'], 'string', 'max' => 255],
            [['url', 'title', 'description', 'keywords'], 'safe'],
            [['url'], 'unique', 'targetAttribute' => ['url'], 'message' => 'Url has already been taken.'],
            [['inrobots', 'active'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['active', 'inrobots'], 'default', 'value' => self::STATUS_ACTIVE],
            [['main'], 'default', 'value' => null],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'Url',
            'main' => 'Main',
            'title' => 'Title',
            'description' => 'Description',
            'keywords' => 'Keywords',
            'active' => 'Active',
            'module_id' => 'Module ID',
            'record_id' => 'Record ID',
            'inrobots' => 'Inrobots',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getModule()
    {
        return $this->hasOne(Module::class, ['id' => 'module_id']);
    }

    /**
     * @return string
     */
    public function getShortUrl(): string
    {
        if ($this->module && $this->module->url_prefix
            && substr($this->url, 0, strlen($this->module->url_prefix)) == $this->module->url_prefix) {
            return substr($this->url, strlen($this->module->url_prefix));
        }
        return $this->url ?: '';
    }

    /**
     * Обновить данные для webpage как приложения к модулю
     *
     * @param array $data
     * @return bool
     */
    public function updateAsAppendix($data)
    {
        if ($this->load($data)) {
            if (!$this->save()) {
                $this->moveErrorsToFlash();
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) return false;
        if ($this->module_id) {
            $module = Module::findOne($this->module_id);
            if ($module && $module->url_prefix) {
                $this->url = $module->url_prefix . $this->url;
            }
        }
        return true;
    }

    public function beforeSave($insert)
    {
        \common\components\Webpage::clearCache($this);
        return parent::beforeSave($insert);
    }

    public function beforeDelete()
    {
        \common\components\Webpage::clearCache($this);
        return parent::beforeDelete();
    }
}
