<?php

use dosamigos\datepicker\DatePickerLanguageAsset;

DatePickerLanguageAsset::register($this)->js[] = 'bootstrap-datepicker.ru.min.js';

/* @var $this yii\web\View */
/* @var $admin \yii\web\User */
/* @var $orderCount int */
/* @var $feedbackCount int */
/* @var $reviewCount int */

$this->title = 'Панель управления';
?>
<div id="messages_place"></div>
<div class="row" id="step1">
    <div class="col-xs-6">
        <a class="btn btn-default btn-lg full-width" href="#" onclick="Dashboard.step2('strict'); return false;">
            <span class="fas fa-barcode fa-3x"></span><hr>
            Номер (договора, карты и т п)
        </a>
    </div>
    <div class="col-xs-6">
        <a class="btn btn-default btn-lg full-width" href="#" onclick="Dashboard.step2('flex'); return false;">
            <span class="fas fa-search fa-3x"></span><hr>
            Телефон, ФИО и т п
        </a>
    </div>
</div>
<div class="row hidden step2" id="step2_strict">
    <button class="btn btn-default" onclick="Dashboard.step1();"><span class="fas fa-arrow-left"></span> назад</button>
    <br><br>
    <form onsubmit="Dashboard.find(this); return false;">
        <div class="input-group input-group-lg">
            <input class="form-control autofocus search" data-search="strict" placeholder="Номер (договора, карты и т п)" required>
            <span class="input-group-btn">
                <button class="btn btn-success"><span class="fas fa-search"></span></button>
            </span>
        </div>
    </form>
</div>
<div class="row hidden step2" id="step2_flex">
    <button class="btn btn-default" onclick="Dashboard.step1();"><span class="fas fa-arrow-left"></span> назад</button>
    <br><br>
    <form onsubmit="Dashboard.find(this); return false;">
        <div class="input-group input-group-lg">
            <input class="form-control autofocus search" data-search="flex" placeholder="Телефон, ФИО и т п" required>
            <span class="input-group-btn">
                <button class="btn btn-success"><span class="fas fa-search"></span></button>
            </span>
        </div>
    </form>
</div>
<div class="row hidden" id="result" style="margin-top: 20px;"></div>
<div class="modal fade" tabindex="-1" role="dialog" id="modal-contract">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel">Принять оплату</h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" id="contract-form" onsubmit="Dashboard.completeContract(this); return false;">
                    <input type="hidden" name="contractId" id="contract-id">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Студент</label>
                        <div class="col-sm-10">
                            <p class="form-control-static" id="contract-pupilName"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Группа</label>
                        <div class="col-sm-10">
                            <p class="form-control-static" id="contract-groupName"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Сумма</label>
                        <div class="col-sm-10">
                            <p class="form-control-static" id="contract-amount"></p>
                        </div>
                    </div>
                    <div id="contract-pupil-exists">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Занимается с</label>
                            <div class="col-sm-10">
                                <p class="form-control-static" id="contract-pupilDateStart"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Оплачено до</label>
                            <div class="col-sm-10">
                                <p class="form-control-static" id="contract-pupilDateCharge"></p>
                            </div>
                        </div>
                    </div>
                    <div id="contract-pupil-new">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Начало занятий в группе</label>
                            <div class="col-sm-10">
                                <div class="input-group date datepicker">
                                    <input class="form-control" name="contractPupilDateStart" id="contract-createDate" value="" required pattern="\d{2}\.\d{2}\.\d{4}">
                                    <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary" id="contract_button">принять</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
