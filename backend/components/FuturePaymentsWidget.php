<?php
namespace backend\components;

use common\models\User;
use common\components\helpers\Calendar;
use common\components\helpers\MoneyHelper;
use yii\base\Widget;

class FuturePaymentsWidget extends Widget
{
    /** @var  User */
    public $user;

    public function run()
    {
        $htmlData = '';
        if ($this->user instanceof User) {
            if ($this->user->role == User::ROLE_PUPIL) {
                $currentPayments = $this->user->thisMonthPayments;
                if ($currentPayments['total'] > 0) {
                    $htmlData = '<div class="alert alert-warning"><b>Ожидаемые списания:</b><br>';
                    foreach ($currentPayments['payments'] as $payment) {
                        $htmlData .= '<div class="pull-left">' . $payment['group']->name . ' за ' . Calendar::$monthNames[$payment['date']->format('n')] . '</div><div class="pull-right">' . MoneyHelper::roundThousand($payment['amount']) . '</div><div class="clearfix"></div>';
                    }
                    $htmlData .= '<b>Вам необходимо оплатить ещё <i>' . MoneyHelper::roundThousand($currentPayments['total']) . '</i></b></div>';
                } elseif (date('j') >= 25) {
                    $nextPayments = $this->user->nextMonthPayments;

                    if (!empty($nextPayments['payments'])) {
                        $htmlData = '<div class="alert alert-info"><b>Ожидаемые списания:</b><br>';
                        foreach ($nextPayments['payments'] as $payment) {
                            $htmlData .= '<div class="pull-left">' . $payment['group']->name . ' за ' . Calendar::$monthNames[$payment['date']->format('n')] . '</div><div class="pull-right">' . MoneyHelper::roundThousand($payment['amount']) . '</div><div class="clearfix"></div>';
                        }
                        if ($nextPayments['total'] > 0) {
                            $htmlData .= '<b>Вам необходимо оплатить ещё <i>' . MoneyHelper::roundThousand($nextPayments['total']) . '</i></b>';
                        }
                        $htmlData .= '</div>';
                    }
                }
            } elseif ($this->user->role == User::ROLE_PARENTS) {
                $totalDebt = 0;
                $hasCurrentDebt = false;
                foreach ($this->user->children as $child) {
                    $currentPayments = $child->thisMonthPayments;
                    if ($currentPayments['total'] > 0) {
                        if (!$hasCurrentDebt) $htmlData = '<div class="alert alert-warning"><b>Ожидаемые списания:</b><br>';
                        $hasCurrentDebt = true;
                        foreach ($currentPayments['payments'] as $payment) {
                            $htmlData .= '<div class="pull-left">' . $child->name . ' в ' . $payment['group']->name . ' за ' . Calendar::$monthNames[$payment['date']->format('n')] . '</div><div class="pull-right">' . MoneyHelper::roundThousand($payment['amount']) . '</div><div class="clearfix"></div>';
                        }
                        $totalDebt += $currentPayments['total'];
                    }
                }
                if ($hasCurrentDebt) {
                    $htmlData .= '<b>Вам необходимо оплатить ещё <i>' . MoneyHelper::roundThousand($totalDebt) . '</i></b></div>';
                } elseif (date('j') >= 25) {
                    $hasNextPayments = false;
                    foreach ($this->user->children as $child) {
                        $nextPayments = $child->nextMonthPayments;
                        if (!empty($nextPayments['payments'])) {
                            if (!$hasNextPayments) $htmlData = '<div class="alert alert-warning"><b>Ожидаемые списания:</b><br>';
                            $hasNextPayments = true;
                            foreach ($nextPayments['payments'] as $payment) {
                                $htmlData .= '<div class="pull-left">' . $child->name . ' в ' . $payment['group']->name . ' за ' . Calendar::$monthNames[$payment['date']->format('n')] . '</div><div class="pull-right">' . MoneyHelper::roundThousand($payment['amount']) . '</div><div class="clearfix"></div>';
                            }
                            if ($nextPayments['total'] > 0) {
                                $totalDebt += $nextPayments['total'];
                            }
                        }
                    }
                    if ($totalDebt) {
                        $htmlData .= '<b>Вам необходимо оплатить ещё <i>' . MoneyHelper::roundThousand($totalDebt) . '</i></b>';
                    }
                    $htmlData .= '</div>';
                }
            }
        }
        return $htmlData;
    }
}
