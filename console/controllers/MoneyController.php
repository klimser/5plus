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
 * MoneyController is used to charge students.
 */
class MoneyController extends Controller
{
    /**
     * Charges students that are not charged yet.
     * @return int
     */
    public function actionCharge()
    {
        $admin = User::findOne(User::SYSTEM_USER_ID);
        Yii::$app->user->login($admin);
        $transaction = null;
        try {
            /** @var Course[] $activeCourses */
            $activeCourses = Course::find()->andWhere(['active' => Course::STATUS_ACTIVE])->all();
            $nowDate = new \DateTime();
            foreach ($activeCourses as $emptyCourse) {
                /** @var Course $course */
                $course = Course::find()->andWhere(['id' => $emptyCourse->id])
                    ->with(['courseStudents', 'events.members.payments'])
                    ->one();
                $transaction = Yii::$app->db->beginTransaction();

                $isActive = false;
                foreach ($course->activeCourseStudents as $courseStudent) {
                    if (($courseStudent->date_end && $courseStudent->endDateObject < $nowDate)
                        || ($course->date_end && $course->endDateObject < $nowDate)) {
                        $courseStudent->active = CourseStudent::STATUS_INACTIVE;
                        if (!$courseStudent->date_end) $courseStudent->date_end = $course->date_end;
                        $courseStudent->save();
                    } else {
                        $isActive = true;
                    }
                }
                if (!$isActive && $course->date_end && $course->endDateObject < $nowDate) {
                    $course->active = Course::STATUS_INACTIVE;
                    $course->latestCourseConfig->date_to = $course->date_end;
                    $course->latestCourseConfig->save();
                    $course->save();
                }

                if ($course->active == Course::STATUS_ACTIVE) {
                    EventComponent::fillSchedule($course);
                }

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
