<?php

use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $courseMap array */

$this->title = 'Отсутствуют на занятиях';
$this->params['breadcrumbs'][] = $this->title;

?>

<table class="table table-sm">
    <?php foreach ($courseMap as $courseId => $data):
        if (empty($data['students'])) continue;
    ?>
        <tr>
            <th colspan="4">
                <a href="<?= Url::to(['missed/table', 'courseId' => $courseId]); ?>" target="_blank">
                    <?= $data['entity']->courseConfig->name; ?>
                </a>
            </th>
        </tr>
        <?php foreach ($data['students'] as $studentData): ?>
            <tr>
                <td><?= $studentData['courseStudent']->user->name; ?></td>
                <td>
                    <span class="text-nowrap"><?= $studentData['courseStudent']->user->phoneFull; ?></span>
                    <?php if ($studentData['courseStudent']->user->phone2): ?>
                        <br>
                        <span class="text-nowrap"><?= $studentData['courseStudent']->user->phone2Full; ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= implode('<hr class="thin">',
                    array_map(function($call) {
                        /** @var \backend\models\UserCall $call */
                        return $call->createDate->format('d.m.y H:i') . ' '
                            . $call->admin->name . ' '
                            . '<i>' . $call->comment . '</i>';
                    }, $studentData['calls'])); ?>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-dark" onclick="return Missed.showCall(<?= $studentData['courseStudent']->id; ?>, this);">
                        <span class="fas fa-phone"></span>
                    </button>
                    <div id="phone_call_<?= $studentData['courseStudent']->id; ?>"></div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>
</table>
