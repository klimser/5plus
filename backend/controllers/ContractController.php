<?php

namespace backend\controllers;

use backend\models\Contract;
use backend\models\ContractSearch;
use backend\models\GroupParam;
use backend\models\Group;
use backend\models\User;
use yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;

/**
 * ContractController implements contracts management.
 */
class ContractController extends AdminController
{
     /**
     * Print Contract.
     * @param int $id
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionPrint($id)
    {
//        if (!Yii::$app->user->can('moneyDebt')) throw new ForbiddenHttpException('Access denied!');
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');

        $contract = $this->findModel($id);
        $pupilType = 'individual';
        if ($contract->user->parent_id && $contract->user->parent->role == User::ROLE_COMPANY) {
            $pupilType = 'company';
        }

        $this->layout = 'print';
        return $this->render("/contract/print/$pupilType", ['contract' => $contract]);
    }

    /**
     * Monitor all Contracts.
     * @return string
     * @throws ForbiddenHttpException
     */
    public function actionIndex()
    {
//        if (!Yii::$app->user->can('moneyPayment')) throw new ForbiddenHttpException('Access denied!');
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');

        $searchModel = new ContractSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $students */
        $students = User::find()->where(['role' => User::ROLE_PUPIL])->orderBy(['name' => SORT_ASC])->all();
        $studentMap = [null => 'Все'];
        foreach ($students as $student) $studentMap[$student->id] = $student->name;

        /** @var Group[] $groups */
        $groups = Group::find()->orderBy(['active' => SORT_DESC, 'name' => SORT_ASC])->all();
        $groupMap = [null => 'Все'];
        foreach ($groups as $group) $groupMap[$group->id] = $group->name;

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'studentMap' => $studentMap,
            'groupMap' => $groupMap,
        ]);
    }

    /**
     * @return yii\web\Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionFind()
    {
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');
        if (!Yii::$app->request->isAjax) throw new BadRequestHttpException('Request is not AJAX');

        $jsonData = self::getJsonOkResult();
        $contractNum = preg_replace('#\D#', '', Yii::$app->request->post('number', ''));

        if (empty($contractNum)) $jsonData = self::getJsonErrorResult('Неверный номер договора');
        else {
            $contract = Contract::findOne(['number' => $contractNum]);
            if (!$contract) $jsonData = self::getJsonErrorResult('Договор не найден');
            elseif ($contract->status != Contract::STATUS_NEW) $jsonData = self::getJsonErrorResult('Договор уже оплачен!');
            else {
                $jsonData['id'] = $contract->id;
                $jsonData['user_name'] = $contract->user->name;
                $jsonData['group_name'] = $contract->group->name;
                $jsonData['amount'] = $contract->amount;
                $jsonData['discount'] = $contract->discount;
                $jsonData['create_date'] = $contract->createDate->format('d.m.Y');
            }
        }

        return $this->asJson($jsonData);
    }

    /**
     * Finds the Contract model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Contract the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Contract::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}