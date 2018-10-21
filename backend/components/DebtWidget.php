<?php
namespace backend\components;


use backend\models\Debt;
use backend\models\User;
use yii\base\Widget;

class DebtWidget extends Widget
{
    /** @var  User */
    public $user;

    public function run()
    {
        if ($this->user instanceof User) {
            $debtAmount = $balance = 0;
            if ($this->user->role != User::ROLE_PARENTS && $this->user->role != User::ROLE_PUPIL) return '';
            
            if ($this->user->role == User::ROLE_PARENTS) {
                foreach ($this->user->children as $child) {
                    if ($child->debt) $debtAmount += $child->debt->amount;
                    else $balance += $this->user->money;
                }
            } else {
                if ($this->user->debt) $debtAmount += $this->user->debt->amount;
                else $balance += $this->user->money;
            }
            
            if ($debtAmount) {
                return '<div class="pull-right alert alert-danger">Задолженность: <b>' . $debtAmount . '</b></div>';
            } else {
                return '<div class="pull-right alert alert-info">Баланс: <b>' . $balance . '</b></div>';
            }
        }
        return '';
    }
}