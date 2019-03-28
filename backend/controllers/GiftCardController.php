<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use common\models\GiftCard;
use common\models\GiftCardSearch;
use common\models\User;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * GiftCardController implements the CRUD actions for GiftCard model.
 */
class GiftCardController extends AdminController
{
    use Active;

    protected $accessRule = 'moneyManagement';

    /**
     * Lists all GiftCard models.
     * @param string $status
     * @return mixed
     */
    public function actionIndex($status = null)
    {
        $searchModel = new GiftCardSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
        if ($status !== null) {
            $status = intval($status);
            $dataProvider->query->andFilterWhere([ 'status' => $status ]);
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'status' => $status,
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionFind()
    {
        if (!\Yii::$app->request->isAjax) throw new BadRequestHttpException('Request is not AJAX');

        $jsonData = self::getJsonOkResult();
        $giftCardCode = \Yii::$app->request->post('code', '');

        if (empty($giftCardCode)) $jsonData = self::getJsonErrorResult('Неверный номер карты');
        else {
            $giftCard = GiftCard::findOne(['code' => $giftCardCode]);
            if (!$giftCard) $jsonData = self::getJsonErrorResult('Карта не найдена');
            elseif ($giftCard->status == GiftCard::STATUS_NEW) $jsonData = self::getJsonErrorResult('Карта не оплачена!');
            elseif ($giftCard->status == GiftCard::STATUS_USED) $jsonData = self::getJsonErrorResult('Карта уже использована!');
            else {
                $jsonData['id'] = $giftCard->id;
                $jsonData['pupil_name'] = $giftCard->customer_name;
                $jsonData['pupil_phone'] = $giftCard->phoneFormatted;
                $jsonData['parents_name'] = array_key_exists('parents_name', $giftCard->additionalData) ? $giftCard->additionalData['parents_name'] : '';
                $jsonData['parents_phone'] = array_key_exists('parents_phone', $giftCard->additionalData) ? $giftCard->additionalData['parents_phone'] : '';
                $jsonData['type'] = $giftCard->name;
                $jsonData['amount'] = $giftCard->amount;
                /** @var User $pupil */
                $pupil = User::find()
                    ->andWhere(['role' => [User::ROLE_PUPIL]])
                    ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                    ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $giftCard->customer_phone])
                    ->with(['activeGroupPupils.group'])
                    ->one();
                if ($pupil && count($pupil->activeGroupPupils) > 0) {
                    $existingPupil = [
                        'id' => $pupil->id,
                        'name' => $pupil->name,
                        'phone' => $pupil->phoneFormatted,
                        'parents_name' => $pupil->parent_id ? $pupil->parent->name : '',
                        'parents_phone' => $pupil->parent_id ? $pupil->parent->phoneFormatted : '',
                        'groupPupils' => [],
                    ];
                    foreach ($pupil->activeGroupPupils as $groupPupil) {
                        $existingPupil['group_pupils'][] = [
                            'id' => $groupPupil->id,
                            'group' => $groupPupil->group->name,
                            'from' => $groupPupil->startDateObject->format('d.m.Y'),
                        ];
                    }
                    $jsonData['existing_pupil'] = $existingPupil;
                }
            }
        }

        return $this->asJson($jsonData);
    }

    /**
     * Finds the GiftCard model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return GiftCard the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GiftCard::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested gift card does not exist.');
        }
    }
}
