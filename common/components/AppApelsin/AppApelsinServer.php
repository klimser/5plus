<?php

namespace common\components\AppApelsin;

use common\components\apelsin\ApelsinApiException;
use common\components\ComponentContainer;
use common\components\helpers\PhoneHelper;
use common\components\MoneyComponent;
use common\models\Company;
use common\models\Contract;
use common\models\Course;
use common\models\CourseStudent;
use common\models\Debt;
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
        $response->data = ['status' => false];

        if (!$request->isPost) {
            $response->data['error'] = 'Request is not POST';

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
            $response->data['error'] = 'Authentication failed';

            return $response;
        }

        return null;
    }

    /**
     * @return array{student:User,course:Course}
     * @throws ApelsinApiException
     */
    private function findStudentAndCourse(string $phone, string $subject): array
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
            throw new ApelsinApiException('Не удалось однозначно идентифицировать студента по номеру телефона');
        } elseif (count($students) === 0) {
            throw new ApelsinApiException('Студент не найден');
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
            throw new ApelsinApiException('У студента нет курсов для оплаты');
        }

        if (count($courses) === 1) {
            $result['course'] = reset($courses);

            return $result;
        }

        $subjectMap = ComponentContainer::getAppApelsinApi()->getSubjectMap();
        $subject = (int) $subject;
        if (isset($subjectMap[$subject])) {
            $subjectIds = $subjectMap[$subject];
            $payCourse = null;
            foreach ($courses as $course) {
                if (in_array($course->subject_id, $subjectIds)) {
                    if (null === $payCourse) {
                        $payCourse = $course;
                    } else {
                        throw new ApelsinApiException('Не удалось определить курс, возможно студент занимается на нескольких курасах по этому предмету');
                    }
                }
            }

            if (null !== $payCourse) {
                $result['course'] = $payCourse;

                return $result;
            } else {
                throw new ApelsinApiException('Не удалось определить курс, возможно студент не занимается на курсах по этому предмету');
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
                    throw new ApelsinApiException('Не удалось определить курс, возможно студент занимается на нескольких курасах по этому предмету');
                }
            }
        }

        if (null !== $payCourse) {
            $result['course'] = $payCourse;

            return $result;
        } else {
            throw new ApelsinApiException('Не удалось определить курс, возможно студент не занимается на курсах по этому предмету');
        }
    }

    public function handleCheck(Request $request): Response
    {
        if ($response = $this->validateRequest($request)) {
            return $response;
        }

        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $response->data = ['status' => false];

        $requestData = json_decode($request->rawBody, true);
        if (empty($requestData['phone']) || empty($requestData['course'])) {
            $response->data['error'] = 'Missing mandatory request parameters';

            return $response;
        }
        $phone = $requestData['phone'];
        $course = $requestData['course'];

        try {
            if ($searchResult = $this->findStudentAndCourse($phone, $course)) {
                $response->data['status'] = true;
                $response->data['data'] = [
                    'fullName' => $searchResult['student']->nameHidden . ', группа ' . $searchResult['course']->courseConfig->legal_name,
                    'balance' => 100 * Payment::find()
                        ->select(['SUM(amount) as balance'])
                        ->andWhere(['course_id' => $searchResult['course']->id, 'user_id' => $searchResult['student']->id])
                        ->scalar(),
                ];
            }
        } catch (ApelsinApiException $ex) {
            $response->data['error'] = $ex->getMessage();

            return $response;
        }

        return $response;
    }

    public function handlePay(Request $request): Response
    {
        if ($response = $this->validateRequest($request)) {
            return $response;
        }

        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $response->data = ['status' => false];

        $requestData = json_decode($request->rawBody, true);
        if (empty($requestData['transactionId']) || empty($requestData['amount']) || empty($requestData['phone']) || empty($requestData['course'])) {
            $response->data['error'] = 'Missing mandatory request parameters';

            return $response;
        }

        $amount = (int) $requestData['amount'] / 100;
        $phone = $requestData['phone'];
        $course = $requestData['course'];

        if (null !== Contract::findOne(['payment_type' => Contract::PAYMENT_TYPE_APP_APELSIN, 'external_id' => $requestData['transactionId']])) {
            $response->data['error'] = 'Duplicated transactionId';

            return $response;
        }

        if ($amount < 1000 || $amount > 100000000) {
            $response->data['error'] = 'Invalid amount';

            return $response;
        }

        try {
            $searchResult = $this->findStudentAndCourse($phone, $course);
        } catch (ApelsinApiException $ex) {
            $response->data['error'] = $ex->getMessage();

            return $response;
        }

        /** @var $courseStudent CourseStudent */
        $courseStudent = CourseStudent::find()
            ->andWhere(['user_id' => $searchResult['student']->id, 'course_id' => $searchResult['course']->id, 'active' => CourseStudent::STATUS_ACTIVE])
            ->one();
        /** @var Debt $debt */
        $debt = Debt::find()->andWhere(['user_id' => $searchResult['student']->id, 'course_id' => $searchResult['course']->id])->one();

        if (!($courseStudent) && !($debt)) {
            $response->data['error'] = 'Не найден курс, за который возможна оплата';

            return $response;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $contract = MoneyComponent::addStudentContract(
                Company::findOne(Company::COMPANY_EXCLUSIVE_ID),
                ($courseStudent ?? $debt)->user,
                $amount,
                ($courseStudent ?? $debt)->course
            );

            $contract->status = Contract::STATUS_PROCESS;
            $contract->external_id = $requestData['transactionId'];

            if (!$contract->save()) {
                $transaction->rollBack();
                ComponentContainer::getErrorLogger()
                    ->logError('payment/create', print_r($contract->getErrors(), true), true);
                $response->data['error'] = 'Internal server error';

                return $response;
            }

            MoneyComponent::payContract(
                $contract,
                new \DateTime('now'),
                Contract::PAYMENT_TYPE_APP_APELSIN,
                $requestData['transactionId']
            );

            $transaction->commit();
            $response->data = ['status' => true];
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            $response->data['error'] = 'Internal server error';
        }

        return $response;
    }

    public function getPaymentTypeId(): int
    {
        return Contract::PAYMENT_TYPE_APP_APELSIN;
    }
}
