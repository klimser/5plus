<?php

namespace frontend\components\extended;


use common\models\Webpage;
use yii\bootstrap\Html;

class View extends \yii\web\View
{
    /**
     * @param Webpage $webpage
     */
    public function fillMetaFromWebpage(Webpage $webpage)
    {
        $this->title = $webpage->title;
        if ($webpage->description) {
            $this->registerMetaTag([
                'name' => 'description',
                'content' => $webpage->description,
            ]);
        }
        if ($webpage->keywords) {
            $this->registerMetaTag([
                'name' => 'keywords',
                'content' => $webpage->keywords,
            ]);
        }
    }

    public function getMainPicture() {
        $folder = \Yii::getAlias('@uploads/main_slider/');
        if (!file_exists($folder)) return '';
        $draftArray = scandir($folder);
        $files = [];
        foreach ($draftArray as $elem) {
            if ($elem != '.' && $elem != '..' && !preg_match('#@2x#', $elem)) $files[] = $elem;
        }
        if (empty($files)) return '';
        $index = rand(0, count($files) - 1);

        return Html::img(\Yii::getAlias('@uploadsUrl/main_slider/' . $files[$index]));
    }
}