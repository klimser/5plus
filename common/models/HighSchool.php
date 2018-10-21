<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\UploadImage;
use yii;

/**
 * This is the model class for table "{{%high_school}}".
 *
 * @property string $id
 * @property int $type
 * @property string $name
 * @property string $name_short
 * @property string $photo
 * @property string $short_description
 * @property string $description
 * @property int $page_order
 * @property int $active
 * @property string $descriptionForEdit
 * @property string $photoUrl
 */
class HighSchool extends ActiveRecord
{
    use UploadImage;

    const TYPE_HIGHSCHOOL = 1;
    const TYPE_LYCEUM = 2;

    /**
     * @return array
     */
    public function getUploadImageConfig(): array
    {
        return [
            'neededImageWidth' => 300,
            'neededImageHeight' => 300,
            'imageFolder' => 'high_school',
            'imageDBField' => 'photo',
            'imageFilenameBase' => 'name_short',
            'imageFilenameAppendix' => 'id',
        ];
    }

    private $descriptionForEdit;

    /** @var yii\web\UploadedFile */
    public $photoFile;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%high_school}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'name_short', 'short_description', 'description'], 'required'],
            [['short_description', 'description', 'descriptionForEdit'], 'string'],
            [['type', 'active', 'page_order'], 'integer'],
            [['name', 'photo'], 'string', 'max' => 255],
            [['name_short'], 'string', 'max' => 127],
            [['photoFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg', 'checkExtensionByMimeType' => true],
            [['type'], 'in', 'range' => [self::TYPE_HIGHSCHOOL, self::TYPE_LYCEUM]],
            [['active'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'name_short' => 'Аббревиатура',
            'photo' => 'Фото',
            'photoFile' => 'Фото',
            'descriptionForEdit' => 'Краткое описание',
            'description' => 'Полное описание',
            'active' => 'Показывать',
        ];
    }

    /**
     * @return string
     */
    public function getDescriptionForEdit()
    {
        if ($this->descriptionForEdit === null) $this->descriptionForEdit = self::convertTextForEditor($this->short_description, 'highschool_highlight');
        return $this->descriptionForEdit;
    }

    /**
     * @param string $value
     */
    public function setDescriptionForEdit($value)
    {
        $this->descriptionForEdit = $value;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if ($this->descriptionForEdit) $this->short_description = self::convertTextForDB($this->descriptionForEdit, 'highschool_highlight');
            return true;
        } else return false;
    }
}