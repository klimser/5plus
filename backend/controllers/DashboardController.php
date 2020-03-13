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
    const SEARCH_TYPE_STRICT = 'strict';
    const SEARCH_TYPE_FLEX = 'flex';
    protected $accessRule = 'manager';

    public function actionIndex()
    {
        return $this->render('index', [
            'pupilLimitDate' => GroupComponent::getPupilLimitDate(),
        ]);
    }

     /**
     * Search system
     * @return mixed
     */
    public function actionFind()
    {
        $searchType = Yii::$app->request->get('type', self::SEARCH_TYPE_STRICT);
        $searchValue = Yii::$app->request->get('value');

        $contract = $giftCard = $existingPupil = null;
        $pupils = $parents = [];
        switch ($searchType) {
            case self::SEARCH_TYPE_STRICT:
                $contract = Contract::findOne(['number' => $searchValue]);
                $giftCard = GiftCard::findOne(['code' => $searchValue]);
                if ($giftCard) {
                    /** @var User $existingPupil */
                    $existingPupil = User::find()
                        ->andWhere(['role' => [User::ROLE_PUPIL]])
                        ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                        ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $giftCard->customer_phone])
                        ->with(['activeGroupPupils.group'])
                        ->one();
                }
                break;
            case self::SEARCH_TYPE_FLEX:
                $digitsOnly = preg_replace('#\D#', '', $searchValue);
                $activeStatuses = [User::STATUS_ACTIVE, User::STATUS_INACTIVE];
                $query = User::find()
                    ->andWhere([
                        'status' => $activeStatuses
                    ])
                    ->addOrderBy(['role' => SORT_DESC, 'name' => SORT_ASC]);
                $searchCondition = [['like', 'name', $searchValue]];
                if (strlen($digitsOnly) >= 7) {
                    $searchCondition[] = ['like', 'phone', "%$digitsOnly", false];
                    $searchCondition[] = ['like', 'phone2', "%$digitsOnly", false];
                }
                $query->andWhere(array_merge(['or'], $searchCondition));

                $pupilIdSet = [];
                $parentQuery = clone $query;
                /** @var User[] $parents */
                $parents = $parentQuery->andWhere(['role' => [User::ROLE_PARENTS, User::ROLE_COMPANY]])
                    ->with('notLockedChildren')
                    ->all();
                foreach ($parents as $parent) {
                    foreach ($parent->notLockedChildren as $child) {
                        $pupilIdSet[$child->id] = true;
                    }
                }
                
                /** @var User[] $users */
                $users = $query->andWhere(['role' => User::ROLE_PUPIL])->all();
                foreach ($users as $user) {
                    if (!array_key_exists($user->id, $pupilIdSet)) {
                        $pupils[] = $user;
                        $pupilIdSet[$user->id] = true;
                    }
                }
                break;
        }
        
        return $this->renderPartial('results', [
            'contract' => $contract,
            'giftCard' => $giftCard,
            'existingPupil' => $existingPupil,
            'parents' => $parents,
            'pupils' => $pupils,
        ]);
    }
}
