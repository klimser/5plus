<?php

namespace console\controllers;

use backend\components\EventComponent;
use common\components\ComponentContainer;
use common\components\GroupComponent;
use common\components\MoneyComponent;
use common\models\Group;
use common\models\User;
use yii;
use yii\console\Controller;

/**
 * FuckupController is used to solve fuckups.
 */
class FuckupController extends Controller
{
    public function actionResolve()
    {
        $filename = \Yii::getAlias('@runtime/fuckup') . '/data.txt';

        $admin = User::findOne(User::SYSTEM_USER_ID);
        Yii::$app->user->login($admin);
        
        $lastId = file_exists($filename) ? file_get_contents($filename) : 0;
        
        /** @var Group $group */
        $group = Group::find()
            ->andWhere(['>', 'id', $lastId])
            ->orderBy(['id' => SORT_ASC])
            ->one();
        
        if (!$group) return yii\console\ExitCode::OK;
        
        file_put_contents($filename, $group->id);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            EventComponent::fillSchedule($group);
            foreach ($group->groupPupils as $groupPupil) {
                MoneyComponent::rechargePupil($groupPupil->user, $groupPupil->group);
                MoneyComponent::setUserChargeDates($groupPupil->user, $groupPupil->group);
                MoneyComponent::recalculateDebt($groupPupil->user, $groupPupil->group);
                MoneyComponent::rechargePupil($groupPupil->user, $groupPupil->group);
                MoneyComponent::setUserChargeDates($groupPupil->user, $groupPupil->group);
            }
            GroupComponent::calculateTeacherSalary($group);

            $transaction->commit();
        } catch (\Throwable $ex) {
            echo $ex->getMessage();
            ComponentContainer::getErrorLogger()
                ->logError('console/fuckup-resolve', $ex->getMessage(), true);
            if ($transaction !== null) $transaction->rollBack();
            return yii\console\ExitCode::UNSPECIFIED_ERROR;
        }
        
        return yii\console\ExitCode::OK;
    }
}
