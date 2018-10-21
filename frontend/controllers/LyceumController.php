<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\HighSchool;
use common\models\Webpage;

class LyceumController extends Controller
{
    /**
     * Displays a Highschools page.
     * @param $webpage Webpage
     * @return mixed
     */
    public function actionIndex($webpage)
    {
        $highSchools = HighSchool::find()->where(['active' => HighSchool::STATUS_ACTIVE, 'type' => HighSchool::TYPE_LYCEUM])->orderBy('page_order')->all();
        return $this->render('/high-school/index', [
            'highSchools' => $highSchools,
            'webpage' => $webpage,
            'h1' => $webpage->title,
        ]);
    }
}
