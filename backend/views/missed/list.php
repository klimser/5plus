<?php

/* @var $this yii\web\View */
/* @var $resultMap \common\models\User[][] */
/* @var $groupMap common\models\Group[] */

$this->title = 'Отсутствуют на занятиях';
$this->params['breadcrumbs'][] = $this->title;

?>

<?php foreach ($resultMap as $groupId => $users):
    if (empty($users)) continue;
?>
    <table class="table table-condensed">
        <tr>
            <th colspan="2"><?= $groupMap[$groupId]->name; ?></th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user->name; ?></td>
                <td>
                    <?= $user->phoneFull; ?>
                    <?php if ($user->phone2): ?>
                        <br>
                        <?= $user->phone2Full; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endforeach; ?>