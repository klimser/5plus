<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use common\models\GiftCard;
use common\models\GiftCardSearch;
use Yii;
use yii\web\NotFoundHttpException;

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
