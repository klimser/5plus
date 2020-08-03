<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\Inserted;
use common\models\traits\UploadImage;
use yii\db\ActiveQuery;
use yii\web\UploadedFile;

/**
 * This is the model class for table "{{%module_news}}".
 *
 * @property int $id ID новости
 * @property string $name Заголовок
 * @property string $image Картинка
 * @property string $content Контент
 * @property string $teaser Тизер
 * @property bool $teasered Обрезан ли тизер или туда поместился весь контент
 * @property int $webpage_id ID страницы
 * @property int $active Активен
 * @property string $created_at Дата публикакции
 *
 * @property Webpage $webpage
 */
class News extends ActiveRecord
{
    use Inserted, UploadImage;

    const TEASER_LENGTH = 400;

    /**
     * @return array
     */
    public function getUploadImageConfig(): array
    {
        return [
            'neededImageWidth' => 350,
            'neededImageHeight' => 0,
            'imageFolder' => 'news',
            'imageDBField' => 'image',
            'imageFilenameBase' => 'name',
            'imageFilenameAppendix' => 'id',
        ];
    }

    /** @var UploadedFile */
    public $imageFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%module_news}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'content'], 'required'],
            [['content'], 'string'],
            [['webpage_id', 'active'], 'integer'],
            [['created_at'], 'safe'],
            [['name'], 'string', 'max' => 100],
            [['image'], 'string', 'max' => 255],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg', 'checkExtensionByMimeType' => true],
            [['imageFile'], 'required', 'when' => function ($model, $attribute) {return $model->isNewRecord;}, 'whenClient' => "function (attribute, value) {
                return !$(attribute.input).data(\"id\");
            }"],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID новости',
            'name' => 'Заголовок',
            'imageFile' => 'Картинка (350x225)',
            'content' => 'Контент',
            'webpage_id' => 'ID страницы',
            'active' => 'Активен',
            'created_at' => 'Дата публикакции',
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
     * @return string
     */
    public function getTeaser(): string
    {
        $teaser = preg_replace('#<\/p>[\r\n ]*<p>#', '<br> ', $this->content);
        $teaser = strip_tags($teaser, '<br>');
        if (mb_strlen($teaser) > self::TEASER_LENGTH) {
            $teaser = mb_substr($teaser, 0, self::TEASER_LENGTH);
            if (mb_strpos($teaser, '.') !== false) {
                $teaser = mb_substr($teaser, 0, mb_strrpos($teaser, '.'));
            } else {
                $teaser = mb_substr($teaser, 0, mb_strrpos($teaser, ' '));
            }
            $teaser .= '...';
        }

        return $teaser;
    }

    public function getTeasered(): bool
    {
        return mb_strlen($this->content) > self::TEASER_LENGTH;
    }

    /**
     * @return ActiveQuery
     */
    public static function getActiveListQuery()
    {
        return self::find()->where(['active' => self::STATUS_ACTIVE])->orderBy(['created_at' => SORT_DESC]);
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
