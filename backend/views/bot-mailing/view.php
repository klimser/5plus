<?php

use yii\bootstrap4\Html;
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
                    return $model->message_image
                        ? Html::img(Yii::getAlias('@uploadsUrl') . $model->message_image, ['class' => 'img-fluid', 'style' => 'max-width: 200px;'])
                        : '-';
                },
            ],
            [
                'attribute' => 'data',
                'format' => 'raw',
                'header' => 'Результат',
                'value' => function ($model, $widget) {
                    /** @var \common\models\BotMailing $model */
                    $content = '';
                    if ($model->process_result) {
                        $buttonLabel = 'Успешно: ' . $model->process_result['success'];
                        if ($model->process_result['error']) {
                            $buttonLabel .= "\nНеуспешно: " . $model->process_result['error'];
                        }
                        $content = '<button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#mailing-result" aria-expanded="false" aria-controls="mailing-result">'
                            . $buttonLabel . '</button>
                            <div class="collapse mt-3" id="mailing-result">';
                        foreach ($model->usersResult as $result) {
                            $content .= $result['user']->name . ' - ' . (
                                    $result['result']['status'] === 'ok'
                                        ? '<span class="badge badge-success">ok</span>'
                                        : '<span class="badge badge-danger">error</span> ' . $result['result']['error_message']
                                ) . '<br>';
                        }
                        $content .= '</div>';
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
