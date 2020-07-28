<?php

use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $groupMap array */

$this->title = 'Отсутствуют на занятиях';
$this->params['breadcrumbs'][] = $this->title;

?>

<table class="table table-sm">
    <?php foreach ($groupMap as $groupId => $data):
        if (empty($data['pupils'])) continue;
    ?>
        <tr>
            <th colspan="4">
                <a href="<?= Url::to(['missed/table', 'groupId' => $groupId]); ?>" target="_blank">
                    <?= $data['entity']->name; ?>
                </a>
            </th>
        </tr>
        <?php foreach ($data['pupils'] as $pupilData): ?>
            <tr>
                <td><?= $pupilData['groupPupil']->user->name; ?></td>
                <td>
                    <span class="text-nowrap"><?= $pupilData['groupPupil']->user->phoneFull; ?></span>
                    <?php if ($pupilData['groupPupil']->user->phone2): ?>
                        <br>
                        <span class="text-nowrap"><?= $pupilData['groupPupil']->user->phone2Full; ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= implode('<hr class="thin">',
                    array_map(function($call) {
                        /** @var \backend\models\UserCall $call */
                        return $call->createDate->format('d.m.y H:i') . ' '
                            . $call->admin->name . ' '
                            . '<i>' . $call->comment . '</i>';
                    }, $pupilData['calls'])); ?>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-dark" onclick="return Missed.showCall(<?= $pupilData['groupPupil']->id; ?>, this);">
                        <span class="fas fa-phone"></span>
                    </button>
                    <div id="phone_call_<?= $pupilData['groupPupil']->id; ?>"></div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>
</table>
