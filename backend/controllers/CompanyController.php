<?php

namespace backend\controllers;

use common\models\Company;
use common\models\CompanySearch;
use yii\web\NotFoundHttpException;

/**
 * CompanyController implements the CRUD actions for Company model.
 */
class CompanyController extends AdminController
{
    /**
     * Lists all Company models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CompanySearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Company model.
     * If creation is successful, the browser will be redirected to the 'page' page.
     * @return mixed
     */
    public function actionCreate()
    {
        return $this->processCompanyData(new Company());
    }

    /**
     * Updates an existing Company model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function actionUpdate($id)
    {
        return $this->processCompanyData($this->findModel($id));
    }

    /**
     * @param Company $company
     * @return string|\yii\web\Response
     * @throws \Exception
     */
    public function processCompanyData(Company $company)
    {
        if (\Yii::$app->request->isPost) {
            $isNew = $company->isNewRecord;
            $transaction = \Yii::$app->getDb()->beginTransaction();
            try {
                if (!$company->load(\Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
                elseif (!$company->save()) {
                    $company->moveErrorsToFlash();
                    $transaction->rollBack();
                } else {
                    $transaction->commit();
                    \Yii::$app->session->addFlash('success', $isNew ? 'Пост добавлен' : 'Пост обновлён');
                    return $this->redirect(['update', 'id' => $company->id]);
                }
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('update', [
            'company' => $company,
        ]);
    }

    /**
     * Deletes an existing Company model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Company model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Company the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Company::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
