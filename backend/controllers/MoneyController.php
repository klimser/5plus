<?php

namespace backend\controllers;

use backend\components\GroupComponent;
use backend\components\MoneyComponent;
use backend\models\ActionSearch;
use backend\models\Contract;
use backend\models\Event;
use backend\models\EventMember;
use backend\models\GroupParam;
use backend\models\GroupPupil;
use common\components\Action;
use backend\models\Debt;
use backend\models\DebtSearch;
use backend\models\Group;
use backend\models\Payment;
use backend\models\PaymentSearch;
use backend\models\User;
use common\components\helpers\Calendar;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use yii;
use yii\web\ForbiddenHttpException;

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

        $params = [];
        $userId = Yii::$app->request->get('user');
        if ($userId) {
            $user = User::findOne($userId);
            if ($user) $params['user'] = $user;
        }
        return $this->render('income', $params);
    }

    /**
     * @return yii\web\Response
     * @throws ForbiddenHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessIncome()
    {
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        $userId = Yii::$app->request->post('user');
        $groupId = Yii::$app->request->post('group');
        $amount = intval(Yii::$app->request->post('amount', 0));
        $discount = Yii::$app->request->post('discount');
        $comment = Yii::$app->request->post('comment');
        $paymentDate = date_create_from_format('d.m.Y', Yii::$app->request->post('date'));
        $contractNum = Yii::$app->request->post('contract');

        if (!$userId || !$groupId || !$amount || !$contractNum || !$paymentDate) $jsonData = self::getJsonErrorResult('Wrong request');
        else {
            $user = User::findOne($userId);
            $group = Group::findOne(['id' => $groupId, 'active' => Group::STATUS_ACTIVE]);

            if (!$user) $jsonData = self::getJsonErrorResult('Студент не найден');
            elseif ($amount <= 0) $jsonData = self::getJsonErrorResult('Сумма не может быть <= 0');
            elseif (!$paymentDate) $jsonData = self::getJsonErrorResult('Указана некорректная дата платежа');
            elseif (!$group) $jsonData = self::getJsonErrorResult('Группа не найдена');
            else {
                $payment = new Payment();
                $payment->admin_id = Yii::$app->user->id;
                $payment->user_id = $user->id;
                $payment->group_id = $group->id;
                $payment->amount = $amount;
                $payment->discount = $discount ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE;
                $payment->comment = $comment ?: null;
                $payment->created_at = $paymentDate->format('Y-m-d H:i:s');

                $contract = new Contract();
                $contract->number = $contractNum;
                $contract->user_id = $user->id;
                $contract->group_id = $group->id;
                $contract->amount = $amount;
                $contract->discount = $discount ? Contract::STATUS_ACTIVE : Contract::STATUS_INACTIVE;
                $contract->status = Contract::STATUS_PAID;
                $contract->payment_type = Contract::PAYMENT_TYPE_MANUAL;
                $contract->created_at = $payment->created_at;
                $contract->created_admin_id = Yii::$app->user->id;
                $contract->paid_at = $payment->created_at;
                $contract->paid_admin_id = Yii::$app->user->id;

                try {
                    if (!$contract->save()) throw new \Exception('Contract save error: ' . $contract->getErrorsAsString());
                    $contract->link('payments', $payment);
                    $paymentId = MoneyComponent::registerIncome($payment);
                    MoneyComponent::setUserChargeDates($user, $group);
                    $jsonData = self::getJsonOkResult(['paymentId' => $paymentId, 'contractLink' => yii\helpers\Url::to(['contract/print', 'id' => $contract->id])]);
                } catch (\Throwable $ex) {
                    $jsonData = self::getJsonErrorResult($ex->getMessage());
                }
            }
        }

        return $this->asJson($jsonData);
    }

    /**
     * @return yii\web\Response
     * @throws ForbiddenHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessContract()
    {
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        $contractId = Yii::$app->request->post('id');
        $contractPaidDate = date_create_from_format('d.m.Y', Yii::$app->request->post('contract_paid', ''));
        if (!$contractId) $jsonData = self::getJsonErrorResult('No contract ID');
        elseif (!$contractPaidDate) $jsonData =self::getJsonErrorResult('Неправильная дата оплаты');
        else {
            $contract = Contract::findOne($contractId);
            if (!$contract) $jsonData = self::getJsonErrorResult('Договор не найден');
            elseif ($contract->status != Contract::STATUS_NEW) $jsonData = self::getJsonErrorResult('Договор уже оплачен!');
            else {
                $payment = new Payment();
                $payment->admin_id = Yii::$app->user->id;
                $payment->user_id = $contract->user_id;
                $payment->group_id = $contract->group_id;
                $payment->amount = $contract->amount;
                $payment->discount = $contract->discount;
                $payment->contract_id = $contract->id;
                $payment->created_at = $contractPaidDate->format('Y-m-d H:i:s');

                $contract->status = Contract::STATUS_PAID;
                $contract->payment_type = Contract::PAYMENT_TYPE_MANUAL;
                $contract->paid_admin_id = Yii::$app->user->id;
                $contract->paid_at = $contractPaidDate->format('Y-m-d H:i:s');

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if (!$contract->save()) throw new \Exception('Contract save error: ' . $contract->getErrorsAsString());

                    $groupPupil = GroupPupil::find()
                        ->andWhere(['user_id' => $contract->user_id, 'group_id' => $contract->group_id, 'active' => GroupPupil::STATUS_ACTIVE])
                        ->one();
                    if (!$groupPupil) {
                        GroupComponent::addPupilToGroup($contract->user, $contract->group, $contractPaidDate);
                    }

                    $paymentId = MoneyComponent::registerIncome($payment);
                    \Yii::$app->actionLogger->log(
                        $contract->user,
                        Action::TYPE_CONTRACT_PAID,
                        $contract->amount,
                        $contract->group
                    );
                    MoneyComponent::setUserChargeDates($contract->user, $contract->group);
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
     * Monitor all money debts.
     * @return mixed
     * @throws \Exception
     * @throws \yii\db\Exception
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

        return $this->render('debt', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'debtorMap' => $debtorMap,
            'groups' => Group::find()->all(),
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
        $adminMap = [null => 'Все'];
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
        foreach (Action::$typeLabels as $key => $value) $typeMap[$key] = $value;

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
            ->orderBy([GroupParam::tableName() . '.id' => SORT_ASC])->all();
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
     * @param int $group
     * @param int $year
     * @param int $month
     * @return yii\web\Response
     * @throws ForbiddenHttpException
     * @throws yii\web\NotFoundHttpException
     */
    public function actionSalaryDetails(int $group, int $year, int $month)
    {
        if (!Yii::$app->user->can('viewSalary')) throw new ForbiddenHttpException('Access denied!');

        $group = Group::findOne($group);
        if (!$group) throw new yii\web\NotFoundHttpException('Group not found');
        $date = new \DateTime("$year-$month-01 midnight");
        $groupParam = GroupParam::findByDate($group, $date);
        if (!$groupParam) throw new yii\web\NotFoundHttpException('There is no salary for this month');

        $daysCount = intval($date->format('t'));
        $lastColumn = \common\components\helpers\Spreadsheet::getColumnByNumber($daysCount + 2);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $spreadsheet->getActiveSheet()->setTitle($date->format('m-Y') . ' ' . mb_substr($group->name, 0, 22));

        $spreadsheet->getActiveSheet()->mergeCells("A1:{$lastColumn}1");
        $spreadsheet->getActiveSheet()->setCellValue('A1', "$group->name - " . Calendar::$monthNames[$month] . " $year");
        $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setSize(22);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

        $spreadsheet->getActiveSheet()->setCellValue('A3', 'Преподаватель');
        $spreadsheet->getActiveSheet()->setCellValue('A4', 'Стоимость занятия');
        $spreadsheet->getActiveSheet()->setCellValue('A5', 'Стоимость со скидкой');

        $spreadsheet->getActiveSheet()->mergeCells("B3:{$lastColumn}3");
        $spreadsheet->getActiveSheet()->mergeCells("B4:{$lastColumn}4");
        $spreadsheet->getActiveSheet()->mergeCells("B5:{$lastColumn}5");
        $spreadsheet->getActiveSheet()->setCellValue('B3', $groupParam->teacher->name);
        $spreadsheet->getActiveSheet()->setCellValue('B4', $groupParam->lesson_price);
        $spreadsheet->getActiveSheet()->setCellValue('B5', $groupParam->lesson_price_discount);
        $spreadsheet->getActiveSheet()->getStyle('B3:B5')->getFont()->setBold(true);

        $nextMonth = clone $date;
        $nextMonth->add(new \DateInterval('P1M'));
        /** @var Event[] $events */
        $events = Event::find()
            ->andWhere(['group_id' => $group->id])
            ->andWhere(['BETWEEN', 'event_date', $date->format('Y-m-d H:i:s'), $nextMonth->format('Y-m-d H:i:s')])
            ->with('members.payments')
            ->all();

        if (!$events) {
            $spreadsheet->getActiveSheet()->mergeCells("A7:{$lastColumn}7");
            $spreadsheet->getActiveSheet()->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->setCellValue('A7', 'В группе не было занятий');
        } else {
            $groupPupilMap = [];
            $userMap = [];
            $userChargeMap = [];
            /** @var GroupPupil[] $groupPupils */
            $groupPupils = GroupPupil::find()
                ->andWhere(['group_id' => $group->id])
                ->andWhere(['<', 'date_start', $nextMonth->format('Y-m-d')])
                ->andWhere(['or', 'date_end IS NULL', ['>=', 'date_end', $date->format('Y-m-d')]])
                ->joinWith('user')
                ->orderBy(['{{%user}}.name' => SORT_ASC])
                ->all();
            $row = 8;
            foreach ($groupPupils as $groupPupil) {
                if (!array_key_exists($groupPupil->user_id, $userMap)) {
                    $spreadsheet->getActiveSheet()->setCellValue("A$row", $groupPupil->user->name);
                    $userMap[$groupPupil->user_id] = $row;
                    $groupPupilMap[$groupPupil->id] = $row;
                    $row++;
                } else {
                    $groupPupilMap[$groupPupil->id] = $userMap[$groupPupil->user_id];
                }
                $userChargeMap[$groupPupil->user_id] = 0;
            }

            for ($i = 1; $i <= $daysCount; $i++) {
                $spreadsheet->getActiveSheet()->setCellValue(\common\components\helpers\Spreadsheet::getColumnByNumber($i + 1) . '7', $i);
            }
            $spreadsheet->getActiveSheet()->setCellValue("{$lastColumn}7", 'Итого');
            $spreadsheet->getActiveSheet()->getStyle("B7:{$lastColumn}7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle("B7:{$lastColumn}7")->getFont()->setBold(true);

            $spreadsheet->getActiveSheet()->setCellValue("A$row", 'Зарплата преподавателю');
            $redColor = 'f2dede';
            foreach ($events as $event) {
                $column = \common\components\helpers\Spreadsheet::getColumnByNumber(intval($event->eventDateTime->format('j')) + 1);

                if ($event->status == Event::STATUS_CANCELED) {
                    $spreadsheet->getActiveSheet()->getStyle("{$column}7")
                        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($redColor);
                } else {
                    $totalSum = 0;
                    foreach ($event->members as $member) {
                        if ($member->payments) {
                            $paymentSum = 0;
                            foreach ($member->payments as $payment) {
                                $paymentSum += $payment->amount;
                                $userChargeMap[$payment->user_id] += $payment->amount;
                                $totalSum += $payment->amount;
                            }
                            $spreadsheet->getActiveSheet()->setCellValue($column . $groupPupilMap[$member->group_pupil_id], $paymentSum * -1);
                        }

                        if ($member->status == EventMember::STATUS_MISS) {
                            $spreadsheet->getActiveSheet()->getStyle($column . $groupPupilMap[$member->group_pupil_id])
                                ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setRGB($redColor);
                        }
                    }

                    $spreadsheet->getActiveSheet()->setCellValue($column . $row, round($totalSum * (-1) / 100 * $groupParam->teacher_rate));
                }
            }

            foreach ($userChargeMap as $userId => $chargeAmount) {
                $spreadsheet->getActiveSheet()->setCellValue($lastColumn . $userMap[$userId], $chargeAmount * (-1));
            }
            $spreadsheet->getActiveSheet()->setCellValue($lastColumn . $row, $groupParam->teacher_salary);
            $spreadsheet->getActiveSheet()->getStyle("A$row:$lastColumn$row")->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->getStyle("A7:$lastColumn$row")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
        }

        for ($i = 1; $i < $daysCount + 2; $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimension(\common\components\helpers\Spreadsheet::getColumnByNumber($i))->setAutoSize(true);
        }

        $response = new yii\web\Response();
        ob_start();
        $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->save('php://output');
        $response->sendContentAsFile(
            ob_get_clean(),
            "$group->name $month-$year.xlsx",
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        return $response;
    }
}