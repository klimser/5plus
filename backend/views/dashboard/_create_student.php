<?php
/* @var $this \yii\web\View */

use common\models\User; ?>
<div id="modal-create-student" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="create-student-form" class="multistep_form user-view" method="post" onsubmit="Dashboard.createStudent(this); return false;" novalidate>
                <div class="modal-header">
                    <h4 class="modal-title">Добавить студента</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body ui-front">
                    <div id="create_student_messages_place"></div>
                    <nav>
                        <div class="nav step-line row justify-content-between" role="tablist">
                            <?php $tab_item = function($stepNum, $title, $targetId, $active) {
                                return '<a class="nav-item nav-link step-tab ' . ($active ? ' active ' : '' ) . '" id="' . $targetId . '-tab" href="#' . $targetId . '" role="tab" '
                                    . 'aria-controls="' . $targetId . '" aria-selected="' . ($active ? 'true' : 'false') . '" data-step-order="' . $stepNum . '" onclick="return MultiStepForm.jump(this);">'
                                    . '<div class="step-label">' . $stepNum . '</div><div class="d-none d-md-block step-details">'
                                    . '<div class="title">' . $title . '</div></div></a>';
                                };
                            ?>

                            <?= $tab_item(1, 'Студент', 'step-student', true); ?>
                            <?= $tab_item(2, 'Родители', 'step-parent', false); ?>
                            <?= $tab_item(3, 'Консультация', 'step-consultation', false); ?>
                            <?= $tab_item(4, 'Пробный урок', 'step-welcome', false); ?>
                            <?= $tab_item(5, 'Группа', 'step-course', false); ?>
                        </div>
                    </nav>
                    <div class="tab-content" id="nav-tabContent">
                        <?php $tab_content = function($stepNum, $targetId, $active, $lastStep, $template, $params) {
                            return '<div class="tab-pane fade pt-3 ' . ($active ? ' show active ' : '' ) . '" id="' . $targetId . '" role="tabpanel" aria-labelledby="' . $targetId . '-tab">'
                                . $this->render($template, $params)
                                . '<div class="my-3">'
                                . ($stepNum !== 1  ? '<button type="button" class="btn btn-secondary mr-2" onclick="MultiStepForm.jumpTo(' . ($stepNum - 1) . ');">назад</button>' : '')
                                . ($lastStep
                                    ? '<button type="submit" class="btn btn-primary">сохранить <span class="button-loading-spinner d-none"></span></button>'
                                    : '<button type="button" class="btn btn-primary" onclick="MultiStepForm.jumpTo(' . ($stepNum + 1) . ');">далее</button>'
                                )
                                . '</div>'
                                . '</div>';
                        };
                        ?>

                        <?= $tab_content(1, 'step-student', true, false, "/user/_student", []); ?>
                        <?= $tab_content(2, 'step-parent', false, false, "/user/_parent", []); ?>
                        <?= $tab_content(3, 'step-consultation', false, false, "/user/_view_consultation", ['student' => (new User())]); ?>
                        <?= $tab_content(4, 'step-welcome', false, false, "/user/_view_welcome_lesson", ['student' => (new User())]); ?>
                        <?= $tab_content(5, 'step-course', false, true, "/user/_view_course", ['student' => (new User())]); ?>
                        
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">закрыть</button>
                </div>
            </form>
        </div>
    </div>
</div>
