<?php

use yii\bootstrap\Html;

/* @var $this yii\web\View */
/* @var $user \common\models\User */
/* @var $group \common\models\Group */
/* @var $moneyLeft int */
/* @var $groupList \common\models\Group[] */

$this->title = 'Перенести деньги студента в другую группу';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="row move-pupil">
    <h1><?= Html::encode($this->title) ?></h1>

    <div id="messages_place"></div>
    <?= Html::beginForm(); ?>
        <div class="col-xs-12">
            <table class="table table-bordered table-condensed">
                <tr>
                    <td>Студент</td>
                    <td><?= $user->name; ?> (<?= $user->phoneFull; ?>)</td>
                </tr>
                <tr>
                    <td>Группа</td>
                    <td><?= $group->name; ?></td>
                </tr>
                <tr>
                    <td>Остаток денег</td>
                    <td><?= $moneyLeft; ?></td>
                </tr>
                <tr>
                    <td><label for="group_to">Куда переносим</label></td>
                    <td>
                        <select id="group_to" name="group_to" class="form-control" required>
                            <?php foreach ($groupList as $group): ?>
                                <option value="<?= $group->id; ?>"><?= $group->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <button class="btn btn-primary">Перенести</button>
                    </td>
                </tr>
            </table>
        </div>
    <?= Html::endForm(); ?>
</div>