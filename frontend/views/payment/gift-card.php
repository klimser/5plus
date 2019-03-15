<?php
/* @var $this \frontend\components\extended\View */
/* @var $giftCard \common\models\GiftCard */
?>

<tr>
    <th>Студент</th>
    <td><?= $giftCard->customer_name; ?></td>
</tr>
<tr>
    <th>Предмет</th>
    <td><?= $giftCard->name ?></td>
</tr>
<?php if ($giftCard->status == \common\models\GiftCard::STATUS_PAID): ?>
    <tr>
        <th>Квитанция</th>
        <td>
            <a href="<?= \yii\helpers\Url::to(['payment/print', 'gc' => $giftCard->code]); ?>" target="_blank">
                <span class="fas fa-print"></span> Распечатать
            </a><br>
            Квитанция также отправлена на указанный вами e-mail, вы можете распечатать её из письма в любое время.
        </td>
    </tr>
<?php endif; ?>