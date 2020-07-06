<?php

use common\components\DefaultValuesComponent;
use common\models\Contract;
use common\models\Payment;
use kartik\field\FieldRange;
use yii\bootstrap4\Html;
use yii\bootstrap4\LinkPager;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \common\models\PaymentSearch */
/* @var $studentMap \common\models\User[] */
/* @var $adminMap \common\models\User[] */
/* @var $groupMap \common\models\User[] */

$this->title = 'Платежи';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="payment-index">
    <div class="float-right"><a href="<?= Url::to(['money/income']); ?>" class="btn btn-info">Внести оплату</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return = [];
            if ($model->amount > 0) {
                $return['class'] = $model->discount ? 'table-info' : 'table-success';
            } elseif ($model->amount < 0) {
                $return['class'] = $model->discount ? 'table-warning' : 'table-danger';
            }
            return $return;
        },
        'columns' => [
            [
                'attribute' => 'admin_id',
                'content' => function ($model, $key, $index, $column) {
                    /** @var Payment $model */
                    return
                    ($model->contract_id && $model->contract->payment_type != Contract::PAYMENT_TYPE_MANUAL
                        ? '<span class="badge badge-info">online</span> '
                        : ''
                    )
                    . ($model->cash_received == Payment::STATUS_INACTIVE
                        ? '<span class="badge badge-danger">деньги не получены</span> '
                        : ''
                    )
                    . ($model->admin_id ? $model->admin->name : '');
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'admin_id',
                    $adminMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'user_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->user->name;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'user_id',
                    $studentMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'group_id',
                'label' => 'Группа',
                'content' => function ($model, $key, $index, $column) {
                    return Html::a($model->group->name, Url::to(['group/update', 'id' => $model->group_id]));
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'group_id',
                    $groupMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'amount',
                'filter' => FieldRange::widget([
                    'model' => $searchModel,
                    'attribute1' => 'amountFrom',
                    'attribute2' => 'amountTo',
//                    'name1'=>'amountFrom',
//                    'name2'=>'amountTo',
                    'separator' => '-',
                    'template' => '{widget}',
                    'type' => FieldRange::INPUT_TEXT,
                ]),
                'contentOptions' => ['class' => 'text-right'],
                'content' => function ($model, $key, $index, $column) {
                    return $model->amount
                        . ($model->amount < 0 && $model->used_payment_id === null
                            ? ' <span class="badge badge-warning">no</span>'
                            : ''
                        );
                },
            ],
            'comment',
            [
                'attribute' => 'created_at',
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
