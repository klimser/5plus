<?php

namespace frontend\components\widgets;


use common\models\Subject;
use common\models\SubjectCategory;
use yii\base\Widget;

class SubjectCarouselWidget extends Widget
{
    /** @var SubjectCategory */
    public $subjectCategory;
    /** @var bool */
    public $buttonLeft = false;
    /** @var int */
    public $index;

    public function run()
    {
        return $this->render('subject-carousel', ['subjectCategory' => $this->subjectCategory, 'buttonLeft' => $this->buttonLeft, 'index' => $this->index]);
    }
}