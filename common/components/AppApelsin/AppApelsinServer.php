<?php

namespace common\components\AppApelsin;

use common\components\ComponentContainer;
use common\components\helpers\PhoneHelper;
use common\components\MoneyComponent;
use common\models\Company;
use common\models\Contract;
use common\models\Course;
use common\models\CourseStudent;
use common\models\Payment;
use common\models\User;
use common\service\payment\AbstractPaymentServer;
use Yii;
use yii\web\Request;
use yii\web\Response;

class AppApelsinServer extends AbstractPaymentServer
{
    public function handle(Request $request): Response
    {
        $response = new Response();
        $response->statusCode = 400;
        $response->data = 'Not supported';

        return $response;
    }

    private function validateRequest(Request $request): ?Response
    {
        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $response->data = ['success' => false];

        if (!$request->isPost) {
            return $response;
        }

        $authComplete = false;
        $auth = $request->getHeaders()->get('Authorization', '');
        if ($auth) {
            [$devNull, $auth] = explode(' ', trim($auth), 2);
            $auth = base64_decode($auth);
            [$login, $password] = explode(':', $auth, 2);
            if ($login === ComponentContainer::getAppApelsinApi()->login && $password === ComponentContainer::getAppApelsinApi()->password) {
                $authComplete = true;
            }
        }
        if (!$authComplete) {
            return $response;
        }

        return null;
    }

    public function handleCheck(Request $request): Response
    {
        if ($response = $this->validateRequest($request)) {
            return $response;
        }

        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $response->data = ['success' => false];

        if (!$phone = $request->rawBody) {
            return $response;
        }

        $phoneNumber = mb_substr(PhoneHelper::getPhoneDigitsOnly($phone), -9, null, 'UTF-8');
        $phoneNumber = PhoneHelper::getPhoneInternational($phoneNumber);
        /** @var User[] $users */
        $users = User::find()->andWhere(['or', ['phone' => $phoneNumber], ['phone2' => $phoneNumber]])->all();
        /** @var User[] $students */
        $students = [];
        foreach ($users as $user) {
            switch ($user->role) {
                case User::ROLE_STUDENT:
                    $students[] = $user;
                    break;
                case User::ROLE_PARENTS:
                case User::ROLE_COMPANY:
                    $students = array_merge($students, $user->children);
                    break;
            }
        }

        $result = [];
        foreach ($students as $student) {
            /** @var array<int, Course> $courses */
            $courseIdSet = [];
            foreach ($student->activeCourseStudents as $courseStudent) {
                $result[] = [
                    'id' => $student->id . ':' . $courseStudent->course_id,
                    'name' => $student->nameHidden . ', группа ' . $courseStudent->course->courseConfig->legal_name,
                    'balance' => Payment::find()
                        ->select(['SUM(amount) as balance'])
                        ->andWhere(['course_id' => $courseStudent->course_id, 'user_id' => $student->id])
                        ->scalar(),
                    'shouldPay' => $courseStudent->course->courseConfig->price12Lesson,
                ];
                $courseIdSet[$courseStudent->course_id] = true;
            }

            foreach ($student->debts as $debt) {
                if (!isset($courses[$debt->course_id])) {
                    $result[] = [
                        'id' => $student->id . ':' . $debt->course_id,
                        'name' => $student->nameHidden . ', группа ' . $debt->course->courseConfig->legal_name,
                        'balance' => Payment::find()
                            ->select(['SUM(amount) as balance'])
                            ->andWhere(['course_id' => $debt->course_id, 'user_id' => $student->id])
                            ->scalar(),
                        'shouldPay' => $debt->amount,
                    ];
                    $courseIdSet[$debt->course_id] = true;
                }
            }
        }

        $response->data = ['students' => $result];

        return $response;
    }

    public function handlePay(Request $request): Response
    {
        if ($response = $this->validateRequest($request)) {
            return $response;
        }

        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $response->data = ['success' => false];

        $requestData = json_decode($request->rawBody, true);
        if (empty($requestData['id']) || empty($requestData['amount']) || empty($requestData['studentId'])) {
            return $response;
        }

        $identity = explode(':', $requestData['studentId']);
        if (count($identity) < 2) {
            return $response;
        }

        /** @var $courseStudent CourseStudent */
        if (!($courseStudent = CourseStudent::find()
            ->andWhere(['user_id' => $identity[0], 'course_id' => $identity[1], 'active' => CourseStudent::STATUS_ACTIVE])
            ->one())) {
            return $response;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $contract = MoneyComponent::addStudentContract(
                Company::findOne(Company::COMPANY_EXCLUSIVE_ID),
                $courseStudent->user,
                ((int) $requestData['amount']) / 100,
                $courseStudent->course
            );

            $contract->status = Contract::STATUS_PROCESS;
            $contract->external_id = $requestData['id'];

            if (!$contract->save()) {
                $transaction->rollBack();
                ComponentContainer::getErrorLogger()
                    ->logError('payment/create', print_r($contract->getErrors(), true), true);

                return $response;
            }

            MoneyComponent::payContract(
                $contract,
                new \DateTime('now'),
                Contract::PAYMENT_TYPE_APP_APELSIN,
                $requestData['id']
            );

            $transaction->commit();
            $response->data = ['success' => true, 'transactionId' => $contract->number, 'timeStamp' => date('c')];
        } catch (\Throwable $ex) {
            $transaction->rollBack();
        }

        return $response;
    }

    public function getPaymentTypeId(): int
    {
        return Contract::PAYMENT_TYPE_APP_APELSIN;
    }
}
