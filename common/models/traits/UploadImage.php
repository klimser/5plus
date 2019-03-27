<?php

namespace common\models\traits;
use backend\components\TranslitComponent;
use common\components\ComponentContainer;
use Tinify\Source;
use yii\base\UnknownPropertyException;
use yii\web\UploadedFile;

/**
 * Trait UploadImage
 * @package common\models\traits
 *
 * @property string $imageUrl
 */
trait UploadImage
{
    /**
     * @return array
     */
    protected abstract function getUploadImageConfig(): array;
    /**
     * @param string[]|string|null $attributeNames
     * @param bool $clearErrors
     * @return bool
     */
    public abstract function validate($attributeNames = null, $clearErrors = true);
    /**
     * @param string $attribute
     * @param string $error
     */
    public abstract function addError($attribute, $error = '');
    /**
     * @param string
     * @return array
     */
    public abstract function getErrors($attribute = null);
    /**
     * @param string $filename
     */
    public abstract static function deleteImages($filename);

    /**
     * @param null|string $uploadedField
     * @return bool
     */
    private function returnConfigError(?string $uploadedField = null)
    {
        if ($uploadedField) $this->addError($uploadedField, 'Model is not properly configured for image upload, call developer');
        return false;
    }

    /**
     * @param array $config
     * @return bool
     */
    public function upload($config = [])
    {
        if (empty($config)) $config = $this->getUploadImageConfig();

        if (!array_key_exists('neededImageWidth', $config)
            || !array_key_exists('neededImageHeight', $config)
            || !array_key_exists('imageFolder', $config)
            || !array_key_exists('imageDBField', $config)
            || !array_key_exists('imageFilenameBase', $config)
        ) return $this->returnConfigError();

        $mandatory2x = array_key_exists('mandatory2x', $config) ? $config['mandatory2x'] : false;
        $imageField = $config['imageDBField'];
        $uploadedField = $imageField . 'File';
        $filenameBase = $config['imageFilenameBase'];
        $filenameAppendix = '';
        try {
            $this->$imageField;
            $this->$uploadedField;
            $this->$filenameBase;
            if (array_key_exists('imageFilenameAppendix', $config)) {
                $filenameAppendix = $config['imageFilenameAppendix'];
                $this->$filenameAppendix;
            }
        } catch (UnknownPropertyException $ex) {
            return $this->returnConfigError($uploadedField);
        }

        if ($this->$uploadedField instanceof UploadedFile && $this->$uploadedField->hasError) {
            return false;
        }

        if ($this->validate()) {
            $imageWidth = 0;
            $imageHeight = 0;
            if (class_exists('\Imagick')) {
                $image = new \Imagick($this->$uploadedField->tempName);
                $imageWidth = $image->getImageWidth();
                $imageHeight = $image->getImageHeight();

            } elseif (extension_loaded('gd') && function_exists('gd_info')) {
                $params = getimagesize($this->$uploadedField->tempName);
                if ($params) {
                    $imageWidth = $params[0];
                    $imageHeight = $params[1];
                }
            } else {
                $this->addError($uploadedField, 'Cannot determine image size, call admin!');
                return false;
            }

            if ($imageWidth < $config['neededImageWidth'] || ($mandatory2x && $imageWidth < $config['neededImageWidth'] * 2))
                $this->addError($uploadedField, 'Image is too small, min width - ' . $config['neededImageWidth'] . 'px');
            if ($config['neededImageHeight'] > 0) {
                if ($imageHeight < $config['neededImageHeight'] || ($mandatory2x && $imageHeight < $config['neededImageHeight'] * 2))
                    $this->addError($uploadedField, 'Image is too small, min height - ' . $config['neededImageHeight'] . 'px');
                if ($config['neededImageWidth']  != round($imageWidth * $config['neededImageHeight'] / $imageHeight))
                    $this->addError($uploadedField, 'Image has wring proportions, upload image proportional to ' . $config['neededImageWidth'] . 'x' . $config['neededImageHeight']);
            }
            if ($this->getErrors($uploadedField)) return false;

            if (!is_dir(\Yii::getAlias('@uploads/' . $config['imageFolder']))) {
                mkdir(\Yii::getAlias('@uploads/' . $config['imageFolder']), 0755, true);
            }

            $fileName = TranslitComponent::filename($this->$filenameBase);
            if ($filenameAppendix) $fileName .= '_' . $this->$filenameAppendix;
            $fileName .= '_' . time();
            $fileName2x = $fileName . '@2x.' . $this->$uploadedField->extension;
            $fileName .= '.' . $this->$uploadedField->extension;

            $resizeImage = function(string $sourcePath, string $destPath, int $width, int $height = 0) use ($config) {
                if ((!array_key_exists('skipTinify', $config) || !$config['skipTinify']) && $source = ComponentContainer::getTinifier()->getFromFile($sourcePath)) {
                    $params = ['method' => 'scale', 'width' => $width];
                    if ($height > 0) {
                        $params['height'] = $height * 2;
                        $params['method'] = 'fit';
                    }

                    $source->resize($params)->toFile($destPath);
                } elseif (class_exists('\Imagick')) {
                    $image = new \Imagick($sourcePath);
                    $image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
                    $image->writeImage($destPath);
                } elseif (extension_loaded('gd') && function_exists('gd_info')) {
                    $params = getimagesize($sourcePath);
                    $imageWidth = $params[0];
                    $imageHeight = $params[1];
                    if ($params[2] == IMAGETYPE_PNG) $image = imagecreatefrompng($sourcePath);
                    elseif ($params[2] == IMAGETYPE_JPEG) $image = imagecreatefromjpeg($sourcePath);
                    else throw new \Exception('Wrong image type');
                    if ($image) {
                        $scaleWidth = $width;
                        if ($height == 0) $scaleHeight = -1;
                        else {
                            $scaleHeight = $imageHeight * $scaleWidth / $imageWidth;
                            if ($scaleHeight > $height) {
                                $scaleHeight = $height;
                                $scaleWidth = $imageWidth * $scaleHeight / $imageHeight;
                            }
                        }
                        $scaled = imagescale($image, $scaleWidth, $scaleHeight, IMG_BICUBIC);

                        if ($params[2] == IMAGETYPE_PNG) imagepng($scaled, $destPath);
                        elseif ($params[2] == IMAGETYPE_JPEG) imagejpeg($scaled, $destPath, 100);
                        imagedestroy($image);
                        imagedestroy($scaled);
                    }
                }
            };

            try {
                if ($imageWidth >= $config['neededImageWidth'] * 2) {
                    $resizeImage(
                        $this->$uploadedField->tempName,
                        \Yii::getAlias('@uploads/' . $config['imageFolder'] . '/') . $fileName2x,
                        $config['neededImageWidth'] * 2,
                        $config['neededImageHeight'] * 2
                    );
                }

                $resizeImage(
                    $this->$uploadedField->tempName,
                    \Yii::getAlias('@uploads/' . $config['imageFolder'] . '/') . $fileName,
                    $config['neededImageWidth'],
                    $config['neededImageHeight']
                );

                if ($this->$imageField && $this->$imageField != $fileName) {
                    self::deleteImages(\Yii::getAlias('@uploads/' . $config['imageFolder'] . '/') . $this->$imageField);
                }
                $this->$imageField = $fileName;
            } catch (\Exception $ex) {
                $this->addError($uploadedField, $ex->getMessage());
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $config
     * @return string
     */
    public function getImageUrl($config = []): string
    {
        if (empty($config)) $config = $this->getUploadImageConfig();
        if (!array_key_exists('imageDBField', $config)) return '';
        $imageField = $config['imageDBField'];
        try {
            $this->$imageField;
        } catch (UnknownPropertyException $ex) {return '';}

        return \Yii::getAlias('@uploadsUrl') . '/' . $config['imageFolder'] . '/' . $this->$imageField;
    }

    /**
     * @param array $config
     * @return bool
     */
    public function deleteImage($config = []): bool
    {
        if (empty($config)) $config = $this->getUploadImageConfig();
        if (!array_key_exists('imageDBField', $config)) return false;
        $imageField = $config['imageDBField'];
        try {
            $this->$imageField;
        } catch (UnknownPropertyException $ex) {return false;}

        self::deleteImages(\Yii::getAlias('@uploads/' . $config['imageFolder'] . '/') . $this->$imageField);
        $this->$imageField = null;
        return true;
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $config = $this->getUploadImageConfig();
        $images = array_key_exists('onDelete', $config) ? $config['onDelete'] : ['getUploadImageConfig'];

        foreach ($images as $image) {
            $imageConfig = $this->$image();
            if (array_key_exists('imageDBField', $imageConfig)) {
                $imageField = $imageConfig['imageDBField'];
                try {
                    if ($this->$imageField) self::deleteImages(\Yii::getAlias('@uploads/' . $imageConfig['imageFolder'] . '/') . $this->$imageField);
                } catch (UnknownPropertyException $ex) {}
            }
        }
    }
}