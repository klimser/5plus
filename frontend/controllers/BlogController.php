<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Module;
use common\models\Blog;
use common\models\Webpage;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;

class BlogController extends Controller
{
    /**
     * Displays a Blog page.
     * @param $webpage Webpage
     * @return mixed
     */
    public function actionIndex($webpage)
    {
        $itemsPerPage = 6;

        $qB = Blog::getActiveListQuery();
        $pager = new Pagination([
            'totalCount' => $qB->count(),
            'defaultPageSize' => $itemsPerPage,
            'route' => 'blog/webpage',
            'params' => array_merge($_GET, ['id' => $webpage->id])
        ]);
        return $this->render('index', [
            'posts' => $qB->limit($pager->limit)->offset($pager->offset)->all(),
            'pager' => $pager,
            'webpage' => $webpage,
            'h1' => $webpage->title,
            'hide_social' => true,
        ]);
    }

    /**
     * Displays a single Blog model.
     * @param string $id
     * @param Webpage $webpage
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($id, $webpage)
    {
        $blog = $this->findModel($id);
        return $this->render('view', [
            'post' => $blog,
            'webpage' => $webpage,
            'h1' => $blog->name,
            'blogWebpage' => Webpage::findOne(['module_id' => Module::getModuleIdByControllerAndAction('blog', 'index')]),
        ]);
    }

    /**
     * Finds the Blog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Blog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Blog::findOne($id)) !== null && $model->active == Blog::STATUS_ACTIVE) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
