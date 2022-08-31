<?php

namespace console\controllers;

use backend\components\EventComponent;
use common\components\ComponentContainer;
use common\models\Course;
use common\models\CourseStudent;
use common\models\User;
use yii;
use yii\console\Controller;
use yii\console\ExitCode;

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
            /** @var Course[] $activeGroups */
            $activeGroups = Course::find()->andWhere(['active' => Course::STATUS_ACTIVE])->all();
            $nowDate = new \DateTime();
            foreach ($activeGroups as $emptyGroup) {
                /** @var Course $group */
                $group = Course::find()->andWhere(['id' => $emptyGroup->id])
                    ->with(['groupPupils', 'events.members.payments'])
                    ->one();
                $transaction = Yii::$app->db->beginTransaction();

                $isActive = false;
                foreach ($group->activeGroupPupils as $groupPupil) {
                    if (($groupPupil->date_end && $groupPupil->endDateObject < $nowDate)
                        || ($group->date_end && $group->endDateObject < $nowDate)) {
                        $groupPupil->active = CourseStudent::STATUS_INACTIVE;
                        if (!$groupPupil->date_end) $groupPupil->date_end = $group->date_end;
                        $groupPupil->save();
                    } else {
                        $isActive = true;
                    }
                }
                if (!$isActive && $group->date_end && $group->endDateObject < $nowDate) {
                    $group->active = Course::STATUS_INACTIVE;
                    $group->save();
                }

                if ($group->active == Course::STATUS_ACTIVE) EventComponent::fillSchedule($group);
                $transaction->commit();
            }
        } catch (\Throwable $ex) {
            echo $ex->getMessage();
            ComponentContainer::getErrorLogger()
                ->logError('console/money-charge', $ex->getMessage(), true);
            $transaction?->rollBack();
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
