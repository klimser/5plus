<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Module;
use common\models\Review;
use common\models\SubjectCategory;
use common\models\Webpage;
use common\models\Page;
use yii\web\NotFoundHttpException;

/**
 * PageController implements the CRUD actions for Page model.
 */
class PageController extends Controller
{
    /**
     * Displays a single Page model.
     * @param string $id
     * @param $webpage Webpage
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($id, $webpage)
    {
        $page = $this->findModel($id);
        if ($page->isActive()) {
            $params = [
                'page' => $page,
                'webpage' => $webpage,
                'h1' => $page->title,
            ];
            if ($webpage->main) {
                $params['subjectCategoryCollection'] = SubjectCategory::find()
                    ->joinWith('activeSubjects')
                    ->with('activeSubjects.webpage')
                    ->orderBy('page_order')
                    ->all();
                $params['reviews'] = Review::find()->where(['status' => Review::STATUS_APPROVED])->orderBy('rand()')->limit(10)->all();
                $params['quizWebpage'] = Webpage::findOne(['module_id' => Module::getModuleIdByControllerAndAction('quiz', 'list')]);
                $params['paymentWebpage'] = Webpage::findOne(['module_id' => Module::getModuleIdByControllerAndAction('payment', 'index')]);

                return $this->render('main', $params);
            }
            return $this->render('view', $params);
        } else {
            throw new NotFoundHttpException('Page is not exists');
        }
    }

    /**
     * Finds the Page model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Page the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Page::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
