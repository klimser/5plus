<?php

namespace console\controllers;

use backend\components\EventComponent;
use backend\models\Group;
use backend\models\GroupPupil;
use backend\models\User;
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
        $admin = User::findOne(4);
        Yii::$app->user->login($admin);
        $transaction = null;
        try {
            /** @var Group[] $activeGroups */
            $activeGroups = Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->with(['groupPupils', 'events.members.payments'])->all();
            $nowDate = new \DateTime();
            foreach ($activeGroups as $group) {
                $transaction = Yii::$app->db->beginTransaction();

                $isActive = false;
                foreach ($group->activeGroupPupils as $groupPupil) {
                    if (($groupPupil->date_end && $groupPupil->endDateObject < $nowDate)
                        || ($group->date_end && $group->endDateObject < $nowDate)) {
                        $groupPupil->active = GroupPupil::STATUS_INACTIVE;
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
            Yii::$app->errorLogger->logError('console/money-charge', $ex->getMessage(), true);
            if ($transaction !== null) $transaction->rollBack();
            return yii\console\ExitCode::UNSPECIFIED_ERROR;
        }

        return yii\console\ExitCode::OK;
    }
}