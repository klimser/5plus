<?php

namespace backend\controllers;

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
     * @return Response
     * @throws ForbiddenHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessIncome()
    {
        if (!Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        $userId = Yii::$app->request->post('user');
        $groupId = Yii::$app->request->post('group');
        $companyId = Yii::$app->request->post('company');
        $amount = intval(Yii::$app->request->post('amount', 0));
        $comment = Yii::$app->request->post('comment', '');
        $contractNum = Yii::$app->request->post('contract');

        if (!$userId || !$groupId || !$companyId || !$amount || !$contractNum) $jsonData = self::getJsonErrorResult('Wrong request');
        else {
            $user = User::findOne($userId);
            $group = Group::findOne(['id' => $groupId, 'active' => Group::STATUS_ACTIVE]);
            $company = Company::findOne($companyId);

            if (!$user) $jsonData = self::getJsonErrorResult('Студент не найден');
            elseif ($amount <= 0) $jsonData = self::getJsonErrorResult('Сумма не может быть <= 0');
            elseif (!$group) $jsonData = self::getJsonErrorResult('Группа не найдена');
            elseif (!$company) $jsonData = self::getJsonErrorResult('Компания не выбрана');
            else {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    $contract = MoneyComponent::addPupilContract($company, $user, $amount, $contractNum != 'auto' ? $contractNum : null, $group);
                    $paymentId = MoneyComponent::payContract($contract, null, Contract::PAYMENT_TYPE_MANUAL, $comment);

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
     * @return Response
     * @throws ForbiddenHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessContract()
    {
        if (!\Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');
        if (!\Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        $contractId = \Yii::$app->request->post('id');
        if (!$contractId) $jsonData = self::getJsonErrorResult('No contract ID');
        else {
            $contract = Contract::findOne($contractId);
            if (!$contract) $jsonData = self::getJsonErrorResult('Договор не найден');
            else {
                /** @var GroupPupil $groupPupil */
                $groupPupil = GroupPupil::find()->andWhere(['user_id' => $contract->user_id, 'group_id' => $contract->group_id, 'active' => GroupPupil::STATUS_ACTIVE])->one();
                $pupilStartDate = null;
                if (!$groupPupil) {
                    $pupilStartDate = date_create_from_format('d.m.Y', \Yii::$app->request->post('pupil_start_date', ''));
                }
                $transaction = \Yii::$app->db->beginTransaction();
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
        if (!\Yii::$app->user->can('moneyManagement')) throw new ForbiddenHttpException('Access denied!');
        if (!\Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        \Yii::$app->response->format = Response::FORMAT_JSON;
        $giftCardId = \Yii::$app->request->post('gift_card_id');
        if (!$giftCardId) return self::getJsonErrorResult('No gift card ID');
        else {
            $giftCard = GiftCard::findOne($giftCardId);
            if (!$giftCard) return self::getJsonErrorResult('Карта не найдена');
            elseif ($giftCard->status == GiftCard::STATUS_NEW) return self::getJsonErrorResult('Карта не оплачена!');
            elseif ($giftCard->status == GiftCard::STATUS_USED) return self::getJsonErrorResult('Карта уже использована!');
            else {
                $formData = \Yii::$app->request->post();
                $pupil = null;
                if (isset($formData['pupil']['id'])) {
                    $pupil = User::find()->andWhere(['role' => User::ROLE_PUPIL, 'id' => $formData['pupil']['id']])->one();
                }
                if (!$pupil) {
                    $pupil = new User();
                    $pupil->role = User::ROLE_PUPIL;
                    $pupil->load($formData, 'pupil');
                    if (!$pupil->save()) return self::getJsonErrorResult($pupil->getErrorsAsString());
                }
                if (!$pupil->parent_id) {
                    if ($formData['parents']['name'] && $formData['parents']['phoneFormatted']) {
                        $parent = new User();
                        $parent->role = User::ROLE_PARENTS;
                        $parent->load($formData, 'parents');
                        if (!$parent->save()) return self::getJsonErrorResult($parent->getErrorsAsString());
                        $pupil->link('parent', $parent);
                    }
                }

                $groupPupil = null;
                if ($formData['group']['existing']) {
                    /** @var GroupPupil $groupPupil */
                    $groupPupil = GroupPupil::findOne(['id' => $formData['group']['existing'], 'active' => GroupPupil::STATUS_ACTIVE, 'user_id' => $pupil->id]);
                }
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    $startDate = date_create_from_format('d.m.Y', $formData['group']['date']);
                    if (!$startDate) throw new \Exception('Неверная дата начала занятий');
                    if (!$groupPupil) {
                        /** @var Group $group */
                        $group = Group::find()->andWhere(['id' => $formData['group']['id'], 'active' => Group::STATUS_ACTIVE])->one();
                        if (!$group) throw new \Exception('Группа не найдена');
                    } else {
                        $group = $groupPupil->group;
                    }

                    $contract = MoneyComponent::addPupilContract(
                        Company::findOne(Company::COMPANY_SUPER_ID),
                        $pupil,
                        $giftCard->amount,
                        null,
                        $group
                    );

                    $paymentId = MoneyComponent::payContract($contract, $startDate, Contract::PAYMENT_TYPE_MANUAL);
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
        }
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
        return \Yii::$app->response->sendContentAsFile(
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

    public function actionCorrection(int $userId, int $groupId)
    {
        if (!Yii::$app->user->can('moneyCorrection')) throw new ForbiddenHttpException('Access denied!');

        $pupil = User::findOne($userId);
        $group = Group::findOne($groupId);

        if (!$pupil || $pupil->role != User::ROLE_PUPIL) throw new yii\web\BadRequestHttpException('Pupil not found');
        if (!$group) throw new yii\web\BadRequestHttpException('Group not found');

        $groupPupil = GroupPupil::find()->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id])->one();
        if (!$groupPupil) throw new yii\web\BadRequestHttpException('Wrong pupil and group selection');

        if (\Yii::$app->request->isPost) {
            $paymentSum = \Yii::$app->request->post('payment_sum', 0);
            if ($paymentSum > 0) {
                $payment = new Payment();
                $payment->user_id = $pupil->id;
                $payment->group_id = $group->id;
                $payment->admin_id = \Yii::$app->user->getId();
                $payment->amount = $paymentSum;
                $payment->created_at = date('Y-m-d H:i:s');
                $payment->comment = 'Ручная корректировка долга';
                $payment->cash_received = \Yii::$app->request->post('cash_received', 0) ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE;

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