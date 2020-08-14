<?php

namespace backend\controllers;

use common\components\GroupComponent;
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
            'pupilLimitDate' => GroupComponent::getPupilLimitDate(),
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
        $searchValue = Yii::$app->request->get('value');

        $contract = $giftCard = $existingPupil = null;
        $pupils = $parents = [];
        $showAddPupil = false;
        if ($searchValue) {
            $showAddPupil = !preg_match('#\d#', $searchValue);
            
            $contract = Contract::findOne(['number' => $searchValue]);
            $giftCard = GiftCard::findOne(['code' => $searchValue]);
            if ($giftCard) {
                /** @var User $existingPupil */
                $existingPupil = User::find()
                    ->andWhere(['role' => [User::ROLE_PUPIL]])
                    ->andWhere(['not', ['status' => User::STATUS_LOCKED]])
                    ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $giftCard->customer_phone])
                    ->with(['activeGroupPupils.group'])
                    ->one();
            }

            $digitsOnly = preg_replace('#\D#', '', $searchValue);
            $query = User::find()
                ->andWhere(['not', ['status' => User::STATUS_LOCKED]])
                ->addOrderBy(['role' => SORT_DESC, 'name' => SORT_ASC]);
            $searchCondition = [['like', 'name', $searchValue]];
            if (strlen($digitsOnly) >= 7) {
                $searchCondition[] = ['like', 'phone', "%$digitsOnly", false];
                $searchCondition[] = ['like', 'phone2', "%$digitsOnly", false];
            }
            $query->andWhere(array_merge(['or'], $searchCondition));

            $pupilIdSet = [];
            
            /** @var User[] $users */
            $users = $query->andWhere(['role' => User::ROLE_PUPIL])->all();
            foreach ($users as $user) {
                $pupils[] = $user;
                $pupilIdSet[$user->id] = true;
            }
            
            $parentQuery = clone $query;
            /** @var User[] $parents */
            $users = $parentQuery->andWhere(['role' => [User::ROLE_PARENTS, User::ROLE_COMPANY]])
                ->with('notLockedChildren')
                ->all();
            foreach ($users as $user) {
                $add = false;
                foreach ($user->notLockedChildren as $child) {
                    if (!array_key_exists($child->id, $pupilIdSet)) {
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
            'existingPupil' => $existingPupil,
            'parents' => $parents,
            'pupils' => $pupils,
            'showAddPupil' => $showAddPupil,
        ]);
    }
}
