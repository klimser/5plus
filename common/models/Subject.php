<?php

namespace common\models;

use backend\models\TeacherSubjectLink;
use common\components\extended\ActiveRecord;
use common\models\traits\UploadImage;
use yii\db\ActiveQuery;
use yii\web\UploadedFile;

/**
 * This is the model class for table "{{%subject}}".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $content
 * @property int $webpage_id
 * @property int $active
 * @property string $image
 * @property int|null $category_id
 *
 * @property Webpage $webpage
 * @property SubjectCategory $subjectCategory
 * @property TeacherSubjectLink[] $subjectTeachers
 * @property Teacher[] $teachers
 */
class Subject extends ActiveRecord
{
    use UploadImage;

    /**
     * @return array
     */
    public function getUploadImageConfig(): array
    {
        return [
            'neededImageWidth' => 484,
            'neededImageHeight' => 0,
            'imageFolder' => 'subject',
            'imageDBField' => 'image',
            'imageFilenameBase' => 'name',
            'imageFilenameAppendix' => 'id',
        ];
    }

    /** @var UploadedFile */
    public $imageFile;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%module_subject}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'content', 'description', 'category_id'], 'required'],
            [['name'], 'string', 'max' => 50],
            [['category_id'], 'integer'],
            [['image'], 'string', 'max' => 255],
            [['content', 'description'], 'string'],
            [['imageFile', 'imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg', 'checkExtensionByMimeType' => true],
            [['imageFile'], 'required', 'when' => function ($model, $attribute) {return $model->isNewRecord;}, 'whenClient' => "function (attribute, value) {
                return !$(attribute.input).data(\"id\");
            }"],
            ['name', 'unique', 'targetAttribute' => ['name', 'category_id']],
            [['category_id'], 'exist', 'targetRelation' => 'subjectCategory'],
            [['active'], 'integer'],
            [['active'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['content', 'description'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID курса',
            'name' => 'Название курса',
            'description' => 'Краткое описание',
            'content' => 'Содержимое страницы',
            'active' => 'Опубликован',
            'imageFile' => 'Картинка (min 484x309)',
            'category_id' => 'Группа предметов',
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
     * @return \yii\db\ActiveQuery
     */
    public function getSubjectCategory()
    {
        return $this->hasOne(SubjectCategory::class, ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubjectTeachers()
    {
        return $this->hasMany(TeacherSubjectLink::class, ['subject_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTeachers()
    {
        return $this->hasMany(Teacher::class, ['id' => 'teacher_id'])
            ->via('subjectTeachers');
    }

    /**
     * @return ActiveQuery
     */
    public static function getActiveListQuery()
    {
        return self::find()->where(['active' => self::STATUS_ACTIVE])->orderBy('name');
    }

    /**
     * @return Subject[]
     */
    public static function getActiveList()
    {
        return self::getActiveListQuery()->all();
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) return false;

        if ($this->webpage && !$this->webpage->delete()) {
            $this->webpage->moveErrorsToFlash();
            return false;
        }
        return true;
    }
}
