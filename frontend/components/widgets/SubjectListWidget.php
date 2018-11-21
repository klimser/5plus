<?php

namespace frontend\components\widgets;


use common\models\SubjectCategory;
use yii\base\Widget;

class SubjectListWidget extends Widget
{
    /** @var SubjectCategory */
    public $subjectCategory;
    /** @var bool */
    public $buttonLeft = false;
    /** @var int */
    public $index;

    public function run()
    {
        $activeCategories = SubjectCategory::find()
            ->joinWith('activeSubjects')
            ->with('activeSubjects.webpage')
            ->all();
        return $this->render('subject-list', ['subjectCategories' => $activeCategories]);
    }
}