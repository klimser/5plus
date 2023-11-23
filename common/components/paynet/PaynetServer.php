<?php

namespace common\components\paynet;

use common\components\ComponentContainer;
use common\components\helpers\PhoneHelper;
use common\components\MoneyComponent;
use common\components\payme\PaymeServer;
use common\models\Company;
use common\models\Contract;
use common\models\CourseStudent;
use common\models\User;
use common\service\payment\PaymentServiceException;
use Yii;
use yii\web\Request;
use yii\web\Response;

class PaynetServer extends PaymeServer
{
    public function handle(Request $request): Response
    {
        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        if (!$request->isPost) {
            $response->data = ['jsonrpc' => '2.0', 'id' => 0, 'error' => ['code' => -32300, 'message' => self::ERROR_MESSAGES['request_is_not_post']['en']]];
            return $response;
        }
        
        $requestData = json_decode($request->rawBody, true);
        
        if (!$requestData) {
            $response->data =  ['jsonrpc' => '2.0', 'id' => 0, 'error' => ['code' => -32700, 'message' => self::ERROR_MESSAGES['failed_to_parse']['en']]];
            return $response;
        }

        $authComplete = false;
        $auth = $request->getHeaders()->get('Authorization', '');
        if ($auth) {
            [$devNull, $auth] = explode(' ', trim($auth), 2);
            $auth = base64_decode($auth);
            [$login, $password] = explode(':', $auth, 2);
            if ($login === ComponentContainer::getPaynetApi()->login && $password === ComponentContainer::getPaynetApi()->password) {
                $authComplete = true;
            }
        }
        if (!$authComplete) {
            return (new Response())->setStatusCode(401);
        }

        try {
            switch ($requestData['method'] ?? '') {
                case 'GetInformation':
                    $responseData = $this->getInformation($requestData['params']);
                    break;
                case 'PerformTransaction':
                    $responseData = $this->complete($requestData['params']);
                    break;
                case 'CancelTransaction':
                    $responseData = $this->cancel($requestData['params']);
                    break;
                case 'CheckTransaction':
                    $responseData = $this->get($requestData['params']);
                    break;
                case 'GetStatement':
                    $responseData = $this->history($requestData['params']);
                    break;
                case 'ChangePassword':
                    throw new PaynetApiException('password_is_immutable', 100);
                default:
                    $responseData = ['error' => ['code' => 603, 'message' => self::ERROR_MESSAGES['method_not_exist']['ru']]];
            }
        } catch (PaymentServiceException $ex) {
            $responseData = ['error' => ['code' => $ex->getCode(), 'message' => self::ERROR_MESSAGES[$ex->getMessage()]['ru']]];
        }
        
        $responseData['jsonrpc'] = '2.0';
        $responseData['id'] = $requestData['id'];
        $response->data =  $responseData;
        return $response;
    }

    /**
     * @return array{student:User,courseStudent:CourseStudent}
     * @throws PaynetApiException
     */
    private function findStudentAndCourse(string $phone, string $subjectSlug): array
    {
        $result = [];

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
        if (count($students) > 1) {
            throw new PaynetApiException('unable_to_identify_student', 302);
        } elseif (count($students) === 0) {
            throw new PaynetApiException('student_not_found', 302);
        }

        $student = reset($students);
        $result['student'] = $student;

        /** @var array<int, CourseStudent> $courseStudents */
        $courseStudents = [];
        foreach ($student->activeCourseStudents as $courseStudent) {
            $courseStudents[$courseStudent->course_id] = $courseStudent;
        }

        foreach ($student->debts as $debt) {
            if (!isset($courseStudents[$debt->course_id])) {
                $courseStudents[$debt->course_id] = CourseStudent::find()->andWhere(['course_id' => $debt->course_id, 'student_id' => $student->id])->limit(1)->one();
            }
        }

        if (count($courseStudents) === 0) {
            throw new PaynetApiException('no_course_to_pay', 304);
        }

        if (count($courseStudents) === 1) {
            $result['courseStudent'] = reset($courseStudents);

            return $result;
        }

        $subjectMap = ComponentContainer::getAppPaymeApi()->getSubjectMap();
        $subjectSlug = mb_strtolower($subjectSlug, 'UTF-8');
        if (isset($subjectMap[$subjectSlug])) {
            $subjectIds = $subjectMap[$subjectSlug];
            $payCourseStudent = null;
            foreach ($courseStudents as $courseStudent) {
                if (in_array($courseStudent->course->subject_id, $subjectIds)) {
                    if (null === $payCourseStudent) {
                        $payCourseStudent = $courseStudent;
                    } else {
                        throw new PaynetApiException('unable_to_identify_course', 304);
                    }
                }
            }

            if (null !== $payCourseStudent) {
                $result['courseStudent'] = $payCourseStudent;

                return $result;
            } else {
                throw new PaynetApiException('unable_to_identify_course', 304);
            }
        }

        $definedSubjects = [];
        foreach ($subjectMap as $subjectIds) {
            $definedSubjects = array_merge($definedSubjects, $subjectIds);
        }
        $payCourseStudent = null;
        foreach ($courseStudents as $courseStudent) {
            if (!in_array($courseStudent->course->subject_id, $definedSubjects)) {
                if (null === $payCourseStudent) {
                    $payCourseStudent = $courseStudent;
                } else {
                    throw new PaynetApiException('unable_to_identify_course', 304);
                }
            }
        }

        if (null !== $payCourseStudent) {
            $result['courseStudent'] = $payCourseStudent;

            return $result;
        } else {
            throw new PaynetApiException('unable_to_identify_course', 304);
        }
    }

