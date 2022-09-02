<?php

namespace backend\controllers;

use common\components\CourseComponent;
use common\models\Contract;
use common\models\GiftCard;
use common\models\User;
use yii;

/**
 * DashboardController
 */
class DashboardController extends AdminController
{
    protected $accessRule = 'manager';

    public function actionIndex()
    {
        return $this->render('index', [
            'studentLimitDate' => CourseComponent::getStudentLimitDate(),
            'incomeAllowed' => Yii::$app->user->can('moneyManagement'),
            'contractAllowed' => Yii::$app->user->can('contractManagement'),
        ]);
    }

     /**
     * Search system
     * @return mixed
     */
    public function actionFind()
    {
        $searchValue = trim(Yii::$app->request->get('value', ''));

        $contract = $giftCard = $existingStudent = null;
        $students = $parents = [];
        $showAddStudent = false;
        if ($searchValue) {
            $showAddStudent = !preg_match('#\d#', $searchValue);
            
            $contract = Contract::findOne(['number' => $searchValue]);
            $giftCard = GiftCard::findOne(['code' => $searchValue]);
            if ($giftCard) {
                /** @var User $existingStudent */
                $existingStudent = User::find()
                    ->andWhere(['role' => [User::ROLE_STUDENT]])
                    ->andWhere(['not', ['status' => User::STATUS_LOCKED]])
                    ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $giftCard->customer_phone])
                    ->with(['activeCourseStudents.course.courseConfig'])
                    ->one();
            }

            $digitsOnly = preg_replace('#\D#', '', $searchValue);
            $query = User::find()
                ->andWhere(['not', ['status' => User::STATUS_LOCKED]]);
            $searchCondition = [['like', 'name', $searchValue]];
            if (strlen($digitsOnly) >= 7) {
                $searchCondition[] = ['like', 'phone', "%$digitsOnly", false];
                $searchCondition[] = ['like', 'phone2', "%$digitsOnly", false];
            }
            $query->andWhere(array_merge(['or'], $searchCondition));

            $studentIdSet = [];
            $studentQuery = clone $query;
            /** @var User[] $users */
            $users = $studentQuery->andWhere(['role' => User::ROLE_STUDENT])->all();
            foreach ($users as $user) {
                $students[] = $user;
                $studentIdSet[$user->id] = true;
            }
            
            $parentQuery = clone $query;
            /** @var User[] $users */
            $users = $parentQuery->andWhere(['role' => [User::ROLE_PARENTS, User::ROLE_COMPANY]])
                ->with('notLockedChildren')
                ->all();
            foreach ($users as $user) {
                $add = false;
                foreach ($user->notLockedChildren as $child) {
                    if (!array_key_exists($child->id, $studentIdSet)) {
                        $add = true;
                    }
                }
                if ($add) {
                    $parents[] = $user;
                }
            }
        }
        
        return $this->renderPartial('results', [
            'contract' => $contract,
            'giftCard' => $giftCard,
            'existingStudent' => $existingStudent,
            'parents' => $parents,
            'students' => $students,
            'showAddStudent' => $showAddStudent,
        ]);
    }
}
