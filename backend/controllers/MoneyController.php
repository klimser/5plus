<?php

namespace backend\controllers;

use backend\components\report\StudentReport;
use backend\components\SalaryComponent;
use backend\models\Event;
use common\components\CourseComponent;
use common\components\MoneyComponent;
use backend\models\ActionSearch;
use common\models\Company;
use common\models\Contract;
use common\models\CourseConfig;
use common\models\GiftCard;
use common\models\CourseStudent;
use common\components\Action;
use common\models\Debt;
use common\models\DebtSearch;
use common\models\Course;
use common\models\Payment;
use common\models\PaymentSearch;
use common\models\User;
use DateTimeImmutable;
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
        if (!isset($formData['userId'], $formData['courseId'], $formData['amount'], $formData['payment_type'], $formData['comment'])) {
            return self::getJsonErrorResult('Wrong request');
        }

        $user = User::findOne($formData['userId']);
        $course = Course::findOne(['id' => $formData['courseId'], 'active' => Course::STATUS_ACTIVE]);
        $amount = (int)$formData['amount'];
        $paymentType = $formData['payment_type'];

        if (!$user) return self::getJsonErrorResult('Студент не найден');
        if ($amount <= 0) return self::getJsonErrorResult('Сумма не может быть <= 0');
        if (!$course) return self::getJsonErrorResult('Группа не найдена');
        if (!in_array($paymentType, Contract::MANUAL_PAYMENT_TYPES)) return self::getJsonErrorResult('Неверный метод оплаты');

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $contract = MoneyComponent::addStudentContract(Company::findOne(Company::COMPANY_EXCLUSIVE_ID), $user, $amount, $course);
            $paymentId = MoneyComponent::payContract($contract, null, $paymentType, $formData['comment']);

            $transaction->commit();
            return self::getJsonOkResult(['paymentId' => $paymentId, 'userId' => $user->id, 'contractLink' => yii\helpers\Url::to(['contract/print', 'id' => $contract->id])]);
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            return self::getJsonErrorResult($ex->getMessage());
        }
    }

    /**
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessContract()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('moneyManagement');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $contractId = Yii::$app->request->post('contractId');
        if (!$contractId) return self::getJsonErrorResult('No contract ID');
        
        $contract = Contract::findOne($contractId);
        if (!$contract) return self::getJsonErrorResult('Договор не найден');
        
        $studentStartDate = null;
        if (!$contract->activeCourseStudent) {
            $studentStartDate = new DateTimeImmutable(Yii::$app->request->post('contractStudentDateStart', 'now'));
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $paymentId = MoneyComponent::payContract($contract, $studentStartDate, Contract::PAYMENT_TYPE_MANUAL);
            $transaction->commit();
            return self::getJsonOkResult(['paymentId' => $paymentId]);
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            return self::getJsonErrorResult($ex->getMessage());
        }
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
        $student = null;
        if (isset($formData['student']['id'])) {
            $student = User::findOne(['role' => User::ROLE_STUDENT, 'id' => $formData['student']['id']]);
        }
        if (!$student) {
            $student = new User(['scenario' => User::SCENARIO_USER]);
            $student->role = User::ROLE_STUDENT;
            $student->individual = $personType === User::ROLE_PARENTS ? 1 : 0;
            $student->load($formData, 'student');
            if (!$student->save()) return self::getJsonErrorResult($student->getErrorsAsString());
        }
        if (!$student->parent_id) {
            if ($formData['parents']['name'] && $formData['parents']['phoneFormatted']) {
                $parent = new User(['scenario' => User::SCENARIO_USER]);
                $parent->role = $personType;
                $parent->load($formData, 'parents');
                if (!$parent->save()) return self::getJsonErrorResult($parent->getErrorsAsString());
                $student->link('parent', $parent);
            }
        }

        $courseStudent = null;
        if ($formData['course']['existing']) {
            /** @var CourseStudent $courseStudent */
            $courseStudent = CourseStudent::findOne(['course_id' => $formData['course']['existing'], 'active' => CourseStudent::STATUS_ACTIVE, 'user_id' => $student->id]);
            if (!$courseStudent) return self::getJsonErrorResult('Группа не найдена');
            $course = $courseStudent->course;
        } else {
            /** @var Course $course */
            $course = Course::findOne(['id' => $formData['course']['id'], 'active' => Course::STATUS_ACTIVE]);
            if (!$course) return self::getJsonErrorResult('Группа не найдена');

            $startDate = new DateTimeImmutable($formData['course']['date']);
            if (!$startDate) return self::getJsonErrorResult('Неверная дата начала занятий');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $contract = MoneyComponent::addStudentContract(
                Company::findOne(Company::COMPANY_EXCLUSIVE_ID),
                $student,
                $giftCard->amount,
                $course
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
        $this->checkAccess('moneyManagement');

        $searchModel = new DebtSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $debtors */
        $debtors = User::find()->where(['id' => Debt::find()->select(['user_id'])->distinct()->asArray()->column()])->orderBy(['name' => SORT_ASC])->all();
        $debtorMap = [null => 'Все'];
        foreach ($debtors as $debtor) $debtorMap[$debtor->id] = $debtor->name;

        /** @var Course[] $courses */
        $courses = Course::find()->all();
        $courseMap = [null => 'Все'];
        foreach ($courses as $course) $courseMap[$course->id] = $course->courseConfig->name;

        return $this->render('debt', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'debtorMap' => $debtorMap,
            'courseMap' => $courseMap,
            'canCorrect' => Yii::$app->user->can('moneyCorrection'),
        ]);
    }

    public function actionPayment()
    {
        $this->checkAccess('moneyManagement');

        $searchModel = new PaymentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $admins */
        $admins = User::find()->where(['role' => [User::ROLE_ROOT, User::ROLE_MANAGER]])->orderBy(['name' => SORT_ASC])->all();
        $adminMap = [null => 'Все', '-1' => 'Online оплата'];
        foreach ($admins as $admin) $adminMap[$admin->id] = $admin->name;

        /** @var Course[] $courses */
        $courses = Course::find()->orderBy(['active' => SORT_DESC, 'name' => SORT_ASC])->all();
        $courseMap = [null => 'Все'];
        foreach ($courses as $course) $courseMap[$course->id] = $course->courseConfig->name;

        return $this->render('payment', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'adminMap' => $adminMap,
            'courseMap' => $courseMap,
        ]);
    }

    public function actionActions()
    {
        $this->checkAccess('moneyManagement');

        $searchModel = new ActionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $admins */
        $admins = User::find()->where(['role' => [User::ROLE_ROOT, User::ROLE_MANAGER]])->orderBy(['name' => SORT_ASC])->all();
        $adminMap = [null => 'Все'];
        foreach ($admins as $admin) $adminMap[$admin->id] = $admin->name;

        /** @var Course[] $courses */
        $courses = Course::find()->orderBy(['active' => SORT_DESC, 'name' => SORT_ASC])->all();
        $courseMap = [null => 'Все'];
        foreach ($courses as $course) $courseMap[$course->id] = $course->courseConfig->name;

        $typeMap = [null => 'Все'];
        foreach (Action::TYPE_LABELS as $key => $value) $typeMap[$key] = $value;

        return $this->render('actions', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'adminMap' => $adminMap,
            'courseMap' => $courseMap,
            'typeMap' => $typeMap,
        ]);
    }

    /**
     * Monitor teachers' salary.
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionSalary(int $year = 0, int $month = 0)
    {
        $this->checkAccess('viewSalary');

        if (!$year) $year = intval(date('Y'));
        if (!$month) $month = intval(date('n'));

        $dateFrom = new DateTimeImmutable("$year-$month-01 midnight");
        $dateTo = $dateFrom->modify('+1 month');

        /** @var CourseConfig[] $courseConfigs */
        $courseConfigs = CourseConfig::find()
            ->alias('cc')
            ->andWhere(['<', 'date_from', $dateTo->format('Y-m-d H:i:s')])
            ->andWhere(['or', ['date_to' => null], ['>', 'date_to', $dateFrom->format('Y-m-d H:i:s')]])
            ->with(['teacher', 'course'])
            ->orderBy(['cc.teacher_id' => SORT_ASC])->all();
        $salaryMap = [];
        foreach ($courseConfigs as $courseConfig) {
            if (!array_key_exists($courseConfig->course_id, $salaryMap[$courseConfig->teacher_id])) {
                $salaryMap[$courseConfig->teacher_id][$courseConfig->course_id] = [
                    'teacher' => $courseConfig->teacher->name,
                    'course' => $courseConfig->name,
                    'amount' => 0
                ];
            }
            /** @var DateTimeImmutable $eventDateFrom */
            $eventDateFrom = max($dateFrom, $courseConfig->dateFromObject);
            /** @var DateTimeImmutable $eventDateTo */
            $eventDateTo = (null === $courseConfig->dateToObject) ? $dateTo : min($dateTo, $courseConfig->dateToObject);

            if (null !== $courseConfig->teacher_rate) {
                $paymentSum = Payment::find()
                    ->andWhere(['between', 'created_at', $eventDateFrom->format('Y-m-d H:i:s'), $eventDateTo->format('Y-m-d H:i:s')])
                    ->andWhere(['course_id' => $courseConfig->course_id])
                    ->andWhere('amount < 0')
                    ->andWhere('used_payment_id IS NOT NULL')
                    ->select('SUM(amount)')
                    ->scalar();
                $salary = round($paymentSum * (-1) * $courseConfig->teacher_rate / 100);
            } else {
                $eventPassed = Event::find()
                    ->andWhere(['between', 'event_date', $eventDateFrom->format('Y-m-d H:i:s'), $eventDateTo->format('Y-m-d H:i:s')])
                    ->andWhere(['course_id' => $courseConfig->course_id])
                    ->andWhere(['status' => Event::STATUS_PASSED])
                    ->select('COUNT(id)')
                    ->scalar();
                $salary = round($eventPassed * $courseConfig->teacher_lesson_pay);
            }

            $salaryMap[$courseConfig->teacher_id][$courseConfig->course_id]['amount'] += $salary;
        }

        return $this->render('salary', [
            'date' => $dateFrom,
            'salaryMap' => $salaryMap,
        ]);
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $course
     *
     * @return Response
     * @throws ForbiddenHttpException
     * @throws yii\web\NotFoundHttpException
     */
    public function actionSalaryDetails(int $year, int $month, int $course = 0)
    {
        $this->checkAccess('viewSalary');

        $date = new DateTimeImmutable("$year-$month-01 midnight");
        if ($course) {
            $course = Course::findOne($course);
            $courseConfig = CourseComponent::getCourseConfig($course, $date);
            if (!$course) throw new yii\web\NotFoundHttpException('Course not found');
            try {
                $spreadsheet = SalaryComponent::getCourseSalarySpreadsheet($course, $date);
            } catch (\Throwable $exception) {
                throw new yii\web\NotFoundHttpException($exception->getMessage(), $exception->getCode(), $exception);
            }
        } elseif (Yii::$app->request->get('detail')) {
            try {
                $spreadsheet = SalaryComponent::getMonthDetailedSalarySpreadsheet($date);
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
            ($courseConfig ? $courseConfig->name . ' ' : '') . "$month-$year.xlsx",
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * @param int $userId
     * @param int $courseId
     *
     * @return Response
     * @throws yii\web\BadRequestHttpException
     */
    public function actionStudentReport(int $userId, int $courseId)
    {
        $student = User::findOne($userId);
        $course = Course::findOne($courseId);

        if (!$student || $student->role != User::ROLE_STUDENT) throw new yii\web\BadRequestHttpException('Student not found');
        if (!$course) throw new yii\web\BadRequestHttpException('Course not found');

        $courseStudent = CourseStudent::find()->andWhere(['user_id' => $student->id, 'course_id' => $course->id, 'active' => CourseStudent::STATUS_ACTIVE])->one();
        if (!$courseStudent) throw new yii\web\BadRequestHttpException('Wrong student and course selection');

        ob_start();
        $objWriter = IOFactory::createWriter(StudentReport::create($student, $course), 'Xlsx');
        $objWriter->save('php://output');
        return Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            "$student->name {$course->courseConfig->name}.xlsx",
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function actionCorrection(int $userId, int $courseId)
    {
        $this->checkAccess('moneyCorrection');

        $student = User::findOne($userId);
        $course = Course::findOne($courseId);

        if (!$student || $student->role != User::ROLE_STUDENT) throw new yii\web\BadRequestHttpException('Student not found');
        if (!$course) throw new yii\web\BadRequestHttpException('Course not found');

        $courseStudent = CourseStudent::find()->andWhere(['user_id' => $student->id, 'course_id' => $course->id])->one();
        if (!$courseStudent) throw new yii\web\BadRequestHttpException('Wrong student and course selection');

        if (Yii::$app->request->isPost) {
            $paymentSum = Yii::$app->request->post('payment_sum', 0);
            if ($paymentSum > 0) {
                $payment = new Payment();
                $payment->user_id = $student->id;
                $payment->course_id = $course->id;
                $payment->admin_id = Yii::$app->user->getId();
                $payment->amount = $paymentSum;
                $payment->created_at = date('Y-m-d H:i:s');
                $payment->comment = 'Ручная корректировка долга';
                $payment->cash_received = Yii::$app->request->post('cash_received', 0) ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE;

                MoneyComponent::registerIncome($payment);
            }
        }

        return $this->render('correction', [
            'student' => $student,
            'course' => $course,
            'debt' => Debt::findOne(['user_id' => $student->id, 'course_id' => $course->id]),
        ]);
    }

    /**
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessDebt()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('root');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $formData = Yii::$app->request->post('debt', []);
        if (!isset($formData['userId'], $formData['courseId'], $formData['amount'], $formData['comment'])) {
            return self::getJsonErrorResult('Wrong request');
        }

        $user = User::findOne($formData['userId']);
        $courseStudent = CourseStudent::findOne(['course_id' => $formData['courseId'], 'active' => $formData['refund'] ? CourseStudent::STATUS_INACTIVE : CourseStudent::STATUS_ACTIVE]);
        $amount = (int)$formData['amount'];

        if (!$user) return self::getJsonErrorResult('Студент не найден');
        if ($amount <= 0) return self::getJsonErrorResult('Сумма не может быть <= 0');
        if (!$courseStudent) return self::getJsonErrorResult('Группа не найдена');

        try {
            $payment = new Payment();
            $payment->user_id = $user->id;
            $payment->course_id = $courseStudent->course_id;
            $payment->admin_id = Yii::$app->user->getId();
            $payment->amount = 0 - $amount;
            $payment->created_at = date('Y-m-d H:i:s');
            $payment->comment = ($formData['refund'] ? 'Возврат средств: ' : 'Задолженность добавлена вручную: ') . $formData['comment'];

            MoneyComponent::registerIncome($payment);
            MoneyComponent::setUserChargeDates($user, $courseStudent->course);
            return self::getJsonOkResult(['userId' => $user->id, 'refund' => $formData['refund']]);
        } catch (\Throwable $ex) {
            return self::getJsonErrorResult($ex->getMessage());
        }
    }
}
