<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use backend\controllers\traits\Sortable;
use common\models\HighSchool;
use common\models\Module;
use common\models\Webpage;
use yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * HighSchoolController implements the CRUD actions for HightSchool model.
 */
class HighSchoolController extends AdminController
{
    use Active, Sortable;

    protected $accessRule = 'manageHighSchools';

    /**
     * Lists all HightSchool models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => HighSchool::find()->where(['type' => HighSchool::TYPE_HIGHSCHOOL])->orderBy(['active' => SORT_DESC, 'name' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new HighSchool model.
     * If creation is successful, the browser will be redirected to the 'page' page.
     * @return mixed
     */
    public function actionCreate()
    {
        return $this->processHighSchoolData(new HighSchool());
    }

    /**
     * Updates an existing HighSchool model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function actionUpdate($id)
    {
        return $this->processHighSchoolData($this->findModel($id));
    }

    /**
     * @param HighSchool $highSchool
     * @return string|yii\web\Response
     * @throws \Exception
     */
    public function processHighSchoolData(HighSchool $highSchool)
    {
        if (\Yii::$app->request->isPost) {
            $isNew = $highSchool->isNewRecord;
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                /*     Сохраняем ВУЗ      */
                if (!$highSchool->load(Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
                else {
                    $highSchool->type = HighSchool::TYPE_HIGHSCHOOL;
                    $highSchool->photoFile = yii\web\UploadedFile::getInstance($highSchool, 'photoFile');
                    if (!$highSchool->save()) {
                        $highSchool->moveErrorsToFlash();
                        $transaction->rollBack();
                    } else {
                        /*     Сохраняем картинку      */
                        if ($highSchool->photoFile && (!$highSchool->upload() || !$highSchool->save(true, ['photo']))) {
                            \Yii::$app->session->addFlash('error', 'Unable to upload image');
                            $transaction->rollBack();
                        } else {
                            $transaction->commit();
                            Yii::$app->session->addFlash('success', $isNew ? 'ВУЗ добавлен' : 'ВУЗ обновлён');
                            return $this->redirect(['update', 'id' => $highSchool->id]);
                        }
                    }
                }
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('update', [
            'highSchool' => $highSchool,
            'label' => 'ВУЗ',
            'labelParent' => 'ВУЗы',
        ]);
    }

    /**
     * Deletes a Photo of high school.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionDeletePhoto($id)
    {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            $highSchool = $this->findModel($id);
            if ($highSchool->photo) $highSchool->deleteImage();
            $jsonData = $highSchool->save() ? self::getJsonOkResult() : self::getJsonErrorResult($highSchool->getErrorsAsString());
        }
        return $this->asJson($jsonData);
    }

    /**
     * Deletes a high school.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if (!$model->delete()) {
            $model->moveErrorsToFlash();
        }

        return $this->redirect(['index']);
    }

    /**
     * @return string
     */
    public function actionPage()
    {
        $prefix = 'highshchool_';
        $webpage = null;
        $moduleId = Module::getModuleIdByControllerAndAction('high-school', 'index');
        $webpage = Webpage::find()->where(['module_id' => $moduleId])->one();
        if (!$webpage) {
            $webpage = new Webpage();
            $webpage->module_id = $moduleId;
        }

        if (Yii::$app->request->isPost) {
            if (!$webpage->load(Yii::$app->request->post())) {
                \Yii::$app->session->addFlash('error', 'Form data not found');
            } elseif (!$webpage->save()) {
                $webpage->moveErrorsToFlash();
            } else {
                try {
                    $this->saveSortedData($prefix);
                    Yii::$app->session->addFlash('success', 'Изменения сохранены');
                    return $this->redirect(['page']);
                } catch (\Throwable $exception) {
                    \Yii::$app->session->addFlash('error', $exception->getMessage());
                }
            }
        }

        return $this->render('page', [
            'webpage' => $webpage,
            'highSchools' => HighSchool::find()->where(['active' => HighSchool::STATUS_ACTIVE, 'type' => HighSchool::TYPE_HIGHSCHOOL])->orderBy('page_order')->all(),
            'prefix' => $prefix,
            'title' => 'ВУЗы',
        ]);
    }

    /**
     * Finds the HighSchool model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return HighSchool the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = HighSchool::findOne($id)) !== null && $model->type == HighSchool::TYPE_HIGHSCHOOL) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
