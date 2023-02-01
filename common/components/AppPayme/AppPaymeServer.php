<?php

namespace common\components\AppPayme;

use common\components\ComponentContainer;
use common\components\helpers\PhoneHelper;
use common\components\MoneyComponent;
use common\components\payme\PaymeApiException;
use common\components\payme\PaymeServer;
use common\models\Company;
use common\models\Contract;
use common\models\Course;
use common\models\User;
use common\service\payment\PaymentServiceException;
use Yii;
use yii\web\Request;
use yii\web\Response;

class AppPaymeServer extends PaymeServer
{
    public function handle(Request $request): Response
    {
        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        if (!$request->isPost) {
            $response->data = ['id' => 0, 'error' => ['code' => -32300, 'message' => self::ERROR_MESSAGES['request_is_not_post']]];
            return $response;
        }
        
        $requestData = json_decode($request->rawBody, true);
        
        if (!$requestData) {
            $response->data =  ['id' => 0, 'error' => ['code' => -32700, 'message' => self::ERROR_MESSAGES['failed_to_parse']]];
            return $response;
        }

        try {
            $authComplete = false;
            $auth = $request->getHeaders()->get('Authorization', '');
            if ($auth) {
                [$devNull, $auth] = explode(' ', trim($auth), 2);
                $auth = base64_decode($auth);
                [$login, $password] = explode(':', $auth, 2);
                if ($login === ComponentContainer::getAppPaymeApi()->login && $password === ComponentContainer::getAppPaymeApi()->password) {
                    $authComplete = true;
                }
            }
            if (!$authComplete) {
                throw new PaymeApiException('authorization_failed', -32504);
            }
            
            switch ($requestData['method'] ?? '') {
                case 'CheckPerformTransaction':
                    $responseData = $this->checkPerformTransaction($requestData['params']);
                    break;
                case 'CreateTransaction':
                    $responseData = $this->createTransaction($requestData['params']);
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
                    throw new PaymeApiException('password_is_immutable', -32400);
                default:
                    $responseData = ['error' => ['code' => -32601, 'message' => self::ERROR_MESSAGES['method_not_exist']]];
            }
        } catch (PaymentServiceException $ex) {
            $responseData = ['error' => ['code' => $ex->getCode(), 'message' => self::ERROR_MESSAGES[$ex->getMessage()]]];
        }
        
        $responseData['id'] = $requestData['id'];
        $response->data =  $responseData;
        return $response;
    }

    /**
     * @return array{student:User,course:Course}
     * @throws PaymeApiException
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
                    $students += $user->children;
                    break;
            }
        }
        if (count($students) > 1) {
            throw new PaymeApiException('unable_to_identify_student', -31060);
        } elseif (count($students) === 0) {
            throw new PaymeApiException('student_not_found', -31061);
        }

        $student = reset($students);
        $result['student'] = $student;

        /** @var array<int, Course> $courses */
        $courses = [];
        foreach ($student->activeCourseStudents as $courseStudent) {
            $courses[$courseStudent->course_id] = $courseStudent->course;
        }

        foreach ($student->debts as $debt) {
            if (!isset($courses[$debt->course_id])) {
                $courses[$debt->course_id] = Course::findOne($debt->course_id);
            }
        }

        if (count($courses) === 0) {
            throw new PaymeApiException('no_course_to_pay', -31061);
        }

        if (count($courses) === 1) {
            $result['course'] = reset($courses);

            return $result;
        }

        $subjectMap = ComponentContainer::getAppPaymeApi()->getSubjectMap();
        if (isset($subjectMap[$subjectSlug])) {
            $subjectIds = $subjectMap[$subjectSlug];
            $payCourse = null;
            foreach ($courses as $course) {
                if (in_array($course->subject_id, $subjectIds)) {
                    if (null === $payCourse) {
                        $payCourse = $course;
                    } else {
                        throw new PaymeApiException('unable_to_identify_course', -31062);
                    }
                }
            }

            if (null !== $payCourse) {
                $result['course'] = $payCourse;

                return $result;
            } else {
                throw new PaymeApiException('unable_to_identify_course', -31062);
            }
        }

