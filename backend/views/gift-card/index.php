<?php

use common\components\DefaultValuesComponent;
use common\components\helpers\Html;
use common\models\Contract;
use common\models\GiftCard;
use common\models\GiftCardSearch;
use yii\bootstrap4\LinkPager;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;
use yii\jui\DatePicker;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel GiftCardSearch */
/* @var $status string */

$this->title = 'Предоплаченные карты';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="gift-card-type-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link <?= $status === null ? ' active ' : ''; ?>" href="<?= Url::to(['gift-card/index']); ?>">Все</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status === GiftCard::STATUS_NEW ? ' active ' : ''; ?>" href="<?= Url::to(['gift-card/index', 'status' => GiftCard::STATUS_NEW]); ?>">Не оплаченные</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status == GiftCard::STATUS_PAID ? ' active ' : ''; ?>" href="<?= Url::to(['gift-card/index', 'status' => GiftCard::STATUS_PAID]); ?>">Оплаченные</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status == GiftCard::STATUS_USED ? ' active ' : ''; ?>" href="<?= Url::to(['gift-card/index', 'status' => GiftCard::STATUS_USED]); ?>">Активированные</a>
        </li>
    </ul>
    <hr>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return  = [];
            switch ($model->status) {
                case GiftCard::STATUS_PAID:
                    $return['class'] = 'table-success';
                    break;
                case GiftCard::STATUS_USED:
                    $return['class'] = 'table-warning';
                    break;
            }
            return $return;
        },
        'columns' => [
            'name',
            'amount',
            'customer_name',
            [
                'attribute' => 'customer_phone',
                'content' => function ($model, $key, $index, $column) {
                    return Html::phoneLink($model->customer_phone, $model->phoneFormatted);
                },
            ],
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
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
            [
                'attribute' => 'paid_at',
                'format' => 'datetime',
                'filter' => DatePicker::widget(ArrayHelper::merge(
                    DefaultValuesComponent::getDatePickerSettings(),
                    [
                        'model' => $searchModel,
                        'attribute' => 'paidDateString',
                        'dateFormat' => 'y-M-dd',
                        'options' => [
                            'pattern' => '\d{4}-\d{2}-\d{2}',
                        ],
                    ])),
                'content' => function($model, $key, $index, $column) {
                    return (!empty($model->additionalData['payment_method'])
                            ? '<span class="badge badge-info">' . Contract::PAYMENT_TYPE_LABELS[$model->additionalData['payment_method']] . '</span><br>'
                            : '')
                        . Yii::$app->formatter->asDate($model->paid_at, 'long');
                },
            ],
            [
                'attribute' => 'used_at',
                'format' => 'datetime',
                'filter' => DatePicker::widget(ArrayHelper::merge(
                    DefaultValuesComponent::getDatePickerSettings(),
                    [
                        'model' => $searchModel,
                        'attribute' => 'usedDateString',
                        'dateFormat' => 'y-M-dd',
                        'options' => [
                            'pattern' => '\d{4}-\d{2}-\d{2}',
                        ],
                    ])),
            ],
        ],
    ]); ?>
</div>
