<?php

/* @var $this yii\web\View */

use common\models\User;
use yii\helpers\Url;

/* @var $contract \common\models\Contract */
/* @var $giftCard \common\models\GiftCard */
/* @var $existingStudent User */
/* @var $parents User[] */
/* @var $students User[] */
/* @var $showAddStudent bool */

$noResults = true;

if ($contract): 
    $noResults = false; ?>
    <?= $this->render('_result_contract', ['contract' => $contract]); ?>
<?php endif;

if ($giftCard):
    $noResults = false; ?>
    <?= $this->render('_result_gift_card', ['giftCard' => $giftCard, 'existingStudent' => $existingStudent]); ?>
<?php endif;

$studentIdSet = [];
if ($students):
    $noResults = false;
    foreach ($students as $student):
        $studentIdSet[$student->id] = true;
        ?>
        <?= $this->render('_result_student', ['student' => $student]); ?>
    <?php endforeach;
endif;

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
            <?php foreach ($parent->notLockedChildren as $child):
                if (!isset($studentIdSet[$child->id])): ?>
                    <?= $this->render('_result_student', ['student' => $child]); ?>
                <?php endif;
            endforeach; ?>
        </div>
    </div>
<?php endforeach;
endif;

if ($noResults): ?>
<div class="alert alert-warning">
    Ничего не найдено
    <br><br>
    <?php if ($showAddStudent): ?>
        <a href="#" target="_blank" class="btn btn-info" onclick="Dashboard.showCreateStudentForm(); return false;">Добавить студента</a>
    <?php else: ?>
        <b>Попробуйте поискать по фамилии или имени</b>
    <?php endif; ?>
</div>
<?php endif; ?>
