<?php

namespace backend\controllers;

use backend\components\report\PupilReport;
use backend\components\SalaryComponent;
use common\components\MoneyComponent;
use backend\models\ActionSearch;
use common\models\Company;
use common\models\Contract;
use common\models\GiftCard;
use common\models\GroupParam;
use common\models\GroupPupil;
use common\components\Action;
use common\models\Debt;
use common\models\DebtSearch;
use common\models\Group;
use common\models\Payment;
use common\models\PaymentSearch;
use common\models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use yii;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * MoneyController implements money management.
 */
class MoneyController extends AdminController
{
    /**
     * Register money income
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionIncome()
    {
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');

        $params = [
            'companies' => Company::find()->orderBy(['second_name' => SORT_ASC])->all(),
            'groups' => Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->orderBy('name')->with('teacher')->all(),
        ];
        $userId = Yii::$app->request->get('user');
        if ($userId) {
            $user = User::findOne($userId);
            if ($user) $params['user'] = $user;
        }
        return $this->render('income', $params);
    }

    /**
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessIncome()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('moneyManagement');

        Yii::$app->response->format = Response::FORMAT_JSON;
        $formData = Yii::$app->request->post('income', []);

        if (!isset($formData['userId'], $formData['groupId'], $formData['amount'], $formData['comment'])) {
            return self::getJsonErrorResult('Wrong request');
        }

        $user = User::findOne($formData['userId']);
        $group = Group::findOne(['id' => $formData['groupId'], 'active' => Group::STATUS_ACTIVE]);
        $amount = (int)$formData['amount'];

        if (!$user) return self::getJsonErrorResult('Студент не найден');
        if ($amount <= 0) return self::getJsonErrorResult('Сумма не может быть <= 0');
        if (!$group) return self::getJsonErrorResult('Группа не найдена');

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $contract = MoneyComponent::addPupilContract(Company::findOne(Company::COMPANY_EXCLUSIVE_ID), $user, $amount, $group);
            $paymentId = MoneyComponent::payContract($contract, null, Contract::PAYMENT_TYPE_MANUAL, $formData['comment']);

            $transaction->commit();
            return self::getJsonOkResult(['paymentId' => $paymentId, 'userId' => $user->id, 'contractLink' => yii\helpers\Url::to(['contract/print', 'id' => $contract->id])]);
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            return self::getJsonErrorResult($ex->getMessage());
        }
    }

    /**
     * @return Response
     * @throws ForbiddenHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessContract()
    {
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        $contractId = Yii::$app->request->post('contractId');
        if (!$contractId) $jsonData = self::getJsonErrorResult('No contract ID');
        else {
            $contract = Contract::findOne($contractId);
            if (!$contract) $jsonData = self::getJsonErrorResult('Договор не найден');
            else {
                $pupilStartDate = null;
                if (!$contract->activeGroupPupil) {
                    $pupilStartDate = date_create_from_format('d.m.Y', Yii::$app->request->post('contractPupilDateStart', ''));
                }
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $paymentId = MoneyComponent::payContract($contract, $pupilStartDate, Contract::PAYMENT_TYPE_MANUAL);
                    $transaction->commit();
                    $jsonData = self::getJsonOkResult(['paymentId' => $paymentId]);
                } catch (\Throwable $ex) {
                    $transaction->rollBack();
                    $jsonData = self::getJsonErrorResult($ex->getMessage());
                }
            }
        }

        return $this->asJson($jsonData);
    }

    /**
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessGiftCard()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('moneyManagement');

        Yii::$app->response->format = Response::FORMAT_JSON;
        $giftCardId = Yii::$app->request->post('gift_card_id');
        if (!$giftCardId) return self::getJsonErrorResult('No gift card ID');

        $giftCard = GiftCard::findOne($giftCardId);
        if (!$giftCard) return self::getJsonErrorResult('Карта не найдена');
        if ($giftCard->status === GiftCard::STATUS_NEW) return self::getJsonErrorResult('Карта не оплачена!');
        if ($giftCard->status === GiftCard::STATUS_USED) return self::getJsonErrorResult('Карта уже использована!');

        $formData = Yii::$app->request->post();
        $personType = Yii::$app->request->post('person_type', User::ROLE_PARENTS);
        $pupil = null;
        if (isset($formData['pupil']['id'])) {
            $pupil = User::find()->andWhere(['role' => User::ROLE_PUPIL, 'id' => $formData['pupil']['id']])->one();
        }
        if (!$pupil) {
            $pupil = new User(['scenario' => User::SCENARIO_USER]);
            $pupil->role = User::ROLE_PUPIL;
            $pupil->individual = $personType === User::ROLE_PARENTS ? 1 : 0;
            $pupil->load($formData, 'pupil');
            if (!$pupil->save()) return self::getJsonErrorResult($pupil->getErrorsAsString());
        }
        if (!$pupil->parent_id) {
            if ($formData['parents']['name'] && $formData['parents']['phoneFormatted']) {
                $parent = new User(['scenario' => User::SCENARIO_USER]);
                $parent->role = $personType;
                $parent->load($formData, 'parents');
                if (!$parent->save()) return self::getJsonErrorResult($parent->getErrorsAsString());
                $pupil->link('parent', $parent);
            }
        }

        $groupPupil = null;
        if ($formData['group']['existing']) {
            /** @var GroupPupil $groupPupil */
            $groupPupil = GroupPupil::findOne(['group_id' => $formData['group']['existing'], 'active' => GroupPupil::STATUS_ACTIVE, 'user_id' => $pupil->id]);
            if (!$groupPupil) return self::getJsonErrorResult('Группа не найдена');
            $group = $groupPupil->group;
        } else {
            /** @var Group $group */
            $group = Group::findOne(['id' => $formData['group']['id'], 'active' => Group::STATUS_ACTIVE]);
            if (!$group) return self::getJsonErrorResult('Группа не найдена');

            $startDate = date_create_from_format('d.m.Y', $formData['group']['date']);
            if (!$startDate) return self::getJsonErrorResult('Неверная дата начала занятий');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $contract = MoneyComponent::addPupilContract(
                Company::findOne(Company::COMPANY_EXCLUSIVE_ID),
                $pupil,
                $giftCard->amount,
                $group
            );

            $paymentId = MoneyComponent::payContract($contract, $startDate ?? new \DateTime(), Contract::PAYMENT_TYPE_MANUAL);
            $giftCard->status = GiftCard::STATUS_USED;
            $giftCard->used_at = date('Y-m-d H:i:s');
            $giftCard->save();
            $transaction->commit();
            return self::getJsonOkResult([
                'paymentId' => $paymentId,
                'contractLink' => yii\helpers\Url::to(['contract/print', 'id' => $contract->id])
            ]);
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            return self::getJsonErrorResult($exception->getMessage());
        }
    }

    /**
     * Monitor all money debts.
     * @return mixed
     * @throws \Exception
     * @throws Yii\db\Exception
     */
    public function actionDebt()
    {
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');

        $searchModel = new DebtSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $debtors */
        $debtors = User::find()->where(['id' => Debt::find()->select(['user_id'])->distinct()->asArray()->column()])->orderBy(['name' => SORT_ASC])->all();
        $debtorMap = [null => 'Все'];
        foreach ($debtors as $debtor) $debtorMap[$debtor->id] = $debtor->name;

        /** @var Group[] $groups */
        $groups = Group::find()->orderBy('name')->all();
        $groupMap = [null => 'Все'];
        foreach ($groups as $group) $groupMap[$group->id] = $group->name;

        return $this->render('debt', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'debtorMap' => $debtorMap,
            'groupMap' => $groupMap,
            'canCorrect' => Yii::$app->user->can('moneyCorrection'),
        ]);
    }

    public function actionPayment()
    {
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');

        $searchModel = new PaymentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $students */
        $students = User::find()->where(['role' => User::ROLE_PUPIL])->orderBy(['name' => SORT_ASC])->all();
        $studentMap = [null => 'Все'];
        foreach ($students as $student) $studentMap[$student->id] = $student->name;

        /** @var User[] $admins */
        $admins = User::find()->where(['role' => [User::ROLE_ROOT, User::ROLE_MANAGER]])->orderBy(['name' => SORT_ASC])->all();
        $adminMap = [null => 'Все', '-1' => 'Online оплата'];
        foreach ($admins as $admin) $adminMap[$admin->id] = $admin->name;

        /** @var Group[] $groups */
        $groups = Group::find()->orderBy(['active' => SORT_DESC, 'name' => SORT_ASC])->all();
        $groupMap = [null => 'Все'];
        foreach ($groups as $group) $groupMap[$group->id] = $group->name;

        return $this->render('payment', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'studentMap' => $studentMap,
            'adminMap' => $adminMap,
            'groupMap' => $groupMap,
        ]);
    }

    public function actionActions()
    {
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');

        $searchModel = new ActionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $students */
        $students = User::find()->where(['role' => User::ROLE_PUPIL])->orderBy(['name' => SORT_ASC])->all();
        $studentMap = [null => 'Все'];
        foreach ($students as $student) $studentMap[$student->id] = $student->name;

        /** @var User[] $admins */
        $admins = User::find()->where(['role' => [User::ROLE_ROOT, User::ROLE_MANAGER]])->orderBy(['name' => SORT_ASC])->all();
        $adminMap = [null => 'Все'];
        foreach ($admins as $admin) $adminMap[$admin->id] = $admin->name;

        /** @var Group[] $groups */
        $groups = Group::find()->orderBy(['active' => SORT_DESC, 'name' => SORT_ASC])->all();
        $groupMap = [null => 'Все'];
        foreach ($groups as $group) $groupMap[$group->id] = $group->name;

        $typeMap = [null => 'Все'];
        foreach (Action::TYPE_LABELS as $key => $value) $typeMap[$key] = $value;

        return $this->render('actions', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'studentMap' => $studentMap,
            'adminMap' => $adminMap,
            'groupMap' => $groupMap,
            'typeMap' => $typeMap,
        ]);
    }

    /**
     * Monitor teachers' salary.
     * @param int $year
     * @param int $month
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionSalary(int $year = 0, int $month = 0)
    {
        if (!Yii::$app->user->can('viewSalary')) throw new ForbiddenHttpException('Access denied!');

        if (!$year) $year = intval(date('Y'));
        if (!$month) $month = intval(date('n'));

        /** @var GroupParam[] $groupParams */
        $groupParams = GroupParam::find()
            ->andWhere(['year' => $year, 'month' => $month])
            ->andWhere(['>', 'teacher_salary', 0])
            ->with(['teacher', 'group'])
            ->orderBy([GroupParam::tableName() . '.teacher_id' => SORT_ASC])->all();
        $salaryMap = [];
        foreach ($groupParams as $groupParam) {
            if (!array_key_exists($groupParam->teacher_id, $salaryMap)) $salaryMap[$groupParam->teacher_id] = [];
            $salaryMap[$groupParam->teacher_id][] = [
                'teacher' => $groupParam->teacher->name,
                'group_id' => $groupParam->group_id,
                'group' => $groupParam->group->name,
                'amount' => $groupParam->teacher_salary
            ];
        }

        return $this->render('salary', [
            'date' => new \DateTime("$year-$month-01 midnight"),
            'salaryMap' => $salaryMap,
        ]);
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $group
     * @return Response
     * @throws ForbiddenHttpException
     * @throws yii\web\NotFoundHttpException
     */
    public function actionSalaryDetails(int $year, int $month, int $group = 0)
    {
        if (!Yii::$app->user->can('viewSalary')) throw new ForbiddenHttpException('Access denied!');

        $date = new \DateTime("$year-$month-01 midnight");
        if ($group) {
            $group = Group::findOne($group);
            if (!$group) throw new yii\web\NotFoundHttpException('Group not found');
            try {
                $spreadsheet = SalaryComponent::getGroupSalarySpreadsheet($group, $date);
            } catch (\Throwable $exception) {
                throw new yii\web\NotFoundHttpException($exception->getMessage(), $exception->getCode());
            }
        } else {
            try {
                $spreadsheet = SalaryComponent::getMonthSalarySpreadsheet($date);
            } catch (\Throwable $exception) {
                throw new yii\web\NotFoundHttpException($exception->getMessage(), $exception->getCode());
            }
        }

        ob_start();
        $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->save('php://output');
        return Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            ($group ? $group->name . ' ' : '') . "$month-$year.xlsx",
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * @param int $userId
     * @param int $groupId
     * @return Response
     * @throws yii\web\BadRequestHttpException
     */
    public function actionPupilReport(int $userId, int $groupId)
    {
        $pupil = User::findOne($userId);
        $group = Group::findOne($groupId);

        if (!$pupil || $pupil->role != User::ROLE_PUPIL) throw new yii\web\BadRequestHttpException('Pupil not found');
        if (!$group) throw new yii\web\BadRequestHttpException('Group not found');

        $groupPupil = GroupPupil::find()->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id, 'active' => GroupPupil::STATUS_ACTIVE])->one();
        if (!$groupPupil) throw new yii\web\BadRequestHttpException('Wrong pupil and group selection');

        ob_start();
        $objWriter = IOFactory::createWriter(PupilReport::create($pupil, $group), 'Xlsx');
        $objWriter->save('php://output');
        return Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            "$pupil->name $group->name.xlsx",
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function actionCorrection(int $userId, int $groupId)
    {
        if (!Yii::$app->user->can('moneyCorrection')) throw new ForbiddenHttpException('Access denied!');

        $pupil = User::findOne($userId);
        $group = Group::findOne($groupId);

        if (!$pupil || $pupil->role != User::ROLE_PUPIL) throw new yii\web\BadRequestHttpException('Pupil not found');
        if (!$group) throw new yii\web\BadRequestHttpException('Group not found');

        $groupPupil = GroupPupil::find()->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id])->one();
        if (!$groupPupil) throw new yii\web\BadRequestHttpException('Wrong pupil and group selection');

        if (Yii::$app->request->isPost) {
            $paymentSum = Yii::$app->request->post('payment_sum', 0);
            if ($paymentSum > 0) {
                $payment = new Payment();
                $payment->user_id = $pupil->id;
                $payment->group_id = $group->id;
                $payment->admin_id = Yii::$app->user->getId();
                $payment->amount = $paymentSum;
                $payment->created_at = date('Y-m-d H:i:s');
                $payment->comment = 'Ручная корректировка долга';
                $payment->cash_received = Yii::$app->request->post('cash_received', 0) ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE;

                MoneyComponent::registerIncome($payment);
            }
        }

        return $this->render('correction', [
            'pupil' => $pupil,
            'group' => $group,
            'debt' => Debt::findOne(['user_id' => $pupil->id, 'group_id' => $group->id]),
        ]);
    }
}
