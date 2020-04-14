<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Module;
use common\models\Teacher;
use common\models\Webpage;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;

class TeacherController extends Controller
{
    /**
     * Displays a Teachers page.
     * @param $webpage Webpage
     * @return mixed
     */
    public function actionIndex($webpage)
    {
        $itemsPerPage = 9;
        $qB = Teacher::getVisibleListQuery();
        $pager = new Pagination([
            'totalCount' => $qB->count(),
            'defaultPageSize' => $itemsPerPage,
            'route' => 'teachers/webpage',
            'params' => array_merge($_GET, ['id' => $webpage->id])
        ]);
        return $this->render('index', [
            'pager' => $pager,
            'teachers' => $qB->limit($pager->limit)->offset($pager->offset)->all(),
            'webpage' => $webpage,
            'h1' => $webpage->title,
        ]);
    }

    /**
     * Displays a single Subject model.
     * @param string $id
     * @param Webpage $webpage
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($id, $webpage)
    {
        $teacher = $this->findModel($id);
        return $this->render('view', [
            'teacher' => $teacher,
            'webpage' => $webpage,
            'h1' => $teacher->officialName,
            'teachersWebpage' => Webpage::findOne(['module_id' => Module::getModuleIdByControllerAndAction('teacher', 'index')]),
        ]);
    }

    /**
     * Finds the Teacher model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Teacher the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Teacher::findOne($id)) !== null && $model->page_visibility == Teacher::STATUS_ACTIVE) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
