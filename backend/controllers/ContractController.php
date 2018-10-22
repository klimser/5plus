<?php

namespace backend\controllers;

use backend\components\MoneyComponent;
use backend\models\ActionSearch;
use backend\models\Contract;
use backend\models\ContractSearch;
use backend\models\Event;
use backend\models\EventMember;
use backend\models\GroupParam;
use backend\models\GroupPupil;
use common\components\Action;
use backend\models\Group;
use backend\models\Payment;
use backend\models\User;
use common\components\helpers\Calendar;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

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