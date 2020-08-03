<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use common\models\Module;
use common\models\News;
use common\models\Webpage;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * NewsController implements the CRUD actions for News model.
 */
class NewsController extends AdminController
{
    use Active;

    protected $accessRule = 'manageNews';

    /**
     * Lists all News models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => News::find()->orderBy(['created_at' => SORT_DESC]),
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
     * Creates a new News model.
     * If creation is successful, the browser will be redirected to the 'page' page.
     * @return mixed
     */
    public function actionCreate()
    {
        return $this->processNewsData(new News());
    }

    /**
     * Updates an existing News model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function actionUpdate($id)
    {
        return $this->processNewsData($this->findModel($id));
    }

    /**
     * @param News $news
     * @return string|\yii\web\Response
     * @throws \Exception
     */
    public function processNewsData(News $news)
    {
        if (\Yii::$app->request->isPost) {
            $isNew = $news->isNewRecord;
            $transaction = \Yii::$app->getDb()->beginTransaction();
            try {
                /*     Сохраняем новость      */
                if (!$news->load(\Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
                else {
                    $news->imageFile = UploadedFile::getInstance($news, 'imageFile');
                    if (!$news->save()) {
                        $news->moveErrorsToFlash();
                        $transaction->rollBack();
                    } else {
                        /*     Сохраняем картинку      */
                        if ($news->imageFile && (!$news->upload() || !$news->save(true, ['image']))) {
                            \Yii::$app->session->addFlash('error', 'Unable to upload image');
                            $transaction->rollBack();
                        } else {
                            /*     Сохраняем страничку      */
                            if (!$news->webpage_id) {
                                $webpage = new Webpage();
                                $webpage->module_id = Module::getModuleIdByControllerAndAction('news', 'view');
                                $webpage->record_id = $news->id;
                            } else {
                                $webpage = $news->webpage;
                            }
                            if (!$webpage->load(\Yii::$app->request->post())) {
                                \Yii::$app->session->addFlash('error', 'Form data not found');
                                $transaction->rollBack();
                            } elseif (!$webpage->save()) {
                                $webpage->moveErrorsToFlash();
                                $transaction->rollBack();
                            } else {
                                if (!$news->webpage_id) $news->link('webpage', $webpage);
                                $transaction->commit();
                                \Yii::$app->session->addFlash('success', $isNew ? 'Новость добавлена' : 'Новость обновлена');
                                return $this->redirect(['update', 'id' => $news->id]);
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
            'news' => $news,
            'module' => Module::getModuleByControllerAndAction('news', 'view'),
        ]);
    }

    /**
     * Deletes an existing News model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionDelete($id)
    {
        $news = $this->findModel($id);
        $transaction = \Yii::$app->getDb()->beginTransaction();
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
        $moduleId = Module::getModuleIdByControllerAndAction('news', 'index');
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
     * Finds the News model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return News the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = News::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested news does not exist.');
        }
    }
}
