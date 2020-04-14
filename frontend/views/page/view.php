<?php

/* @var $this \frontend\components\extended\View */
/* @var $page common\models\Page */
/* @var $webpage common\models\Webpage */
/* @var $feedback \common\models\Feedback */
/* @var $subjectCategoryCollection array */
/* @var $reviews array */

$this->params['breadcrumbs'][] = $page->title;

if ($webpage->url === \common\models\Feedback::PAGE_URL): ?>
    <?= $page->content; ?>
    <section class="leave-question-box">
        <div class="container">
            <h2 class="block-title">ЕСЛИ У ВАС ЕСТЬ ВОПРОСЫ, ВЫ МОЖЕТЕ ОБРАТИТЬСЯ К НАМ:</h2>
            <?= $this->render('feedback-form'); ?>
        </div>
    </section>


    <?php /*<hr>
    <div class="row">
        <div class="col-xs-12">
            <h4 class="text-uppercase">Если у вас есть вопросы, вы можете обратиться к нам:</h4>
            
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2997.547994728739!2d69.27230241542306!3d41.29694087927265!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xad929af041a2d7f5!2zNSDRgSDQv9C70Y7RgdC-0Lw!5e0!3m2!1sru!2sru!4v1536061704121" width="100%" height="400" frameborder="0" style="border:0" allowfullscreen></iframe>
            <div class="clearfix"></div>
        </div>
    </div> */ ?>
<?php else: ?>
    <div class="container">
        <?php if ($webpage->url !== 'price'): ?>
        <div class="content-box">
        <?php endif; ?>
            <?= $page->content; ?>
        <?php if ($webpage->url !== 'price'): ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
