<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use common\models\GiftCard;
use common\models\GiftCardSearch;
use common\models\User;
use Yii;
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
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
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
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionFind()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $giftCardCode = Yii::$app->request->post('code', '');

        if (empty($giftCardCode)) {
            return self::getJsonErrorResult('Неверный номер карты');
        }

        $giftCard = GiftCard::findOne(['code' => $giftCardCode]);
        if (!$giftCard) return self::getJsonErrorResult('Карта не найдена');
        if ($giftCard->status == GiftCard::STATUS_NEW) return self::getJsonErrorResult('Карта не оплачена!');
        if ($giftCard->status == GiftCard::STATUS_USED) return self::getJsonErrorResult('Карта уже использована!');

        $jsonData['id'] = $giftCard->id;
        $jsonData['pupil_name'] = $giftCard->customer_name;
        $jsonData['pupil_phone'] = $giftCard->phoneFormatted;
        $jsonData['parents_name'] = array_key_exists('parents_name', $giftCard->additionalData) ? $giftCard->additionalData['parents_name'] : '';
        $jsonData['parents_phone'] = array_key_exists('parents_phone', $giftCard->additionalData) ? $giftCard->additionalData['parents_phone'] : '';
        $jsonData['type'] = $giftCard->name;
        $jsonData['amount'] = $giftCard->amount;
        /** @var User $student */
        $student = User::find()
            ->andWhere(['role' => [User::ROLE_STUDENT]])
            ->andWhere(['!=', 'status', User::STATUS_LOCKED])
            ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $giftCard->customer_phone])
            ->with(['activeCourseStudents.course'])
            ->one();
        if ($student && count($student->activeCourseStudents) > 0) {
            $existingStudent = [
                'id' => $student->id,
                'name' => $student->name,
                'phone' => $student->phoneFormatted,
                'parents_name' => $student->parent_id ? $student->parent->name : '',
                'parents_phone' => $student->parent_id ? $student->parent->phoneFormatted : '',
                'course_students' => [],
            ];
            foreach ($student->activeCourseStudents as $courseStudent) {
                $existingStudent['course_students'][] = [
                    'id' => $courseStudent->id,
                    'group_id' => $courseStudent->course_id,
                    'group' => $courseStudent->course->courseConfig->name,
                    'from' => $courseStudent->startDateObject->format('d.m.Y'),
                ];
            }
            $jsonData['existing_student'] = $existingStudent;
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
