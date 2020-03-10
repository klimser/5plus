<?php

use \yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $admin \yii\web\User */
/* @var $orderCount int */
/* @var $feedbackCount int */
/* @var $reviewCount int */

$this->title = 'Панель управления';
?>
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
