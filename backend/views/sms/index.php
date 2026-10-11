<?php

use backend\controllers\SmsController;
use common\components\SmsServiceComponent;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $messages array */
/* @var $error string|null */
/* @var $isRoot bool */
/* @var $monthList array */
/* @var $period string */
/* @var $dateFrom DateTimeImmutable|null */
/* @var $dateTo DateTimeImmutable|null */
/* @var $phone string */
/* @var $page int */
/* @var $hasNextPage bool */

$this->title = 'SMS';
$this->params['breadcrumbs'][] = $this->title;

$isCustom = SmsController::PERIOD_CUSTOM === $period;
// Format of datetime-local input value
$inputFormat = 'Y-m-d\TH:i';
$pageUrl = static function (int $page) use ($period, $dateFrom, $dateTo, $phone, $isCustom, $inputFormat): string {
    $params = ['sms/index', 'period' => $period, 'phone' => $phone, 'page' => $page];
    if ($isCustom) {
        $params['date_from'] = $dateFrom ? $dateFrom->format($inputFormat) : '';
        $params['date_to'] = $dateTo ? $dateTo->format($inputFormat) : '';
    }

    return Url::to($params);
};
$statusClasses = [
    SmsServiceComponent::STATUS_NEW => 'secondary',
    SmsServiceComponent::STATUS_QUEUED => 'info',
    SmsServiceComponent::STATUS_SENT => 'success',
    SmsServiceComponent::STATUS_ERROR => 'danger',
];

$customPeriod = SmsController::PERIOD_CUSTOM;
$this->registerJs(<<<JS
$('#sms-period').on('change', function() {
    $('.sms-custom-period').toggleClass('d-none', $(this).val() !== '$customPeriod');
});
JS
);
?>
<h1><?= Html::encode($this->title); ?></h1>

<div class="card p-2 mb-3">
    <?= Html::beginForm(['sms/index'], 'get', ['class' => 'form-inline']); ?>
        <select name="period" id="sms-period" class="form-control mr-2 mb-2">
            <?php foreach ($monthList as $value => $label): ?>
                <option value="<?= $value; ?>" <?= $value === $period ? 'selected' : ''; ?>><?= $label; ?></option>
            <?php endforeach; ?>
            <option value="<?= $customPeriod; ?>" <?= $isCustom ? 'selected' : ''; ?>>Произвольный период</option>
        </select>
        <div class="sms-custom-period form-inline mr-2 mb-2 <?= $isCustom ? '' : 'd-none'; ?>">
            <span class="mr-1">с</span>
            <input type="datetime-local" name="date_from" step="1" class="form-control mr-2"
                   value="<?= $isCustom && $dateFrom ? $dateFrom->format($inputFormat) : ''; ?>">
            <span class="mr-1">по</span>
            <input type="datetime-local" name="date_to" step="1" class="form-control"
                   value="<?= $isCustom && $dateTo ? $dateTo->format($inputFormat) : ''; ?>">
        </div>
        <input type="text" name="phone" class="form-control mr-2 mb-2" placeholder="Телефон" value="<?= Html::encode($phone); ?>">
        <button type="submit" class="btn btn-primary mb-2">Показать</button>
    <?= Html::endForm(); ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= Html::encode($error); ?></div>
<?php endif; ?>

<table class="table table-sm table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Телефон</th>
            <th>Текст</th>
            <th>Статус</th>
            <th>Ошибка</th>
            <th>Создано</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($messages)): ?>
            <tr><td colspan="6" class="text-center text-muted">Нет сообщений</td></tr>
        <?php endif; ?>
        <?php foreach ($messages as $message): ?>
            <tr>
                <td><?= $message['id']; ?></td>
                <td class="text-nowrap"><?= Html::encode($message['phone']); ?></td>
                <td>
                    <?php if (null !== $message['content']): ?>
                        <?= nl2br(Html::encode($message['content'])); ?>
                    <?php elseif (!$isRoot): ?>
                        <span class="text-muted">скрыто</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?= $statusClasses[$message['status']] ?? 'light'; ?>">
                        <?= SmsServiceComponent::STATUS_LABELS[$message['status']] ?? $message['status']; ?>
                    </span>
                </td>
                <td><?= Html::encode($message['error'] ?? ''); ?></td>
                <td class="text-nowrap"><?= $message['created_at'] ? $message['created_at']->format('d.m.Y H:i:s') : ''; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($page > 1 || $hasNextPage): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page > 1 ? '' : 'disabled'; ?>">
                <a class="page-link" href="<?= $page > 1 ? $pageUrl($page - 1) : '#'; ?>">&laquo;</a>
            </li>
            <li class="page-item active"><span class="page-link"><?= $page; ?></span></li>
            <li class="page-item <?= $hasNextPage ? '' : 'disabled'; ?>">
                <a class="page-link" href="<?= $hasNextPage ? $pageUrl($page + 1) : '#'; ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
