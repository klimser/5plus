<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use common\models\Blog;
use common\models\Module;
use common\models\Webpage;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * BlogController implements the CRUD actions for Blog model.
 */
class BlogController extends AdminController
{
    use Active;

    protected $accessRule = 'manageBlog';

    /**
     * Lists all Blog models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Blog::find()->orderBy(['created_at' => SORT_DESC]),
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
     * Creates a new Blog model.
     * If creation is successful, the browser will be redirected to the 'page' page.
     * @return mixed
     */
    public function actionCreate()
    {
        return $this->processBlogData(new Blog());
    }

    /**
     * Updates an existing Blog model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function actionUpdate($id)
    {
        return $this->processBlogData($this->findModel($id));
    }

    /**
     * @param Blog $blog
     * @return string|\yii\web\Response
     * @throws \Exception
     */
    public function processBlogData(Blog $blog)
    {
        if (\Yii::$app->request->isPost) {
            $isNew = $blog->isNewRecord;
            $transaction = \Yii::$app->getDb()->beginTransaction();
            try {
                /*     Сохраняем пост      */
                if (!$blog->load(\Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
                else {
                    $blog->imageFile = UploadedFile::getInstance($blog, 'imageFile');
                    if (!$blog->save()) {
                        $blog->moveErrorsToFlash();
                        $transaction->rollBack();
                    } else {
                        /*     Сохраняем картинку      */
                        if ($blog->imageFile && (!$blog->upload() || !$blog->save(true, ['image']))) {
                            \Yii::$app->session->addFlash('error', 'Unable to upload image');
                            $transaction->rollBack();
                        } else {
                            /*     Сохраняем страничку      */
                            if (!$blog->webpage_id) {
                                $webpage = new Webpage();
                                $webpage->module_id = Module::getModuleIdByControllerAndAction('blog', 'view');
                                $webpage->record_id = $blog->id;
                            } else {
                                $webpage = $blog->webpage;
                            }
                            if (!$webpage->load(\Yii::$app->request->post())) {
                                \Yii::$app->session->addFlash('error', 'Form data not found');
                                $transaction->rollBack();
                            } elseif (!$webpage->save()) {
                                $webpage->moveErrorsToFlash();
                                $transaction->rollBack();
                            } else {
                                if (!$blog->webpage_id) $blog->link('webpage', $webpage);
                                $transaction->commit();
                                \Yii::$app->session->addFlash('success', $isNew ? 'Пост добавлен' : 'Пост обновлён');
                                return $this->redirect(['update', 'id' => $blog->id]);
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
            'blog' => $blog,
            'module' => Module::getModuleByControllerAndAction('blog', 'view'),
        ]);
    }

    /**
     * Deletes an existing Blog model.
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
        $moduleId = Module::getModuleIdByControllerAndAction('blog', 'index');
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
     * Finds the Blog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Blog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Blog::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested blog post does not exist.');
        }
    }
}
