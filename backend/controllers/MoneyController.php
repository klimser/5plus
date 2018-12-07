<?php

namespace backend\controllers;

use common\components\MoneyComponent;
use backend\models\ActionSearch;
use common\models\Contract;
use backend\models\Event;
use backend\models\EventMember;
use common\models\GroupParam;
use common\models\GroupPupil;
use common\components\Action;
use common\models\Debt;
use common\models\DebtSearch;
use common\models\Group;
use common\models\Payment;
use common\models\PaymentSearch;
use common\models\User;
use common\components\helpers\Calendar;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
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
        $comment = Yii::$app->request->post('comment', '');
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
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    $contract = MoneyComponent::addPupilContract(
                        $user,
                        $amount,
                        $discount > 0,
                        $contractNum != 'auto' ? $contractNum : null,
                        null,
                        $group
                    );

                    $paymentId = MoneyComponent::payContract(
                        $contract,
                        $paymentDate,
                        Contract::PAYMENT_TYPE_MANUAL,
                        $comment
                    );

                    $transaction->commit();
                    $jsonData = self::getJsonOkResult(['paymentId' => $paymentId, 'contractLink' => yii\helpers\Url::to(['contract/print', 'id' => $contract->id])]);
                } catch (\Throwable $ex) {
                    $transaction->rollBack();
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
            else {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $paymentId = MoneyComponent::payContract($contract, $contractPaidDate, Contract::PAYMENT_TYPE_MANUAL);
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
        $spreadsheet->getActiveSheet()->setCellValueExplicit('B4', $groupParam->lesson_price, DataType::TYPE_NUMERIC);
        $spreadsheet->getActiveSheet()->setCellValueExplicit('B5', $groupParam->lesson_price_discount, DataType::TYPE_NUMERIC);
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
                ->orderBy([User::tableName() . '.name' => SORT_ASC])
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
                $spreadsheet->getActiveSheet()
                    ->setCellValueExplicit(\common\components\helpers\Spreadsheet::getColumnByNumber($i + 1) . '7', $i, DataType::TYPE_NUMERIC);
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
                        ->getFill()->setFillType(Fill::FILL_SOLID)
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
                            $spreadsheet->getActiveSheet()
                                ->setCellValueExplicit($column . $groupPupilMap[$member->group_pupil_id], $paymentSum * -1, DataType::TYPE_NUMERIC);
                        }

                        if ($member->status == EventMember::STATUS_MISS) {
                            $spreadsheet->getActiveSheet()->getStyle($column . $groupPupilMap[$member->group_pupil_id])
                                ->getFill()->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB($redColor);
                        }
                    }

                    $spreadsheet->getActiveSheet()
                        ->setCellValueExplicit($column . $row, round($totalSum * (-1) / 100 * $groupParam->teacher_rate), DataType::TYPE_NUMERIC);
                }
            }

            foreach ($userChargeMap as $userId => $chargeAmount) {
                $spreadsheet->getActiveSheet()->setCellValueExplicit($lastColumn . $userMap[$userId], $chargeAmount * (-1), DataType::TYPE_NUMERIC);
            }
            $spreadsheet->getActiveSheet()->setCellValueExplicit($lastColumn . $row, $groupParam->teacher_salary, DataType::TYPE_NUMERIC);
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

        ob_start();
        $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->save('php://output');
        return \Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            "$group->name $month-$year.xlsx",
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * @param int $userId
     * @param int $groupId
     * @return yii\web\Response
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

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $greenColor = '9FF298';
        $spreadsheet->getActiveSheet()->mergeCells('A1:H1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', $pupil->name);
        $spreadsheet->getActiveSheet()->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle("A1")->getFont()->setBold(true)->setSize(16);

        $spreadsheet->getActiveSheet()->setCellValue('H3', date('d.m.Y'));
        $spreadsheet->getActiveSheet()->mergeCells('A4:H4');
        $spreadsheet->getActiveSheet()->setCellValue('A4', 'Табель оплат');
        $spreadsheet->getActiveSheet()->getStyle("A4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $spreadsheet->getActiveSheet()->setCellValue('A5', '№');
        $spreadsheet->getActiveSheet()->setCellValue('B5', 'Предмет');
        $spreadsheet->getActiveSheet()->setCellValue('C5', 'Договор №');
        $spreadsheet->getActiveSheet()->setCellValue('D5', 'Дата');
        $spreadsheet->getActiveSheet()->setCellValue('E5', 'Сумма');
        $spreadsheet->getActiveSheet()->setCellValue('F5', 'С');
        $spreadsheet->getActiveSheet()->setCellValue('G5', 'По');
        $spreadsheet->getActiveSheet()->setCellValue('H5', 'Остаток');

        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);

        $row = 6;
        /** @var Payment[] $payments */
        $payments = Payment::find()
            ->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id])
            ->andWhere(['>', 'amount', 0])
            ->orderBy(['created_at' => SORT_ASC])
            ->with('payments')
            ->all();

        $num = 1;
        foreach ($payments as $payment) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A$row", $num, DataType::TYPE_NUMERIC);
            $spreadsheet->getActiveSheet()->setCellValue("B$row", $payment->group->name);
            if ($payment->contract) {
                $spreadsheet->getActiveSheet()->setCellValueExplicit("C$row", $payment->contract->number, DataType::TYPE_STRING);
            }
            $spreadsheet->getActiveSheet()->setCellValue("D$row", Date::PHPToExcel($payment->createDate));
            $spreadsheet->getActiveSheet()->setCellValueExplicit("E$row", $payment->amount, DataType::TYPE_NUMERIC);

            if ($payment->discount == Payment::STATUS_ACTIVE) {
                $spreadsheet->getActiveSheet()->getStyle("E$row")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($greenColor);
            }

            $minDate = $maxDate = null;
            foreach ($payment->payments as $childPayment) {
                if (!$minDate || $childPayment->createDate < $minDate) $minDate = $childPayment->createDate;
                if (!$maxDate || $childPayment->createDate > $maxDate) $maxDate = $childPayment->createDate;
            }
            if ($minDate) {
                $spreadsheet->getActiveSheet()->setCellValue("F$row", Date::PHPToExcel($minDate));
            }
            if ($maxDate && $payment->amount == $payment->paymentsSum) {
                $spreadsheet->getActiveSheet()->setCellValue("G$row", Date::PHPToExcel($maxDate));
            }
            $spreadsheet->getActiveSheet()->setCellValueExplicit("H$row", $payment->amount - $payment->paymentsSum, DataType::TYPE_NUMERIC);

            $num++;
            $row++;
        }
        $row--;

        $spreadsheet->getActiveSheet()->getStyle("A4:H$row")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $spreadsheet->getActiveSheet()->getStyle("A4:H5")->getFont()->setItalic(true);
        $spreadsheet->getActiveSheet()->getStyle("D6:D$row")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DMYMINUS);
        $spreadsheet->getActiveSheet()->getStyle("F6:G$row")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DMYMINUS);

        ob_start();
        $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->save('php://output');
        return \Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            "$pupil->name $group->name.xlsx",
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}