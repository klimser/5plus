<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Module;
use common\models\News;
use common\models\Webpage;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;

class NewsController extends Controller
{
    /**
     * Displays a News page.
     * @param Webpage $webpage
     * @return mixed
     */
    public function actionIndex($webpage)
    {
        $itemsPerPage = 6;

        $qB = News::getActiveListQuery();
        $pager = new Pagination([
            'totalCount' => $qB->count(),
            'defaultPageSize' => $itemsPerPage,
            'route' => 'news/webpage',
            'params' => array_merge($_GET, ['id' => $webpage->id])
        ]);
        return $this->render('index', [
            'newsList' => $qB->limit($pager->limit)->offset($pager->offset)->all(),
            'pager' => $pager,
            'webpage' => $webpage,
            'h1' => $webpage->title,
            'hide_social' => true,
        ]);
    }

    /**
     * Displays a single News model.
     * @param string $id
     * @param Webpage $webpage
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($id, $webpage)
    {
        $news = $this->findModel($id);
        return $this->render('view', [
            'news' => $news,
            'webpage' => $webpage,
            'h1' => $news->name,
            'newsWebpage' => Webpage::findOne(['module_id' => Module::getModuleIdByControllerAndAction('news', 'index')]),
        ]);
    }

    /**
     * Finds the News model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return News the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = News::findOne($id)) !== null && $model->active == News::STATUS_ACTIVE) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
