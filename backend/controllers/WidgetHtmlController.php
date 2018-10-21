<?php

namespace backend\controllers;

use common\models\Module;
use common\models\Webpage;
use common\models\WidgetHtml;
use yii;
use common\models\Page;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * PageController implements the CRUD actions for WidgetHtml model.
 */
class WidgetHtmlController extends AdminController
{
    protected $accessRule = 'manageWidgetHtml';

    /**
     * Lists all Html Widgets.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => WidgetHtml::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Html Widget.
     * If creation is successful, the browser will be redirected to the 'update' page.
     * @return mixed
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
        return $this->processWidgetData(new WidgetHtml());
    }

    /**
     * Updates an existing Html Widget.
     * If update is successful, the browser will be redirected to the 'update' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws yii\db\Exception
     */
    public function actionUpdate($id)
    {
        return $this->processWidgetData($this->findModel($id));
    }

    /**
     * @param WidgetHtml $widget
     * @return string|yii\web\Response
     */
    private function processWidgetData(WidgetHtml $widget)
    {
        if (Yii::$app->request->isPost) {
            $isNew = $widget->isNewRecord;
            if (!$widget->load(Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
            else {
                $postData = Yii::$app->request->post('WidgetHtml');
                $widget->content = ($postData['editor'] == 1) ? $postData['content']['editor'] : $postData['content']['plain'];
                if (!$widget->save()) $widget->moveErrorsToFlash();
                else {
                    Yii::$app->session->addFlash('success', $isNew ? 'Блок добавлен' : 'Блок обновлён');
                    return $this->redirect(['update', 'id' => $widget->id]);
                }
            }
        }

        return $this->render('update', ['widget' => $widget]);
    }

    /**
     * Deletes an existing Page model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws yii\db\Exception
     */
    public function actionDelete($id)
    {
        $page = $this->findModel($id);
        $transaction = Page::getDb()->beginTransaction();
        try {
            if (!$page->delete()) {
                $page->moveErrorsToFlash();
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
     * Finds the WidgetHtml model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return WidgetHtml the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = WidgetHtml::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
