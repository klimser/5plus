<?php
use common\models\GroupPupil;
?>
<div id="modal-end-pupil" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="end-pupil-form" onsubmit="Dashboard.endPupil(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Завершение занятий в группе</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="end-pupil-messages-place"></div>
                    <input type="hidden" name="end-pupil[id]" id="end-pupil-id">
                    <div class="form-group row">
                        <label class="col-12 col-sm-4 col-form-label" for="end-pupil-pupil-name">Студент</label>
                        <div class="col-12 col-sm-8">
                            <input readonly class="form-control-plaintext" id="end-pupil-pupil-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-4 col-form-label" for="end-pupil-group">Группа</label>
                        <div class="col-12 col-sm-8">
                            <input readonly class="form-control-plaintext" id="end-pupil-group">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-4 col-form-label" for="end-pupil-date">День последнего занятия</label>
                        <div class="col-12 col-sm-8">
                            <input class="form-control datepicker" name="end-pupil[date]" id="end-pupil-date" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-4 col-form-label">Причина</label>
                        <div class="col-12 col-sm-8">
                            <?php foreach (GroupPupil::END_REASON_LABELS as $id => $label): ?>
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input class="form-check-input" type="radio" name="end-pupil[reasonId]" value="<?= $id; ?>" required autocomplete="off">
                                        <?= $label; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <textarea name="end-pupil[reasonComment]" class="form-control" rows="3" placeholder="другая причина и прочая информация"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary" id="end-pupil-button">завершить</button>
                </div>
            </form>
        </div>
    </div>
</div>
