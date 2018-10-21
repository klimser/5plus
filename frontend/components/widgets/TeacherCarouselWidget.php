<?php

namespace frontend\components\widgets;

use common\models\Module;
use common\models\Teacher;
use common\models\Webpage;
use yii\base\Widget;

class TeacherCarouselWidget extends Widget
{
    public function run()
    {
        return $this->render('teacher-carousel', [
            'teachers' => Teacher::getVisibleListQuery()->orderBy('rand()')->all(),
            'teachersWebpage' => Webpage::find()->where(['module_id' => Module::getModuleIdByControllerAndAction('teacher', 'index')])->one(),
        ]);
    }
}