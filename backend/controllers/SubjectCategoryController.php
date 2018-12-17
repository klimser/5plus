<?php

namespace backend\controllers;

use backend\controllers\traits\Sortable;
use common\models\Module;
use common\models\SubjectCategory;
use common\models\Webpage;
use yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * PageController implements the CRUD actions for SubjectCategory model.
 */
class SubjectCategoryController extends AdminController
{
    use Sortable;

    protected $accessRule = 'manageSubjectCategories';

    /**
     * Lists all SubjectCategory models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => SubjectCategory::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new SubjectCategory model.
     * If creation is successful, the browser will be redirected to the 'page' page.
     * @return mixed
     * @throws \Exception
     */
    public function actionCreate()
    {
        return $this->processSubjectCategoryData(new SubjectCategory());
    }

    /**
     * Updates an existing SubjectCategory model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function actionUpdate($id)
    {
        return $this->processSubjectCategoryData($this->findModel($id));
    }

    /**
     * @param SubjectCategory $subjectCategory
     * @return string|yii\web\Response
     * @throws \Exception
     */
    public function processSubjectCategoryData(SubjectCategory $subjectCategory)
    {
        if (Yii::$app->request->isPost) {
            $isNew = $subjectCategory->isNewRecord;
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                /*     Сохраняем группу курсов      */
                if (!$subjectCategory->load(Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
                elseif (!$subjectCategory->save()) {
                    $subjectCategory->moveErrorsToFlash();
                    $transaction->rollBack();
                } else {
                    /*     Сохраняем страничку      */
                    if (!$subjectCategory->webpage_id) {
                        $webpage = new Webpage();
                        $webpage->module_id = Module::getModuleIdByControllerAndAction('subject-category', 'view');
                        $webpage->record_id = $subjectCategory->id;
                    } else {
                        $webpage = $subjectCategory->webpage;
                    }
                    if (!$webpage->load(Yii::$app->request->post())) {
                        \Yii::$app->session->addFlash('error', 'Form data not found');
                        $transaction->rollBack();
                    } elseif (!$webpage->save()) {
                        $webpage->moveErrorsToFlash();
                        $transaction->rollBack();
                    } else {
                        if (!$subjectCategory->webpage_id) $subjectCategory->link('webpage', $webpage);
                        $transaction->commit();
                        Yii::$app->session->addFlash('success', $isNew ? 'Группа курсов добавлена' : 'Группа курсов обновлена');
                        return $this->redirect(['update', 'id' => $subjectCategory->id]);
                    }
                }
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('update', [
            'subjectCategory' => $subjectCategory,
            'module' => Module::getModuleByControllerAndAction('subject-category', 'view'),
        ]);
    }

    public function actionPage()
    {
        $prefix = 'category_';
        if (Yii::$app->request->isPost) {
            try {
                $this->saveSortedData($prefix);
                Yii::$app->session->addFlash('success', 'Изменения сохранены');
                return $this->redirect(['page']);
            } catch (\Throwable $exception) {
                \Yii::$app->session->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('page', [
            'categories' => SubjectCategory::find()->orderBy('page_order')->all(),
            'prefix' => $prefix,
        ]);
    }

    /**
     * Deletes an existing SubjectCategory model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the SubjectCategory model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return SubjectCategory the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SubjectCategory::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
