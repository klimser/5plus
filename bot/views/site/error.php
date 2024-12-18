<?php

/* @var $this yii\web\View */
/* @var $message string */
/* @var $exception Exception */

use yii\bootstrap4\Html;

?>

<?php if (property_exists($exception, 'statusCode') && $exception->statusCode == 404): ?>
    <h1>Приносим извинения, но данная страница не существует.</h1>
    <hr>
<?php else: ?>
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