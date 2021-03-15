<?php

use common\components\helpers\Calendar;
use common\components\helpers\WordForm;
use common\models\Group;
use common\models\GroupSearch;
use common\models\Subject;
use common\models\Teacher;
use yii\bootstrap4\LinkPager;
use yii\grid\ActionColumn;
use yii\helpers\ArrayHelper;
use yii\bootstrap4\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;
use yii\web\View;

/* @var $this View */
/* @var $searchModel GroupSearch */
/* @var $dataProvider ActiveDataProvider */
/* @var $subjectMap Subject[] */
/* @var $teacherMap Teacher[] */
/* @var $canEdit bool */
/* @var $isTeacher bool */

$this->title = 'Группы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="group-index">

    <div class="float-right"><a href="<?= Url::to(['inactive']); ?>">Завершённые группы</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render(
            $isTeacher ? '_index_teacher' : '_index_manager',
            [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'subjectMap' => $subjectMap,
                'teacherMap' => $teacherMap,
                'canEdit' => $canEdit
            ]
    ); ?>
</div>
