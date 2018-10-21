<?php

namespace backend\controllers;

use common\models\WidgetHtml;
use yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * PageController implements the CRUD actions for Page model.
 */
class BannerController extends AdminController
{
    public function beforeAction($action)
    {
        if (parent::beforeAction($action) && Yii::$app->user->can('manageBanner')) {
            return true;
        }
        throw new ForbiddenHttpException('Access denied!');
    }

    /**
     * Lists all Page models.
     * @return mixed
     */
    public function actionIndex()
    {
        $banner = $this->findModel(WidgetHtml::BANNER_ID);
        if (Yii::$app->request->isPost) {
            $banner->load(Yii::$app->request->post());
            if ($banner->save(true)) {
                Yii::$app->session->setFlash('success', 'Успешно обновлено');
                WidgetHtml::clearBannerCache();
            } else $banner->moveErrorsToFlash();
        }
        return $this->render('index', ['banner' => $banner]);
    }

    /**
     * Finds the WidgetHtml model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return WidgetHtml the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = WidgetHtml::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested record does not exist.');
        }
    }
}