        $definedSubjects = [];
        foreach ($subjectMap as $subjectIds) {
            $definedSubjects = array_merge($definedSubjects, $subjectIds);
        }
        $payCourse = null;
        foreach ($courses as $course) {
            if (!in_array($course->subject_id, $definedSubjects)) {
                if (null === $payCourse) {
                    $payCourse = $course;
                } else {
                    throw new PaymeApiException('unable_to_identify_course', -31062);
                }
            }
        }

        if (null !== $payCourse) {
            $result['course'] = $payCourse;

            return $result;
        } else {
            throw new PaymeApiException('unable_to_identify_course', -31062);
        }
    }

    private function validateParams(array $params): void
    {
        if (empty($params)
            || !array_key_exists('amount', $params)
            || !array_key_exists('account', $params)
            || !isset($params['account']['phone_number'])
            || !isset($params['account']['course'])) {
            throw new PaymeApiException('invalid_request_data', -31050);
        }
    }
    
    private function checkPerformTransaction($params): array
    {
        $this->validateParams($params);

        if ($params['amount'] < 1000 || $params['amount'] > 100000000) {
            throw new PaymeApiException('invalid_amount', -31001);
        }

        if ($this->findStudentAndCourse($params['account']['phone_number'], $params['account']['course'])) {
            return ['result' => ['allow' => true]];
        }

        throw new PaymeApiException('invoice_not_found', -31050);
    }

    private function createTransaction($params): array
    {
        $this->validateParams($params);

        if ($params['amount'] < 1000 || $params['amount'] > 100000000) {
            throw new PaymeApiException('invalid_amount', -31001);
        }

        $searchResult = $this->findStudentAndCourse($params['account']['phone_number'], $params['account']['course']);

        /** @var Contract $existingContract */
        $existingContract = Contract::find()
            ->andWhere([
                'payment_type' => Contract::PAYMENT_TYPE_APP_PAYME,
                'user_id' => $searchResult['student']->id,
                'course_id' => $searchResult['course']->id,
                'amount' => (int) $params['amount'],
                'status' => Contract::STATUS_PROCESS,
            ])
            ->andWhere(['like', 'external_id', $params['id'] . '|%', false])
            ->one();

        if ($existingContract) {
            [$devNull, $time] = explode('|', $existingContract->external_id);
            $transactionTime = (int) $time;
            return ['result' => ['create_time' => $transactionTime, 'transaction' => $existingContract->number, 'state' => 1]];
        }

        try {
            $contract = MoneyComponent::addStudentContract(
                Company::findOne(Company::COMPANY_EXCLUSIVE_ID),
                $searchResult['student'],
                (int) $params['amount'],
                $searchResult['course']
            );
        } catch (\Throwable $ex) {
            throw new PaymeApiException('unable_to_process_payment', -31063);
        }

        $contract->payment_type = Contract::PAYMENT_TYPE_APP_PAYME;
        $contract->status = Contract::STATUS_PROCESS;
        $contract->external_id = $params['id'] . '|' . $params['time'];
        $transactionTime = $params['time'];

        if (!$contract->save()) {
            ComponentContainer::getErrorLogger()
                ->logError('payment/create', print_r($contract->getErrors(), true), true);
            throw new PaymeApiException('unable_to_process_payment', -31063);
        }

        return ['result' => ['create_time' => $transactionTime, 'transaction' => $contract->number, 'state' => 1]];
    }

    private function complete($params): array
    {
        if (empty($params) || !array_key_exists('id', $params)) {
            throw new PaymeApiException('invalid_request_data', -31050);
        }
        
        /** @var Contract $contract */
        if ($contract = Contract::find()
            ->andWhere(['payment_type' => Contract::PAYMENT_TYPE_APP_PAYME])
            ->andWhere(['like', 'external_id', $params['id'] . '|%', false])->one()) {
            if ($contract->status == Contract::STATUS_CANCEL) {
                throw new PaymeApiException('transaction_canceled', -31008);
            }

            if ($contract->status != Contract::STATUS_PAID) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    MoneyComponent::payContract(
                        $contract,
                        new \DateTime('now'),
                        Contract::PAYMENT_TYPE_APP_PAYME,
                        $params['id']
                    );
                    $transaction->commit();
                } catch (\Throwable $exception) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()->logError('api/payme', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                    throw new PaymeApiException('internal_server_error', -31008);
                }
            }

            return ['result' => ['transaction' => $contract->number, 'perform_time' => $contract->paidDate->getTimestamp() * 1000, 'state' => 2]];
        }

        throw new PaymeApiException('transaction_not_found', -31003);
    }

    private function cancel($params): array
    {
        if (empty($params) || !array_key_exists('id', $params)) {
            throw new PaymeApiException('invalid_request_data', -31050);
        }

        /** @var Contract $contract */
        if ($contract = Contract::find()
            ->andWhere(['payment_type' => Contract::PAYMENT_TYPE_APP_PAYME])
            ->andWhere(['like', 'external_id', $params['id'] . '|%', false])->one()) {
            if ($contract->status == Contract::STATUS_PAID) {
                throw new PaymeApiException('unable_to_cancel_transaction', -31007);
            }
            $contract->status = Contract::STATUS_CANCEL;
            $contract->external_id .= '|' . $params['reason'];
            $contract->save();

            return ['result' => ['transaction' => $contract->number, 'cancel_time' => $contract->createDate->getTimestamp() * 1000, 'state' => -1]];
        }

        throw new PaymeApiException('transaction_not_found', -31003);
    }

    private function get($params): array
    {
        if (empty($params) || !array_key_exists('id', $params)) {
            throw new PaymeApiException('invalid_request_data', -31050);
        }

        /** @var Contract $contract */
        if ($contract = Contract::find()
            ->andWhere(['payment_type' => Contract::PAYMENT_TYPE_APP_PAYME])
            ->andWhere(['like', 'external_id', $params['id'] . '|%', false])->one()) {
            $externalParams = explode('|', $contract->external_id);
            return ['result' => [
                'create_time' => (int)$externalParams[1],
                'transaction' => $contract->number,
                'state' => match ($contract->status) {
                    Contract::STATUS_PAID => 2,
                    Contract::STATUS_CANCEL => -1,
                    default => 1,
                },
                'perform_time' => $contract->status == Contract::STATUS_PAID ? $contract->paidDate->getTimestamp() * 1000 : 0,
                'cancel_time' => $contract->status == Contract::STATUS_CANCEL ? $contract->createDate->getTimestamp() * 1000 : 0,
                'reason' => (int)$externalParams[2] ?? null,
            ]];
        }

        throw new PaymeApiException('transaction_not_found', -31003);
    }
    
    private function history($params)
    {
        if (empty($params) || !array_key_exists('from', $params) || !array_key_exists('to', $params)) {
            throw new PaymeApiException('invalid_request_data', -31050);
        }
        
        $startDate = new \DateTime();
        $startDate->setTimestamp($params['from']);
        $startDate->modify('-2 days');
        $endDate = new \DateTime();
        $endDate->setTimestamp($params['to']);
        $endDate->modify('+2 days');
        
        $results = [];
        
        /** @var Contract[] $contracts */
        $contracts = Contract::find()
            ->andWhere(['payment_type' => Contract::PAYMENT_TYPE_APP_PAYME])
            ->andWhere(['between', 'created_at', $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->andWhere(['not', ['external_id' => null]])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
        foreach ($contracts as $contract) {
            [$transactionId, $timestamp] = explode('|', $contract->external_id);
            if ($timestamp >= $params['from'] && $timestamp <= $params['to']) {
                $results[] = [
                    'id' => $transactionId,
                    'time' => (int)$timestamp,
                    'amount' => $contract->amount * 100,
                    'account' => [
                        'phone_number' => $contract->user->phoneInternational,
                    ],
                    'create_time' => (int)$timestamp,
                    'perform_time' => $contract->status == Contract::STATUS_PAID ? $contract->paidDate->getTimestamp() * 1000 : 0,
                    'cancel_time' => 0,
                    'transaction' => $contract->number,
                    'state' => $contract->status == Contract::STATUS_PAID ? 2 : 1,
                ];
            }
        }

        return ['result' => ['transactions' => $results]];
    }

    public function getPaymentTypeId(): int
    {
        return Contract::PAYMENT_TYPE_APP_PAYME;
    }
}
