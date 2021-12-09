<?php

use backend\models\ActionSearch;
use common\components\Action;
use common\components\DefaultValuesComponent;
use common\models\User;
use kartik\field\FieldRange;
use yii\bootstrap4\Html;
use yii\bootstrap4\LinkPager;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel ActionSearch */
/* @var $adminMap string[] */
/* @var $groupMap string[] */
/* @var $typeMap string[] */

$this->title = 'Действия';
$this->params['breadcrumbs'][] = $this->title;
$this->registerJs('Main.initAutocompleteUser("#search-student");');

$renderTable = function(array $arr)
{
    $html = '<table class="table table-striped table-sm break-word">';
    foreach ($arr as $key => $value) {
        $html .= "<tr><td><b>$key</b></td><td>$value</td></tr>";
    }
    $html .= '</table>';
    return $html;
}

?>
<div class="actions-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return = [];
            if ($model->amount > 0) $return['class'] = 'table-success';
            elseif ($model->amount < 0) $return['class'] = 'table-danger';
            return $return;
        },
        'columns' => [
            [
                'attribute' => 'admin_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->admin ? $model->admin->name : '';
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'admin_id',
                    $adminMap,
                    ['class' => 'form-control']
                ),
            ],
            [
                'attribute' => 'type',
                'content' => function ($model, $key, $index, $column) {
                    return Action::TYPE_LABELS[$model->type];
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'type',
                    $typeMap,
                    ['class' => 'form-control']
                ),
            ],
            [
                'attribute' => 'user_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->user_id ? $model->user->name : null;
                },
                'filter' => '<div><input type="hidden" class="autocomplete-user-id" name="ActionSearch[user_id]" value="' . $searchModel->user_id . '">
                            <input id="search-student" class="autocomplete-user form-control" placeholder="начните печатать фамилию или имя" data-role="' . User::ROLE_PUPIL . '"
                            value="' . ($searchModel->user_id ? $searchModel->user->name : '') . '"></div>',
            ],
            [
                'attribute' => 'group_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->group_id ? $model->group->name : null;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'group_id',
                    $groupMap,
                    ['class' => 'form-control']
                ),

            ],
            [
                'attribute' => 'amount',
                'filter' => FieldRange::widget([
                    'model' => $searchModel,
                    'name1'=>'amountFrom',
                    'name2'=>'amountTo',
                    'separator' => '-',
                    'template' => '{widget}',
                    'type' => FieldRange::INPUT_TEXT,
                ]),
                'contentOptions' => ['class' => 'text-right'],
            ],
            [
                'attribute' => 'comment',
                'content' => function ($model, $key, $index, $column) use ($renderTable) {
                    $decodedData = json_decode($model->comment, true);
                    return is_array($decodedData) ? $renderTable($decodedData) : $model->comment;
                },
            ],
            [
                'attribute' => 'createDate',
                'format' => 'datetime',
                'label' => 'Дата операции',
                'filter' => DatePicker::widget(ArrayHelper::merge(
                    DefaultValuesComponent::getDatePickerSettings(),
                    [
                        'model' => $searchModel,
                        'attribute' => 'createDateString',
                        'dateFormat' => 'y-M-dd',
                        'options' => [
                            'pattern' => '\d{4}-\d{2}-\d{2}',
                        ],
                    ])),
            ],
        ],
    ]); ?>
</div>
