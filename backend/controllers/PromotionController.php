<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use common\models\Module;
use common\models\Promotion;
use common\models\Webpage;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * PromotionController implements the CRUD actions for Promotion model.
 */
class PromotionController extends AdminController
{
    use Active;

    protected $accessRule = 'managePromotions';

    /**
     * Lists all Promotion models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Promotion::find()->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 50,],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes' => ['created_at', 'name'],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Promotion model.
     * If creation is successful, the browser will be redirected to the 'page' page.
     * @return mixed
     */
    public function actionCreate()
    {
        return $this->processPromotionData(new Promotion());
    }

    /**
     * Updates an existing Promotion model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function actionUpdate($id)
    {
        return $this->processPromotionData($this->findModel($id));
    }

    /**
     * @param Promotion $promotion
     * @return string|\yii\web\Response
     * @throws \Exception
     */
    public function processPromotionData(Promotion $promotion)
    {
        if (\Yii::$app->request->isPost) {
            $isNew = $promotion->isNewRecord;
            $transaction = \Yii::$app->getDb()->beginTransaction();
            try {
                /*     Сохраняем акцию      */
                if (!$promotion->load(\Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
                else {
                    $promotion->imageFile = UploadedFile::getInstance($promotion, 'imageFile');
                    if (!$promotion->save()) {
                        $promotion->moveErrorsToFlash();
                        $transaction->rollBack();
                    } else {
                        /*     Сохраняем картинку      */
                        if ($promotion->imageFile && (!$promotion->upload() || !$promotion->save(true, ['image']))) {
                            \Yii::$app->session->addFlash('error', 'Unable to upload image');
                            $transaction->rollBack();
                        } else {
                            /*     Сохраняем страничку      */
                            if (!$promotion->webpage_id) {
                                $webpage = new Webpage();
                                $webpage->module_id = Module::getModuleIdByControllerAndAction('promotion', 'view');
                                $webpage->record_id = $promotion->id;
                            } else {
                                $webpage = $promotion->webpage;
                            }
                            if (!$webpage->load(\Yii::$app->request->post())) {
                                \Yii::$app->session->addFlash('error', 'Form data not found');
                                $transaction->rollBack();
                            } elseif (!$webpage->save()) {
                                $webpage->moveErrorsToFlash();
                                $transaction->rollBack();
                            } else {
                                if (!$promotion->webpage_id) $promotion->link('webpage', $webpage);
                                $transaction->commit();
                                \Yii::$app->session->addFlash('success', $isNew ? 'Акция добавлена' : 'Акция обновлена');
                                return $this->redirect(['update', 'id' => $promotion->id]);
                            }
                        }
                    }
                }
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('update', [
            'promotion' => $promotion,
            'module' => Module::getModuleByControllerAndAction('promotion', 'view'),
        ]);
    }

    /**
     * Deletes an existing Promotion model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function actionDelete($id)
    {
        $news = $this->findModel($id);
        $transaction = Promotion::getDb()->beginTransaction();
        try {
            if (!$news->delete()) {
                $news->moveErrorsToFlash();
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $this->redirect(['index']);
    }

    /**
     * @return string
     */
    public function actionPage()
    {
        $webpage = null;
        $moduleId = Module::getModuleIdByControllerAndAction('promotion', 'index');
        $webpage = Webpage::find()->where(['module_id' => $moduleId])->one();
        if (!$webpage) {
            $webpage = new Webpage();
            $webpage->module_id = $moduleId;
        }

        if (\Yii::$app->request->isPost) {
            if (!$webpage->load(\Yii::$app->request->post())) {
                \Yii::$app->session->addFlash('error', 'Form data not found');
            } elseif (!$webpage->save()) {
                $webpage->moveErrorsToFlash();
            } else {
                \Yii::$app->session->addFlash('success', 'Изменения сохранены');
                return $this->redirect(['page']);
            }
        }

        return $this->render('page', ['webpage' => $webpage]);
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
        if (($model = Promotion::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested promotion does not exist.');
        }
    }
}
