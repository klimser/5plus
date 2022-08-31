<?php
namespace backend\components;

use common\models\User;
use yii\base\Widget;

class DebtWidget extends Widget
{
    /** @var  User */
    public $user;

    public function run()
    {
        if ($this->user instanceof User) {
            $debtAmount = $balance = 0;
            if ($this->user->role != User::ROLE_PARENTS && $this->user->role != User::ROLE_STUDENT) return '';
            
            if ($this->user->role == User::ROLE_PARENTS) {
                foreach ($this->user->children as $child) {
                    if ($child->debts) {
                        foreach ($child->debts as $debt) $debtAmount += $debt->amount;
                    } else $balance += $this->user->money;
                }
            } else {
                if ($this->user->debts) {
                    foreach ($this->user->debts as $debt) $debtAmount += $debt->amount;
                } else $balance += $this->user->money;
            }
            
            if ($debtAmount) {
                return '<div class="float-right alert alert-danger">Задолженность: <b>' . number_format($debtAmount, 0, '.', ' ') . '</b></div>';
            } else {
                return '<div class="float-right alert alert-info">Баланс: <b>' . number_format($balance, 0, '.', ' ') . '</b></div>';
            }
        }
        return '';
    }
}
