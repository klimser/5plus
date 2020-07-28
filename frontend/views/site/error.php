<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\bootstrap4\Html;

$this->title = $name;
?>

<div class="container">
    <div class="content-box">
        <div class="site-error">
        
            <?php if (property_exists($exception, 'statusCode') && $exception->statusCode == 404): ?>
                <h1>Приносим извинения, но данная страница не существует.</h1>
                <hr>
                <?= \frontend\components\widgets\SubjectListWidget::widget(); ?>
            <?php else: ?>
                <h1><?= Html::encode($this->title) ?></h1>
        
                <div class="alert alert-danger">
                    <?= nl2br(Html::encode($message)) ?>
                </div>
        
                <p>
                    The above error occurred while the Web server was processing your request.
                </p>
                <p>
                    Please contact us if you think this is a server error. Thank you.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
