<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $botMailing common\models\BotMailing */

$this->title = 'Рассылка ' . $botMailing->createDateString;
$this->params['breadcrumbs'][] = ['label' => 'Рассылки', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="bot-mailing-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $botMailing,
        'attributes' => [
            [
                'attribute' => 'admin_id',
                'value' => function ($model, $widget) {
                    return $model->admin_id ? $model->admin->name : '';
                },
            ],
            [
                'attribute' => 'message_text',
                'format' => 'html',
                'value' => function ($model, $widget) {
                    return nl2br($model->message_text);
                },
            ],
            [
                'attribute' => 'message_image',
                'format' => 'html',
                'value' => function ($model, $widget) {
                    return $model->message_image ? Html::img(Yii::getAlias('@uploadsUrl') . $model->message_image, ['class' => 'max-width-100']) : '-';
                },  
            ],
            [
                'attribute' => 'data',
                'format' => 'raw',
                'header' => 'Результат',
                'value' => function ($model, $widget) {
                    /** @var \common\models\BotMailing $model */
                    $content = '';
                    if ($model->processResult) {
                        $buttonLabel = 'Успешно: ' . $model->processResult['success'];
                        if ($model->processResult['error']) {
                            $buttonLabel .= "\nНеуспешно: " . $model->processResult['error'];
                        }
                        $content = '<button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#mailing-result" aria-expanded="false" aria-controls="mailing-result">'
                            . $buttonLabel . '</button>
                            <div class="collapse" id="mailing-result">
                              <div class="well">';
                        foreach ($model->usersResult as $result) {
                            $content .= $result['user']->name . ' - ' . (
                                    $result['result']['status'] === 'ok'
                                        ? '<span class="label label-success">ok</span>'
                                        : '<span class="label label-success">error</span> ' . $result['result']['error_message']
                                ) . '<br>';
                        }
                        $content .= '</div>
                            </div>
                        ';
                    }
                    return $content;
                }
            ],
            'created_at:datetime',
            'started_at:datetime',
            'finished_at:datetime',
        ],
    ]) ?>

</div>
