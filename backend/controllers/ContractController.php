<?php

namespace backend\controllers;

use backend\models\Contract;
use backend\models\ContractSearch;
use backend\models\Group;
use backend\models\GroupParam;
use backend\models\User;
use common\components\Action;
use yii;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;

/**
 * ContractController implements contracts management.
 */
class ContractController extends AdminController
{
    protected $accessRule = 'moneyManagement';

     /**
     * Print Contract.
     * @param int $id
     * @return mixed
     */
    public function actionPrint($id)
    {
        $contract = $this->findModel($id);
        $pupilType = 'individual';
        if ($contract->user->parent_id && $contract->user->parent->role == User::ROLE_COMPANY) {
            $pupilType = 'company';
        }

        $this->layout = 'print';
        return $this->render("/contract/print/$pupilType", ['contract' => $contract]);
    }

    /**
     * @param $id
     * @return yii\web\Response
     */
    public function actionBarcode($id)
    {
        $contract = $this->findModel($id);
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $response = new \yii\web\Response();
        $response->sendContentAsFile(
            $generator->getBarcode($contract->number, $generator::TYPE_CODE_128),
            "barcode.svg",
            ['mimeType' => 'image/svg+xml', 'inline' => true]
        );
        return $response;
    }

    /**
     * Monitor all Contracts.
     * @return string
     */
    public function actionIndex()
    {
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
     */
    public function actionFind()
    {
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
     * Create new contract
     * @return mixed
     */
    public function actionCreate()
    {
        if (\Yii::$app->request->isPost) {
            $userId = Yii::$app->request->post('user_id');
            $groupId = Yii::$app->request->post('group_id');
            $amount = Yii::$app->request->post('amount');
            $discount = boolval(Yii::$app->request->post('discount', 0));

            if (!$userId) \Yii::$app->session->addFlash('error', 'No user');
            elseif (!$groupId) \Yii::$app->session->addFlash('error', 'No group');
            elseif ($amount <= 0) \Yii::$app->session->addFlash('error', 'Wrong amount');
            else {
                $user = User::findOne($userId);
                $group = Group::findOne($groupId);
                if (!$user || $user->role != User::ROLE_PUPIL) \Yii::$app->session->addFlash('error', 'Wrong pupil');
                elseif (!$group || $group->active != Group::STATUS_ACTIVE) \Yii::$app->session->addFlash('error', 'Wrong group');
                else {
                    $contract = new Contract();
                    $contract->created_admin_id = Yii::$app->user->id;
                    $contract->user_id = $user->id;
                    $contract->group_id = $group->id;
                    $contract->amount = $amount;
                    $contract->discount = $discount ? Contract::STATUS_ACTIVE : Contract::STATUS_INACTIVE;
                    $contract->created_at = date('Y-m-d H:i:s');

                    $groupParam = GroupParam::findByDate($group, new \DateTime());

                    if ($contract->discount == Contract::STATUS_ACTIVE
                        && (($groupParam && $amount < $groupParam->price3Month) || (!$groupParam && $amount < $group->price3Month))) {
                        \Yii::$app->session->addFlash('error', 'Wrong pupil');
                    } else {
                        $numberPrefix = $contract->createDate->format('Ymd') . $user->id;
                        $numberAffix = 1;
                        while (Contract::find()->andWhere(['number' => $numberPrefix . $numberAffix])->select('COUNT(id)')->scalar() > 0) {
                            $numberAffix++;
                        }
                        $contract->number = $numberPrefix . $numberAffix;

                        if (!$contract->save()) \Yii::$app->session->addFlash('error', 'Не удалось создать договор: ' . $contract->getErrorsAsString());
                        else {
                            Yii::$app->session->addFlash(
                                'success',
                                'Договор ' . $contract->number . ' зарегистрирован '
                                . '<a target="_blank" href="' . yii\helpers\Url::to(['contract/print', 'id' => $contract->id]) . '">Распечатать</a>'
                            );
                            \Yii::$app->actionLogger->log(
                                $user,
                                Action::TYPE_CONTRACT_ADDED,
                                $contract->amount,
                                $group
                            );
                        }
                    }
                }
            }
        }

        $params = [];
        $userId = Yii::$app->request->get('user');
        if ($userId) {
            $user = User::findOne($userId);
            if ($user) $params['user'] = $user;
        }
        return $this->render('create', $params);
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