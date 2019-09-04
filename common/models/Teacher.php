<?php

namespace common\models;

use backend\models\Event;
use backend\models\TeacherSubjectLink;
use common\components\ComponentContainer;
use common\components\extended\ActiveRecord;
use common\models\traits\UploadImage;
use DateTime;
use yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%teacher}}".
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $birthday
 * @property DateTime $birthdayDate
 * @property string $title
 * @property string $description
 * @property string|null $photo
 * @property int $page_visibility
 * @property int $active
 * @property string $shortName
 * @property string $officialName
 * @property string $webpage_id
 * @property string $descriptionForEdit
 * @property int $page_order
 * @property string $noPhotoUrl
 *
 * @property Event[] $events
 * @property TeacherSubjectLink[] $teacherSubjects
 * @property Subject[] $subjects
 * @property Group[] $groups
 * @property Webpage $webpage
 * @property User $user
 */
class Teacher extends ActiveRecord
{
    use UploadImage {
        upload as protected uploadBasic;
    }

    /**
     * @return array
     */
    public function getUploadImageConfig(): array
    {
        return [
            'neededImageWidth' => 188,
            'neededImageHeight' => 188,
            'imageFolder' => 'teacher',
            'imageDBField' => 'photo',
            'imageFilenameBase' => 'name',
            'imageFilenameAppendix' => 'id',
            'skipTinify' => true,
        ];
    }

    private $_shortName;
    private $_officialName;
    private $descriptionForEdit;

    /** @var yii\web\UploadedFile */
    public $photoFile;


    public static function tableName()
    {
        return '{{%teacher}}';
    }


