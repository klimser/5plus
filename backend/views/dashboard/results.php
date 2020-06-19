<?php

/* @var $this yii\web\View */

use common\models\User;
use yii\helpers\Url;

/* @var $contract \common\models\Contract */
/* @var $giftCard \common\models\GiftCard */
/* @var $existingPupil User */
/* @var $parents User[] */
/* @var $pupils User[] */
/* @var $showAddPupil bool */

$noResults = true;

if ($contract): 
    $noResults = false; ?>
    <?= $this->render('_result_contract', ['contract' => $contract]); ?>
<?php endif;

if ($giftCard):
    $noResults = false; ?>
    <?= $this->render('_result_gift_card', ['giftCard' => $giftCard, 'existingPupil' => $existingPupil]); ?>
<?php endif;

if ($parents):
    $noResults = false;
    foreach ($parents as $parent): ?>
    <div class="card border-info result-parent">
        <div class="card-header text-white bg-info justify-content-between align-items-center d-flex px-3 py-2">
            <div class="font-weight-bold">
                <span class="badge badge-secondary mr-2"><?= $parent->role === User::ROLE_PARENTS ? 'родитель' : 'компания'; ?></span>
                <?= $parent->name; ?>
            </div>
            <button class="btn btn-light" onclick="Dashboard.toggleChildren(this);">
                <span class="fas fa-arrow-alt-circle-down"></span>
            </button>
        </div>
        <div class="card-body collapse children-list accordion px-0 py-3">
            <?php foreach ($parent->children as $child): ?>
                <?= $this->render('_result_pupil', ['pupil' => $child]); ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach;
endif;

if ($pupils):
    $noResults = false;
    foreach ($pupils as $pupil): ?>
        <?= $this->render('_result_pupil', ['pupil' => $pupil]); ?>
<?php endforeach;
endif;

if ($noResults): ?>
<div class="alert alert-warning">
    Ничего не найдено
    <br><br>
    <?php if ($showAddPupil): ?>
        <a href="<?= Url::to(['user/create-pupil']); ?>" target="_blank" class="btn btn-info">Добавить студента</a>
    <?php else: ?>
        <b>Попробуйте поискать по фамилии или имени</b>
    <?php endif; ?>
</div>
<?php endif; ?>
