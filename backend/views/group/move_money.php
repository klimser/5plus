<?php

use yii\bootstrap4\Html;

/* @var $this yii\web\View */
/* @var $groupPupil \common\models\GroupPupil */
/* @var $moneyLeft int */
/* @var $groupList \common\models\Group[] */

$this->title = 'Перенести деньги студента в другую группу';
$this->params['breadcrumbs'][] = $this->title;

?>

<h1><?= Html::encode($this->title) ?></h1>

<div id="messages_place"></div>
<?= Html::beginForm(); ?>
    <div class="form-group row">
        <label class="col-12 col-sm-3 control-label">Студент</label>
        <div class="col-12 col-sm-9">
            <input readonly class="form-control-plaintext" value="<?= $groupPupil->user->name; ?> (<?= $groupPupil->user->phoneFull; ?>)">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-12 col-sm-3 control-label">Из группы</label>
        <div class="col-12 col-sm-9">
            <input readonly class="form-control-plaintext" value="<?= $groupPupil->group->name; ?>">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-12 col-sm-3 control-label">Сумма</label>
        <div class="col-12 col-sm-9">
            <input readonly class="form-control-plaintext" value="<?= $moneyLeft; ?>">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-12 col-sm-3 control-label">В группу</label>
        <div class="col-12 col-sm-9">
            <select name="money-move[groupId]" class="form-control" required autocomplete="off">
                <?php foreach ($groupList as $group): ?>
                    <option value="<?= $group->id; ?>"><?= $group->name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button class="form-group btn btn-primary">Перенести</button>
<?= Html::endForm(); ?>