    public function rules()
    {
        return [
            [['name', 'title', 'description'], 'trim'],
            [['active', 'page_visibility'], 'default', 'value' => self::STATUS_ACTIVE],
            [['title', 'phone', 'photo', 'description'], 'default', 'value' => null],
            [['name'], 'required'],
            [['description'], 'required', 'when' => function(self $model) { return $model->active === Teacher::STATUS_ACTIVE; }],
            [['name'], 'string', 'max' => 127],
            [['title', 'photo'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 13],
            [['birthday'], 'date', 'format' => 'yyyy-MM-dd'],
            [['description', 'descriptionForEdit'], 'string'],
            [['photoFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg', 'checkExtensionByMimeType' => true],
            [['active', 'page_visibility', 'page_order'], 'integer'],
            [['active', 'page_visibility'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'ФИО учителя',
            'title' => 'Специализация учителя',
            'description' => 'Текст об учителе',
            'photoFile' => 'Фото учителя (376x376)',
            'phone' => 'Телефон',
            'birthday' => 'День рождения',
            'descriptionForEdit' => 'Текст об учителе',
            'page_visibility' => 'Отображать на странице учителей',
            'active' => 'Работает ли учитель',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getWebpage()
    {
        return $this->hasOne(Webpage::class, ['id' => 'webpage_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['teacher_id' => 'id'])->inverseOf('teacher');
    }

    public function getShortName()
    {
        if ($this->_shortName === null) {
            $officialName = $this->officialName;
            $arr = explode(' ', $officialName);
            if (count($arr) > 1) {
                for ($i = 1; $i < count($arr); $i++) {
                    $arr[$i] = mb_strtoupper(mb_substr($arr[$i], 0, 1, 'UTF-8'), 'UTF-8') . '.';
                }
            }
            $this->_shortName = implode(' ', $arr);
        }
        return $this->_shortName;
    }

    public function getOfficialName()
    {
        if ($this->_officialName === null) {
            $name = $this->name;
            $arr = explode(' ', $name);
            if (count($arr) > 1) {
                $this->_officialName = $arr[1];
                if (count($arr) > 2) $this->_officialName .= ' ' . $arr[2];
            } else {
                $this->_officialName = $arr[0];
            }
        }
        return $this->_officialName;
    }

    public function getBirthdayDate()
    {
        $birthdayDate = $this->birthday ? new DateTime($this->birthday) : null;
        if ($birthdayDate) $birthdayDate->modify(date('Y') . $birthdayDate->format('-m-d'));
        return $birthdayDate;
    }

    /**
     * @return ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(Event::class, ['teacher_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTeacherSubjects()
    {
        return $this->hasMany(TeacherSubjectLink::class, ['teacher_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSubjects()
    {
        return $this->hasMany(Subject::class, ['id' => 'subject_id'])
            ->via('teacherSubjects');
    }

    /**
     * @return ActiveQuery
     */
    public function getGroups()
    {
       return $this->hasMany(Group::class, ['teacher_id' => 'id']);
    }

    /**
     * @param string $imagePath
     * @param string $maskPath
     * @param string $framePath
     * @return string
     * @throws \ImagickException
     */
    private function addPhotoFrame(string $imagePath, string $maskPath, string $framePath): string
    {
        $arr = explode('.', $imagePath);
        $arr[count($arr) - 1] = 'png';
        $fileName = implode('.', $arr);

        if (class_exists('\Imagick')) {
            $base = new \Imagick($imagePath);
            $mask = new \Imagick($maskPath);
            $over = new \Imagick($framePath);

            $base->setImageFormat('png');
            $base->setImageColorspace($over->getImageColorspace());
            $base->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);

            $base->compositeImage($mask, \Imagick::COMPOSITE_DSTIN, 0, 0, \Imagick::CHANNEL_ALPHA);
            $base->borderImage(new \ImagickPixel('rgba(0, 0, 0, 0)'), ($over->getImageWidth() - $base->getImageWidth()) / 2,($over->getImageHeight() - $base->getImageHeight()) / 2);
            $base->compositeImage($over, \Imagick::COMPOSITE_DEFAULT, 0, 0);
            $base->writeImage($fileName);

            $source = ComponentContainer::getTinifier()->getFromFile($fileName);
            $source->toFile($fileName);
        } elseif (extension_loaded('gd') && function_exists('gd_info')) {
            $baseInfo = getimagesize($imagePath);
            if ($baseInfo[2] == IMAGETYPE_PNG) $base = imagecreatefrompng($imagePath);
            else $base = imagecreatefromjpeg($imagePath);
            $mask = imagecreatefrompng($maskPath);
            $over = imagecreatefrompng($framePath);
            $overInfo = getimagesize($framePath);

            $xOffset = ($overInfo[0] - $baseInfo[0]) / 2 - round($overInfo[0] / 188);
            $yOffset = ($overInfo[1] - $baseInfo[1]) / 2 - round($overInfo[1] / 188);

            $newPicture = imagecreatetruecolor($overInfo[0], $overInfo[1]);
            imagesavealpha( $newPicture, true );
            imagefill( $newPicture, 0, 0, imagecolorallocatealpha( $newPicture, 0, 0, 0, 127 ) );

            for($x = 0; $x < $baseInfo[0]; $x++) {
                for($y = 0; $y < $baseInfo[1]; $y++) {
                    $alpha = imagecolorsforindex($mask, imagecolorat($mask, $x, $y));
                    $color = imagecolorsforindex($base, imagecolorat($base, $x, $y));

                    if ($alpha['alpha'] < 127) {
                        imagesetpixel($newPicture, $xOffset + $x, $yOffset + $y, imagecolorallocatealpha($newPicture, $color['red'], $color['green'], $color['blue'], $alpha['alpha']));
                    }
                }
            }

            imagealphablending($newPicture, true);
            imagecopy($newPicture, $over, 0, 0, 0, 0, $overInfo[0], $overInfo[1]);
            imagepng($newPicture, $fileName);

            $source = \Tinify\fromFile($fileName);
            $source->toFile($fileName);
        } else {
            $arr = explode('/', $imagePath);
            return end($arr);
        }

        if ($imagePath != $fileName) unlink($imagePath);
        $arr = explode('/', $fileName);
        return end($arr);
    }

    /**
     * @param array $config
     * @return bool
     */
    public function upload($config = [])
    {
        if ($this->uploadBasic($config)) {
            $config = $this->getUploadImageConfig();
            $imageField = $config['imageDBField'];

            $imagePath = \Yii::getAlias('@uploads/' . $config['imageFolder']) . '/' . $this->$imageField;
            $arr = explode('.', $this->$imageField);
            $arr[count($arr) - 2] .= '@2x';
            $imagePath2x = \Yii::getAlias('@uploads/' . $config['imageFolder']) . '/' . implode('.', $arr);

            if (is_file($imagePath)) {
                $maskPath = \Yii::getAlias('@app/extra') . '/teacher_mask.png';
                $framePath = \Yii::getAlias('@app/extra') . '/teacher_frame.png';
                $this->$imageField = $this->addPhotoFrame($imagePath, $maskPath, $framePath);
            }

            if (is_file($imagePath2x)) {
                $maskPath = \Yii::getAlias('@app/extra') . '/teacher_mask@2x.png';
                $framePath = \Yii::getAlias('@app/extra') . '/teacher_frame@2x.png';
                $this->addPhotoFrame($imagePath2x, $maskPath, $framePath);
            }

            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getNoPhotoUrl()
    {
        $config = $this->getUploadImageConfig();
        return \Yii::getAlias('@uploadsUrl') . '/' . $config['imageFolder'] . '/no_photo.png';
    }

    /**
     * @return string
     */
    public function getDescriptionForEdit()
    {
        if ($this->descriptionForEdit === null) $this->descriptionForEdit = self::convertTextForEditor($this->description, 'teacher_highlight');
        return $this->descriptionForEdit;
    }

    /**
     * @param string $value
     */
    public function setDescriptionForEdit($value)
    {
        $this->descriptionForEdit = $value;
    }

    /**
     * @return ActiveQuery
     */
    public static function getVisibleListQuery()
    {
        return self::find()->where(['page_visibility' => self::STATUS_ACTIVE])->orderBy('page_order');
    }

    /**
     * @return Subject[]
     */
    public static function getVisibleList()
    {
        return self::getVisibleListQuery()->all();
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) return false;
        if ($this->descriptionForEdit) {
            $this->description = self::convertTextForDB($this->descriptionForEdit, 'teacher_highlight');
        }
        return true;
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
