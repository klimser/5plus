<?php
/* @var $this yii\web\View */
/* @var $pupil \common\models\User */
?>
<div class="consultation-mandatory mt-2">
    <?php if (count($pupil->consultations) > 0): ?>
        <table class="table table-bordered table-sm">
            <tr>
                <th>Предмет</th>
                <th>Дата</th>
                <th>Кто проводил</th>
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
<div class="consultation-optional mt-2"></div>
<button type="button" class="btn btn-success" onclick="User.addConsultation(0, $(this).closest('.user-view'));"><span class="fas fa-plus"></span> добавить</button>
