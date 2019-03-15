<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use common\models\GiftCardType;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * GiftCardTypeController implements the CRUD actions for GiftCardType model.
 */
class GiftCardTypeController extends AdminController
{
    use Active;

    protected $accessRule = 'manageGiftCardTypes';

    /**
     * Lists all GiftCardType models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => GiftCardType::find()->orderBy(['active' => SORT_DESC, 'name' => SORT_ASC]),
            'pagination' => ['pageSize' => 50,],
            'sort' => [
                'defaultOrder' => ['name' => SORT_ASC],
                'attributes' => ['name'],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new GiftCardType model.
     * If creation is successful, the browser will be redirected to the 'page' page.
     * @return mixed
     * @throws \Exception
     */
    public function actionCreate()
    {
        $giftCardType = new GiftCardType();

        if (\Yii::$app->request->isPost) {
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if (!$giftCardType->load(\Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
                else {
                    if (!$giftCardType->save()) {
                        $giftCardType->moveErrorsToFlash();
                        $transaction->rollBack();
                    } else {
                        $transaction->commit();
                        \Yii::$app->session->addFlash('success', 'Добавлено');
                        return $this->redirect(['index']);
                    }
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('create', [
            'giftCardType' => $giftCardType,
        ]);
    }

    /**
     * Finds the GiftCardType model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return GiftCardType the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GiftCardType::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested gift card type does not exist.');
        }
    }
}
