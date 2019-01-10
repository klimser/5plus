<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Module;
use common\models\Promotion;
use common\models\Webpage;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;

class PromotionController extends Controller
{
    /**
     * Displays a Promotions page.
     * @param $webpage Webpage
     * @return mixed
     */
    public function actionIndex($webpage)
    {
        $itemsPerPage = 12;

        $qB = Promotion::getActiveListQuery();
        $pager = new Pagination([
            'totalCount' => $qB->count(),
            'defaultPageSize' => $itemsPerPage,
            'route' => 'promotions/webpage',
            'params' => array_merge($_GET, ['id' => $webpage->id])
        ]);
        return $this->render('index', [
            'promotions' => $qB->limit($pager->limit)->offset($pager->offset)->all(),
            'pager' => $pager,
            'webpage' => $webpage,
            'h1' => $webpage->title,
        ]);
    }

    /**
     * Displays a single Promotion model.
     * @param string $id
     * @param Webpage $webpage
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($id, $webpage)
    {
        $promotion = $this->findModel($id);
        return $this->render('view', [
            'promotion' => $promotion,
            'webpage' => $webpage,
            'h1' => $promotion->name,
            'promotionsWebpage' => Webpage::findOne(['module_id' => Module::getModuleIdByControllerAndAction('promotion', 'index')]),
        ]);
    }

    /**
     * Finds the Promotion model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Promotion the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Promotion::findOne($id)) !== null && $model->active == Promotion::STATUS_ACTIVE) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
