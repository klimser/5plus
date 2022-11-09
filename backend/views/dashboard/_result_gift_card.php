<?php
use common\components\helpers\MoneyHelper;
use common\models\User;
use yii\bootstrap4\Html;

/** @var $giftCard \common\models\GiftCard */
/** @var $existingStudent User|null */
?>
<div class="card">
    <div class="card-header">
        Предоплаченная карта
    </div>
    <div class="card-body">
        <p>Предмет <b><?= $giftCard->name; ?></b></p>
        <p>Сумма <b><?= MoneyHelper::formatThousands($giftCard->amount); ?></b></p>
        <?php if ($giftCard->isNew()): ?>
            <span class="label label-danger">не оплачена</span>
        <?php endif; ?>

        <?php if ($giftCard->isUsed()): ?>
            <span class="label label-success">использована</span> <?= $giftCard->usedDate->format('d.m.Y'); ?>
        <?php endif; ?>

        <?php if ($giftCard->isPaid()): ?>
            <p>куплена <b><?= $giftCard->paidDate->format('d.m.Y'); ?></b></p>
            <form id="gift-card-form" onsubmit="Dashboard.completeGiftCard(this); return false;">
                <input type="hidden" name="gift_card_id" value="<?= $giftCard->id; ?>" required>
                <input type="hidden" name="student[id]" value="<?= $existingStudent ? $existingStudent->id : ''; ?>">
                <input type="hidden" name="course[existing]" id="existing_course_id">
                <div class="row">
                    <div class="col-12 col-sm-6">
                        <?php $nameDiffer = ($existingStudent && $existingStudent->name !== $giftCard->customer_name); ?>
                        <div class="form-group <?= $nameDiffer ? ' has-error ' : ''; ?>">
                            <label for="student_name">ФИО студента</label>
                            <input id="student_name" class="form-control" name="student[name]" required
                                <?php if ($existingStudent): ?> disabled value="<?= $existingStudent->name; ?>"
                                <?php else: ?> value="<?= $giftCard->customer_name; ?>" <?php endif; ?> >
                            <?php if ($nameDiffer): ?>
                                <span class="help-block">Имя покупателя <b><?= $giftCard->customer_name; ?></b> отличается от имени студента с этим номером телефона! Проверьте!</span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="student_phone">Телефон студента</label>
                            <div class="input-group">
                                <span class="input-group-prepend">
                                    <span class="input-group-text">+998</span>
                                </span>
                                <input id="student_phone" class="form-control phone-formatted"
                                       name="student[phoneFormatted]" required maxlength="11" pattern="\d{2} \d{3}-\d{4}"
                                    <?php if ($existingStudent): ?> disabled value="<?= $existingStudent->phoneFormatted; ?>"
                                    <?php else: ?> value="<?= $giftCard->phoneFormatted; ?>" <?php endif; ?> >
                            </div>
                        </div>
                        <?= Html::radioList(
                            'person_type',
                            User::ROLE_PARENTS,
                            [
                                User::ROLE_PARENTS => 'Физ. лицо',
                                User::ROLE_COMPANY => 'Юр. лицо',
                            ]
                        ); ?>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="parents_name">ФИО родителей / Название компании</label>
                            <input id="parents_name" class="form-control" name="parents[name]"
                                <?php if ($existingStudent && $existingStudent->parent_id): ?> disabled value="<?= $existingStudent->parent->name; ?>"
                                <?php else: ?> value="<?= $giftCard->additionalData['parents_name'] ?? ''; ?>" <?php endif; ?>>
                        </div>
                        <div class="form-group">
                            <label for="parents_phone">Телефон родителей (компании)</label>
                            <div class="input-group">
                                <span class="input-group-prepend">
                                    <span class="input-group-text">+998</span>
                                </span>
                                <input id="parents_phone" class="form-control phone-formatted" name="parents[phoneFormatted]"
                                       maxlength="11" pattern="\d{2} \d{3}-\d{4}"
                                    <?php if ($existingStudent && $existingStudent->parent_id): ?> disabled value="<?= $existingStudent->parent->phoneFormatted; ?>"
                                    <?php else: ?> value="<?= $giftCard->additionalData['parents_phone'] ?? ''; ?>" <?php endif; ?>>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <?php if ($existingStudent): ?>
                    <h4>Оплатить занятия в группе</h4>
                    <?php foreach ($existingStudent->activeCourseStudents as $courseStudent): ?>
                        <button class="btn btn-outline-secondary btn-lg mr-2 gift-card-existing-course" type="button"
                                data-course="<?= $courseStudent->course_id; ?>" onclick="Dashboard.setGiftCourse(this);">
                            <?= $courseStudent->course->courseConfig->name; ?> с <?= $courseStudent->startDateObject->format('d.m.Y'); ?>
                        </button>
                    <?php endforeach; ?>
                    <br><br>
                <?php endif; ?>
                <h4>Добавить в новую группу</h4>
                <div class="row">
                    <div class="col-12 col-sm-6 form-group">
                        <div class="input-group">
                            <select id="new-course" name="course[id]" class="form-control" onchange="Dashboard.selectGiftCourse(this);"></select>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 form-group">
                        <div class="input-group">
                            <span class="input-group-prepend">
                                <span class="input-group-text">с</span>
                            </span>
                            <input class="form-control datepicker" name="course[date]" id="new-course-date" value="<?= date('d.m.Y'); ?>" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="form-group text-right">
                    <button id="gift-button" class="btn btn-primary">внести</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
