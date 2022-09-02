<?php
/* @var $this yii\web\View */

use common\components\helpers\MoneyHelper;
use common\components\helpers\WordForm;
use common\models\CourseStudent;

/* @var $student \common\models\User */
/* @var $contractAllowed bool */
/* @var $incomeAllowed bool */
/* @var $debtAllowed bool */
/* @var $courseManagementAllowed bool */
/* @var $moveMoneyAllowed bool */
$activeCourseIdSet = [];
foreach ($student->courseStudentsAggregated as $courseId => $courseStudents) {
    foreach ($courseStudents as $courseStudent) {
        if ($courseStudent->active === CourseStudent::STATUS_ACTIVE) {
            $activeCourseIdSet[$courseId] = true;
        }
    }
}
?>
<div class="courses">
    <?php if ($student->id): ?>
        <div class="text-right form-check mb-2">
            <input class="form-check-input filter-type" type="checkbox" value="1" onchange="Dashboard.filterCourses(this);" id="filter-type-<?= $student->id; ?>">
            <label class="form-check-label" for="filter-type-<?= $student->id; ?>">
                показать завершенные
            </label>
        </div>
    <?php endif; ?>
    <div class="courses-table">
        <?php
        /** @var CourseStudent[] $courseStudents */
        foreach ($student->courseStudentsAggregated as $courseId => $courseStudents):
            $courseNameRendered = false;
            $isActive = false;
            foreach ($courseStudents as $courseStudent):
                if ($courseStudent->active === CourseStudent::STATUS_ACTIVE) {
                    $isActive = true;
                }
            ?>
                <div class="row justify-content-between align-items-start border-bottom pb-3 mb-3 collapse course-item <?= $courseStudent->active === CourseStudent::STATUS_INACTIVE ? ' inactive table-secondary ' : ' show '; ?>">
                    <div class="col-8 col-md-9">
                        <div class="row">
                            <div class="col-12 col-md-4 col-lg-6">
                                <?php if (!$courseNameRendered): ?>
                                    <?= $courseStudent->course->courseConfig->name; ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 col-md-4 col-lg-3">
                                с <?= $courseStudent->startDateObject->format('d.m.Y') ;?>
                                <?php if ($courseStudent->date_end): ?>
                                    <br> до <?= $courseStudent->endDateObject->format('d.m.Y'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 col-md-4 col-lg-3">
                                <?php if (!$courseNameRendered):
                                    $moneyLeft = $courseStudent->moneyLeft; ?>
                                    <?= $moneyLeft < 0 ? 'долг ' : ''; ?>
                                    <span class="badge badge-<?= $moneyLeft < 0 ? 'danger' : 'success'; ?>"><?= MoneyHelper::formatThousands(abs($moneyLeft)); ?></span><br>
                                    
                                    <?php if ($courseStudent->paid_lessons >= 0): ?>
                                        <b><?= $courseStudent->paid_lessons; ?></b> <?= WordForm::getLessonsForm($courseStudent->paid_lessons); ?><br>
                                        до <i><?= $courseStudent->chargeDateObject->format('d.m.Y'); ?></i>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-md-3 text-right">
                        <?php if ($courseStudent->active === CourseStudent::STATUS_ACTIVE): ?>
                            <?php if ($incomeAllowed): ?>
                                <?php if ($student->isAgeConfirmed()): ?>
                                    <button type="button" title="принять оплату" class="btn btn-primary mb-2" onclick="Dashboard.showMoneyIncomeForm(this);"
                                            data-course="<?= $courseStudent->course_id; ?>" data-user="<?= $student->id; ?>">
                                        <span class="fas fa-dollar-sign"></span>
                                    </button>
                                <?php else: ?>
                                    <button type="button" title="отправить СМС для подтверждения возраста" class="btn btn-primary mb-2"
                                            onclick="Dashboard.showAgeConfirmationForm(this);" data-user="<?= $student->id; ?>"
                                            data-phone1="<?= $student->phone; ?>" data-phone2="<?= $student->phone2; ?>"
                                            data-phone3="<?= $student->parent_id ? $student->parent->phone : ''; ?>"
                                            data-phone4="<?= $student->parent_id ? $student->parent->phone2 : ''; ?>">
                                        <span class="fas fa-baby"></span>
                                    </button>
                                    <?php if ($debtAllowed): ?>
                                        <button type="button" title="принять оплату" class="btn btn-primary mb-2" onclick="if (confirm('Are you sure?')) Dashboard.showMoneyIncomeForm(this);"
                                                data-course="<?= $courseStudent->course_id; ?>" data-user="<?= $student->id; ?>">
                                            <span class="fas fa-dollar-sign"></span>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        
                            <?php if ($debtAllowed): ?>
                                <button type="button" title="добавить долг" class="btn btn-outline-danger mb-2" onclick="Dashboard.showMoneyDebtForm(this);"
                                        data-course="<?= $courseStudent->course_id; ?>" data-user="<?= $student->id; ?>">
                                    <span class="fas fa-cash-register"></span>
                                </button>
                            <?php endif; ?>
        
                            <?php if ($incomeAllowed): ?>
                                <button type="button" title="выдать договор" class="btn btn-outline-dark mb-2" onclick="Dashboard.showNewContractForm(this);"
                                        data-course="<?= $courseStudent->course_id; ?>" data-user="<?= $student->id; ?>">
                                    <span class="fas fa-file-contract"></span>
                                </button>
                            <?php endif; ?>
        
                            <?php if ($courseManagementAllowed): ?>
                                <button type="button" title="перевести в другую группу" class="btn btn-outline-dark mb-2" onclick="Dashboard.showMoveStudentForm(this);"
                                        data-id="<?= $courseStudent->id; ?>" data-course="<?= $courseStudent->course_id; ?>"
                                        data-date="<?= $courseStudent->startDateObject->format('d.m.Y'); ?>">
                                    <span class="fas fa-running"></span> <span class="fas fa-arrow-right"></span>
                                </button>
                                <button type="button" title="завершает ходить" class="btn btn-outline-dark mb-2" onclick="Dashboard.showEndStudentForm(this);"
                                        data-id="<?= $courseStudent->id; ?>" data-course="<?= $courseStudent->course_id; ?>"
                                        data-date="<?= $courseStudent->startDateObject->format('Y-m-d'); ?>">
                                    <span class="fas fa-skull-crossbones"></span>
                                </button>
                            <?php endif; ?>
                        <?php elseif (!$isActive && $moveMoneyAllowed && $courseStudent->moneyLeft > 0): ?>
                            <button type="button" title="перенести оставшиеся деньги" class="btn btn-outline-dark mb-2" onclick="Dashboard.showMoveMoneyForm(this);"
                                    data-id="<?= $courseStudent->id; ?>" data-course="<?= $courseStudent->course_id; ?>" data-amount="<?= MoneyHelper::formatThousands($courseStudent->moneyLeft); ?>"
                                    data-courses="<?= implode(',', array_keys($activeCourseIdSet)); ?>">
                                <span class="fas fa-dollar-sign"></span> <span class="fas fa-arrow-right"></span>
                            </button>
                        <?php endif; ?>

                        <?php if (!$isActive && $debtAllowed && $courseStudent->moneyLeft > 0): ?>
                            <button type="button" title="возврат" class="btn btn-outline-danger mb-2" onclick="Dashboard.showMoneyDebtForm(this, true);"
                                    data-course="<?= $courseStudent->course_id; ?>" data-user="<?= $student->id; ?>" data-amount="<?= MoneyHelper::formatThousands($courseStudent->moneyLeft); ?>">
                                <span class="fas fa-search-dollar"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
                if ($courseStudent->active === CourseStudent::STATUS_ACTIVE) {
                    $courseNameRendered = true;
                }
            endforeach;
        endforeach; ?>
    </div>
</div>
<button type="button" class="btn btn-success" onclick="User.addCourse(undefined, $(this).closest('.user-view'));"><span class="fas fa-plus"></span> добавить</button>