    private function validateParams(array $params): void
    {
        if (empty($params)
            || !array_key_exists('serviceId', $params)
            || !array_key_exists('fields', $params)
            || !isset($params['fields']['phone_number'])
            || !isset($params['fields']['course'])) {
            throw new PaynetApiException('invalid_request_data', -32600);
        }
    }

    private function getInformation($params): array
    {
        $this->validateParams($params);

        $date = new \DateTimeImmutable();
        if ($studentData = $this->findStudentAndCourse($params['fields']['phone_number'], $params['fields']['course'])) {
            return ['status' => 0, 'timestamp' => $date->format('Y-m-d H:i:s'), 'fields' => ['name' => $studentData['student']->name, 'balance' => $studentData['courseStudent']->moneyLeft]];
        }

        throw new PaynetApiException('student_not_found', 302);
    }

    private function complete($params): array
    {
        $this->validateParams($params);
        $amount = (int) $params['amount'] / 100;

        if ($amount < 1000 || $amount > 10000000) {
            throw new PaynetApiException('invalid_amount', 415);
        }

        $searchResult = $this->findStudentAndCourse($params['fields']['phone_number'], $params['fields']['course']);

        /** @var Contract|null $contract */
        $contract = Contract::find()
            ->andWhere([
                'payment_type' => Contract::PAYMENT_TYPE_PAYNET,
                'user_id' => $searchResult['student']->id,
                'course_id' => $searchResult['courseStudent']->course->id,
                'amount' => $amount,
                'status' => Contract::STATUS_PROCESS,
                'external_id' => (string) $params['transactionId'],
            ])
            ->one();

        $transaction = Yii::$app->db->beginTransaction();

        if (!$contract) {
            $contract = MoneyComponent::addStudentContract(
                Company::findOne(Company::COMPANY_EXCLUSIVE_ID),
                $searchResult['student'],
                $amount,
                $searchResult['courseStudent']->course,
            );
        }

        $contract->payment_type = Contract::PAYMENT_TYPE_PAYNET;
        $contract->status = Contract::STATUS_PROCESS;
        $contract->external_id = (string) $params['transactionId'];

        if (!$contract->save()) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()->logError('payment/create', print_r($contract->getErrors(), true), true);
            throw new PaynetApiException('unable_to_process_payment', -32603);
        }

        try {
            MoneyComponent::payContract(
                $contract,
                new \DateTime('now'),
                Contract::PAYMENT_TYPE_PAYNET,
                (string) $params['transactionId']
            );
            $transaction->commit();

            return ['providerTrnId' => $contract->number, 'timestamp' => $contract->paid_at, 'fields' => $params['fields']];
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()->logError('api/paynet', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
            throw new PaynetApiException('internal_server_error', -32603);
        }
    }

    private function cancel($params): array
    {
        if (empty($params) || !array_key_exists('transactionId', $params)) {
            throw new PaynetApiException('invalid_request_data', 411);
        }

        /** @var Contract $contract */
        if ($contract = Contract::find()
            ->andWhere([
                'payment_type' => Contract::PAYMENT_TYPE_PAYNET,
                'external_id' => $params['transactionId'],
            ])
            ->one()) {

            switch ($contract->status) {
                case Contract::STATUS_PAID:
                    throw new PaynetApiException('unable_to_cancel_transaction', 77);
                case Contract::STATUS_CANCEL:
                    throw new PaynetApiException('transaction_canceled', 202);
            }
            $contract->status = Contract::STATUS_CANCEL;
            $contract->save();

            return ['result' => ['providerTrnId' => $contract->number, 'timestamp' => date('Y-m-d H:i:s'), 'transactionState' => 2]];
        }

        throw new PaynetApiException('transaction_not_found', 203);
    }

    private function get($params): array
    {
        if (empty($params) || !array_key_exists('transactionId', $params)) {
            throw new PaynetApiException('invalid_request_data', 411);
        }

        /** @var Contract|null $contract */
        $contract = Contract::find()
            ->andWhere([
                'payment_type' => Contract::PAYMENT_TYPE_PAYNET,
                'external_id' => $params['transactionId'],
            ])
            ->one();

        return [
            'result' => match ($contract?->status) {
                Contract::STATUS_PAID => [
                    'transactionState' => 1,
                    'timestamp' => $contract->paid_at,
                    'providerTrnId' => $contract->number,
                ],
                Contract::STATUS_CANCEL => [
                    'transactionState' => 2,
                    'timestamp' => $contract->created_at,
                    'providerTrnId' => $contract->number,
                ],
                default => [
                    'transactionState' => 3,
                ],
            }
        ];
    }

    private function history($params)
    {
        if (empty($params) || !array_key_exists('dateFrom', $params) || !array_key_exists('dateTo', $params)) {
            throw new PaynetApiException('invalid_request_data', 411);
        }
        
        $startDate = new \DateTime($params['dateFrom']);
        $endDate = new \DateTime($params['dateTo']);

        $results = [];
        
        /** @var Contract[] $contracts */
        $contracts = Contract::find()
            ->andWhere(['payment_type' => Contract::PAYMENT_TYPE_PAYNET])
            ->andWhere(['between', 'created_at', $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->andWhere(['not', ['external_id' => null]])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
        foreach ($contracts as $contract) {
            $results[] = [
                'transactionId' => $contract->external_id,
                'timestamp' => $contract->status == Contract::STATUS_PAID ? $contract->paid_at : $contract->created_at,
                'amount' => $contract->amount * 100,
                'providerTrnId' => $contract->number,
            ];
        }

        return ['result' => ['statements' => $results]];
    }

    public function getPaymentTypeId(): int
    {
        return Contract::PAYMENT_TYPE_PAYNET;
    }
}
