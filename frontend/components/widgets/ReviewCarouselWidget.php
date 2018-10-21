<?php

namespace frontend\components\widgets;

use common\models\Module;
use common\models\Review;
use common\models\Webpage;
use yii\base\Widget;

class ReviewCarouselWidget extends Widget
{
    public function run()
    {
        return $this->render('review-carousel', [
            'reviews' => Review::getActiveListQuery()->limit(5)->all(),
            'reviewsWebpage' => Webpage::find()->where(['module_id' => Module::getModuleIdByControllerAndAction('review', 'index')])->one(),
        ]);
    }
}