<?php

namespace console\controllers;

use backend\components\EventComponent;
use common\components\ComponentContainer;
use common\models\Group;
use common\models\GroupPupil;
use common\models\User;
use yii;
use yii\console\Controller;

/**
 * MoneyController is used to charge pupils.
 */
class MoneyController extends Controller
{
    /**
     * Charges pupils that are not charged yet.
     * @return int
     */
    public function actionCharge()
    {
        $admin = User::findOne(User::SYSTEM_USER_ID);
        Yii::$app->user->login($admin);
        $transaction = null;
        try {
            /** @var Group[] $activeGroups */
            $activeGroups = Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->all();
            $nowDate = new \DateTime();
            foreach ($activeGroups as $emptyGroup) {
                /** @var Group $group */
                $group = Group::find()->andWhere(['id' => $emptyGroup->id])
                    ->with(['groupPupils', 'events.members.payments'])
                    ->one();
                $transaction = Yii::$app->db->beginTransaction();

                $isActive = false;
                foreach ($group->activeGroupPupils as $groupPupil) {
                    if (($groupPupil->date_end && $groupPupil->endDateObject < $nowDate)
                        || ($group->date_end && $group->endDateObject < $nowDate)) {
                        $groupPupil->active = GroupPupil::STATUS_INACTIVE;
                        if (!$groupPupil->date_end) $groupPupil->date_end = $group->date_end;
                        $groupPupil->save();
                    } else {
                        $isActive = true;
                    }
                }
                if (!$isActive && $group->date_end && $group->endDateObject < $nowDate) {
                    $group->active = Group::STATUS_INACTIVE;
                    $group->save();
                }

                if ($group->active == Group::STATUS_ACTIVE) EventComponent::fillSchedule($group);
                $transaction->commit();
            }
        } catch (\Throwable $ex) {
            echo $ex->getMessage();
            ComponentContainer::getErrorLogger()
                ->logError('console/money-charge', $ex->getMessage(), true);
            if ($transaction !== null) $transaction->rollBack();
            return yii\console\ExitCode::UNSPECIFIED_ERROR;
        }

        return yii\console\ExitCode::OK;
    }
}