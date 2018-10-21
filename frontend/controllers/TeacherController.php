<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Module;
use common\models\Teacher;
use common\models\Webpage;
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
        $qB = Teacher::getVisibleListQuery();
        $itemsPerPage = 8;
        if (\Yii::$app->request->isAjax) {
            $teachers = $qB->limit($itemsPerPage)->offset(\Yii::$app->request->get('loaded'))->all();
            $output = '';
            $i = 0;
            foreach ($teachers as $teacher) {
                $i++;
                $output .= $this->renderPartial('_block', ['teacher' => $teacher]);
                if ($i % 2 == 0) $output .= '<div class="clearfix"></div>';
            }
            return $output;
        } else {
            $subjectsTotal = $qB->count();
            return $this->render('index', [
                'teachers' => $qB->limit($itemsPerPage)->all(),
                'hasMore' => $subjectsTotal > $itemsPerPage,
                'webpage' => $webpage,
                'h1' => $webpage->title,
            ]);
        }
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
