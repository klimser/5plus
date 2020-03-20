<?php
/* @var $this yii\web\View */
/* @var $pupil \common\models\User */
?>
<div id="consultation-mandatory" style="margin-top: 10px;">
    <?php if (count($pupil->consultations) > 0): ?>
        <table class="table table-bordered table-condensed">
            <tr>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <?php foreach ($pupil->consultations as $consultation): ?>
                <tr>
                    <td><?= $consultation->subject->name; ?></td>
                    <td><?= $consultation->createDate->format('d.m.Y H:i'); ?></td>
                    <td><?= $consultation->createdAdmin->name; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<div id="consultation-optional" style="margin-top: 10px;"></div>
<button type="button" class="btn btn-success" onclick="User.addConsultation();"><span class="fas fa-plus"></span> добавить</button>
