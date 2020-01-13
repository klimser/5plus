<?php
namespace backend\controllers;

use common\models\BotMailing;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * PushController allows to send custom push-messages.
 */
class BotMailingController extends AdminController
{
    protected $accessRule = 'manager';
    
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => BotMailing::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'createAllowed' => Yii::$app->user->can('sendPush'),
        ]);
    }

    /**
     * Views an existing BotMailing model.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $botMailing = $this->findModel($id);

        return $this->render('view', [
            'botMailing' => $botMailing,
        ]);
    }
    
    public function actionCreate()
    {
        if (!Yii::$app->user->can('sendPush')) throw new ForbiddenHttpException('Access denied!');

        $botMailing = new BotMailing();
        if (Yii::$app->request->isPost) {
            $transaction = Yii::$app->getDb()->beginTransaction();
            try {
                if (!$botMailing->load(Yii::$app->request->post())) Yii::$app->session->addFlash('error', 'Form data not found');
                else {
                    $botMailing->imageFile = UploadedFile::getInstance($botMailing, 'imageFile');
                    if (!$botMailing->save()) {
                        $botMailing->moveErrorsToFlash();
                        $transaction->rollBack();
                    } else {
                        if ($botMailing->imageFile) {
                            $newPath = '/telegram/' . uniqid() . '.' . $botMailing->imageFile->extension;
                            if ($botMailing->imageFile->saveAs(Yii::getAlias('@uploads') . $newPath)) {
                                $botMailing->message_image = $newPath;
                                $botMailing->imageFile = null;
                            } else {
                                $botMailing->addError('image', 'Error uploading file');
                            }
                            
                            if (!$botMailing->save()) {
                                Yii::$app->session->addFlash('error', 'Unable to upload image');
                                $transaction->rollBack();
                            }
                        }
                        
                        if (!$botMailing->hasErrors()) {
                            $transaction->commit();
                            Yii::$app->session->addFlash('success', 'Успешно добавлено');
                            return $this->redirect(['index']);
                        }
                    }
                }
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        
        return $this->render('create', ['botMailing' => $botMailing]);
    }

    /**
     * Deletes an existing BotMailing model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function actionDelete($id)
    {
        $botMailing = $this->findModel($id);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$botMailing->delete()) {
                $botMailing->moveErrorsToFlash();
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
     * Finds the BotMailing model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return BotMailing the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = BotMailing::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested mailing does not exist.');
        }
    }
}
