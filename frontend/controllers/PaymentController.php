<?php

namespace frontend\controllers;

use common\components\ComponentContainer;
use common\components\extended\Controller;
use common\components\helpers\PhoneHelper;
use common\components\MoneyComponent;
use common\models\Company;
use common\models\Contract;
use common\models\Course;
use common\models\CourseStudent;
use common\models\GiftCard;
use common\models\GiftCardType;
use common\models\Module;
use common\models\PaymentLink;
use common\models\User;
use common\models\Webpage;
use common\service\payment\PaymentApiFactory;
use common\service\payment\PaymentServiceException;
use himiklab\yii2\recaptcha\ReCaptchaValidator2;
use Yii;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentController extends Controller
{
    private function getPageParams(?string $type = null): array
    {
        $moduleId = Module::getModuleIdByControllerAndAction('payment', 'index');
        $params = [
            'webpage' => Webpage::find()->where(['module_id' => $moduleId])->one(),
            'hide_social' => true,
            'h1' => 'Онлайн оплата',
        ];
        switch ($type) {
            case 'student':
                $params['h1'] .= ' для учащихся';
                break;
            case 'new':
                $params['h1'] .= ' для новых студентов';
                $params['giftCardTypes'] = GiftCardType::find()->andWhere(['active' => GiftCardType::STATUS_ACTIVE])->orderBy('name')->all();
                break;
        }
        return $params;
    }

    public function actionIndex($type)
    {
        if (!in_array($type, ['new', 'student'])) {
            $type = null;
        }
        return $this->render('index' . ($type ? "-$type" : ''), $this->getPageParams($type));
    }

    public function actionFind()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Only post allowed');
        }

        $pageParams = $this->getPageParams('student');
        $validator = new ReCaptchaValidator2();
        $reCaptcha = Yii::$app->request->post('reCaptcha');
        try {
            if (!$reCaptcha || !$validator->validate($reCaptcha)) {
                throw new \Exception('Recaptcha failed');
            }
        } catch (\Exception $ex) {
            Yii::$app->session->addFlash('error', 'Проверка на робота не пройдена');
            return $this->render('index-student', $pageParams);
        }

        $phoneFull = '+998' . substr(preg_replace('#\D#', '', Yii::$app->request->post('phoneFormatted')), -9);
        return $this->processPhone($phoneFull);
    }

    private function processPhone($phoneFull)
    {
        $pageParams = $this->getPageParams('student');
        $users = User::findActiveCustomersByPhone($phoneFull);
        if (count($users) == 0) {
            Yii::$app->session->addFlash('error', 'По данному номеру студенты не найдены');
            return $this->render('index-student', $pageParams);
        } else {
            if (count($users) == 1) {
                $user = reset($users);
                if ($user->isAgeConfirmed()) {
                    return $this->renderPaymentForm($user);
                } else {
                    Yii::$app->session->set('userId', $user->id);
                    Yii::$app->session->set('phoneFull', $phoneFull);
                    return $this->renderAgeConfirmationForm($user, $phoneFull, $pageParams);
                }
            }

            Yii::$app->session->set('phoneFull', $phoneFull);
            return $this->render('user-select-form', array_merge($pageParams, ['users' => $users]));
        }
    }

    public function actionSelectUser()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Only post allowed');
        }

        $userId = null;
        foreach (Yii::$app->request->post() as $key => $value) {
            if (preg_match('#^user-(\d+)$#', $key, $matches)) {
                $userId = $matches[1];
                break;
            }
        }
        if (!$userId || !($user = User::findActiveCustomerById($userId))) {
            throw new BadRequestHttpException('User is not found');
        }

        if ($user->isAgeConfirmed()) {
            Yii::$app->session->remove('phoneFull');
            return $this->renderPaymentForm($user);
        } else {
            Yii::$app->session->set('userId', $user->id);
            $pageParams = $this->getPageParams('student');
            return $this->renderAgeConfirmationForm($user, Yii::$app->session->get('phoneFull'), $pageParams);
        }
    }

    public function actionAgeConfirmation($id, $webpage)
    {
        $userId = Yii::$app->session->get('userId');
        $phoneFull = Yii::$app->session->get('phoneFull');
        $pageParams = ['hide_social' => true, 'h1' => 'Подтвердите свой возраст', 'webpage' => $webpage];
        if ($userId && ($user = User::findActiveCustomerById($userId)) && $phoneFull) {
            return $this->renderAgeConfirmationForm($user, $phoneFull, $pageParams);
        }
        return $this->renderAgeConfirmationForm(null, null, $pageParams);
    }

    public function actionPay()
    {
        $pageParams = $this->getPageParams('student');
        $userId = Yii::$app->session->get('userId');
        $phoneFull = Yii::$app->session->get('phoneFull');
        if (!$userId && $phoneFull) {
            return $this->processPhone($phoneFull);
        }
        if ($userId && ($user = User::findActiveCustomerById($userId))) {
            return $user->isAgeConfirmed() ? $this->renderPaymentForm($user) : $this->renderAgeConfirmationForm($user, $phoneFull, $pageParams);
        }
        
        return $this->redirect(Url::to(['webpage', 'id' => $pageParams['webpage']->id, 'type' => 'student']));
    }

    private function renderPaymentForm(User $user)
    {
        $pageParams = $this->getPageParams('student');
        return $this->render('payment-form', array_merge($pageParams, ['user' => $user]));
    }

    private function renderAgeConfirmationForm(?User $user = null, ?string $phone = null, array $pageParams = [])
    {
        return $this->render(
            'age-confirmation-form',
            array_merge(
                $pageParams,
                [
                    'user' => $user,
                    'phone' => $phone ? PhoneHelper::getPhoneFormatted($phone) : null
                ]
            )
        );
    }

    public function actionFlushAgeConfirmationSession()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        Yii::$app->session->remove('phoneFull');
        Yii::$app->session->remove('userId');
        return self::getJsonOkResult();
    }

    public function actionSendAgeConfirmationSms()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $validator = new ReCaptchaValidator2();
        $reCaptcha = Yii::$app->request->post('g-recaptcha-response');
        if (!$reCaptcha || !$validator->validate($reCaptcha)) {
            return self::getJsonErrorResult('Проверка на робота не пройдена');
        }
        if (!Yii::$app->request->post('agree')) {
            return self::getJsonErrorResult('Согласие с публичной офертой обязательно');
        }
        if ($phoneFormatted = Yii::$app->request->post('phoneFormatted')) {
            $phoneFull = '+998' . substr(preg_replace('#\D#', '', $phoneFormatted), -9);
            $users = User::findActiveCustomersByPhone($phoneFull);
        } elseif (($phoneFull = Yii::$app->session->get('phoneFull')) && ($userId = Yii::$app->session->get('userId'))) {
            $user = User::findActiveCustomerById($userId);
            if ($user->phone !== $phoneFull && $user->phone2 !== $phoneFull) {
                Yii::$app->session->remove('phoneFull');
                Yii::$app->session->remove('userId');
                return self::getJsonErrorResult('Обнаружено несоответствие параметров, заполните форму снова');
            }
            $users = [$user];
        }
        
        if (!$phoneFull) {
            return self::getJsonErrorResult('Обнаружено несоответствие параметров, заполните форму снова');
        }
        if (empty($users)) {
            return self::getJsonErrorResult('Не найдено ни одного пользователя');
        }
        if ($blockUntil = ComponentContainer::getAgeValidator()->getBlockUntilDate($phoneFull)) {
            $result = self::getJsonErrorResult('СМС не могут быть отправлены слишком часто, дождитесь получения СМС на телефон или запросите повторную отправку позже');
            $result['timeout'] = $blockUntil->getTimestamp() - time();
            return $result;
        }
        if (ComponentContainer::getAgeValidator()->add($phoneFull, $users)) {
            $blockUntil = ComponentContainer::getAgeValidator()->getBlockUntilDate($phoneFull);
                return self::getJsonOkResult([
                    'message' => 'СМС отправлена, введите код и нажмите "Подтвердить"',
                    'timeout' => $blockUntil->getTimestamp() - time(),
                ]);
        }
        
        return self::getJsonErrorResult('Не удалось отправить СМС');
    }
    
    public function actionAgeConfirm()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->post('agree')) {
            return self::getJsonErrorResult('Согласие с публичной офертой обязательно');
        }
        if ($phoneFormatted = Yii::$app->request->post('phoneFormatted')) {
            $phoneFull = '+998' . substr(preg_replace('#\D#', '', $phoneFormatted), -9);
            $users = User::findActiveCustomersByPhone($phoneFull);
        } elseif (($phoneFull = Yii::$app->session->get('phoneFull')) && ($userId = Yii::$app->session->get('userId'))) {
            $user = User::findActiveCustomerById($userId);
            if ($user->phone !== $phoneFull && $user->phone2 !== $phoneFull) {
                Yii::$app->session->remove('phoneFull');
                Yii::$app->session->remove('userId');
                return self::getJsonErrorResult('Обнаружено несоответствие параметров, заполните форму снова');
            }
            $users = [$user];
        }
        $smsCode = Yii::$app->request->post('smsCode');

        if (!$phoneFull || !$smsCode) {
            return self::getJsonErrorResult('Обнаружено несоответствие параметров, заполните форму снова');
        }
        if (empty($users)) {
            return self::getJsonErrorResult('Не найдено ни одного пользователя');
        }
        $isValid = false;
        foreach ($users as $user) {
            if (ComponentContainer::getAgeValidator()->validate($phoneFull, $user, $smsCode)) {
                $isValid = true;
            }
        }
        if ($isValid) {
            Yii::$app->session->set('phoneFull', $phoneFull);
            if (1 === count($users)) {
                Yii::$app->session->set('userId', $users[0]->id);
            }
            return self::getJsonOkResult(['message' => 'Спасибо, желаем Вам шикарной учебы!']);
        }
        return self::getJsonErrorResult('Не подтверждено. Проверьте корректность введенного кода из СМС.');
    }

    public function actionLink($key)
    {
        $paymentLink = PaymentLink::findOne(['hash_key' => $key]);
        $courseStudents = [];
        if ($paymentLink) {
            $courseStudents = CourseStudent::findAll(['course_id' => $paymentLink->course_id, 'user_id' => $paymentLink->user_id]);
        }
        $params = $this->getPageParams('student');
        $params['paymentLink'] = $paymentLink;
        $params['courseStudents'] = $courseStudents;

        return $this->render('link', $params);
    }

    public function actionCreate()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $studentId = Yii::$app->request->post('student');
        $courseId = Yii::$app->request->post('course');
        $amount = intval(Yii::$app->request->post('amount'));
        $paymentMethodId = intval(Yii::$app->request->post('method', 0));

        if (!$studentId) return self::getJsonErrorResult('No student ID');
        if (!$courseId) return self::getJsonErrorResult('No course ID');
        if ($amount < 1000) return self::getJsonErrorResult('Wrong payment amount');

        /** @var User $student */
        $student = User::find()->andWhere(['id' => $studentId, 'role' => User::ROLE_STUDENT, 'status' => User::STATUS_ACTIVE])->one();
        /** @var Course $course */
        $course = Course::find()->andWhere(['id' => $courseId, 'active' => Course::STATUS_ACTIVE])->one();

        if (!$student) return self::getJsonErrorResult('Student not found');
        if (!$course) return self::getJsonErrorResult('Course not found');

        $courseStudent = CourseStudent::find()->andWhere(['user_id' => $student->id, 'course_id' => $course->id, 'active' => CourseStudent::STATUS_ACTIVE])->one();
        if (!$courseStudent) return self::getJsonErrorResult('Для этого студента внесение оплаты невозможно');

        try {
            $paymentApi = PaymentApiFactory::getPaymentApi($paymentMethodId);
        } catch (PaymentServiceException $ex) {
            return self::getJsonErrorResult('Wrong payment method');
        }

        $contract = MoneyComponent::addStudentContract(
            Company::findOne(Company::COMPANY_EXCLUSIVE_ID),
            $student,
            $amount,
            $course
        );

        $returnUrl = Url::to(['payment/complete', 'payment' => $contract->id], true);
        $details = [
            'description' => sprintf(
                'Оплата занятий в группе %s: %d занятий',
                $course->courseConfig->legal_name,
                intval(round($contract->amount / ($contract->discount ? $course->courseConfig->lesson_price_discount : $course->courseConfig->lesson_price)))
            ),
            'ip' => Yii::$app->request->userIP,
            'paymentDetails' => [
                'студент' => $course->courseConfig->name,
                'группа' => $course->courseConfig->legal_name,
                'занятий' => intval(round($contract->amount / ($contract->discount ? $course->courseConfig->lesson_price_discount : $course->courseConfig->lesson_price)))
            ]
        ];

        try {
            $transactionResponse = $paymentApi->payCreate($contract->amount, $contract->number, $returnUrl, $details);
            $contract->payment_type = $paymentMethodId;
            $contract->status = Contract::STATUS_PROCESS;
            if ($transactionResponse->getTransactionId()) {
                $contract->external_id = $transactionResponse->getTransactionId();
            }
            
            if (!$contract->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('payment/create', print_r($contract->getErrors(), true), true);
                return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
            }

            return self::getJsonOkResult(['redirectUrl' => $transactionResponse->getRedirectUrl()]);
        } catch (\Throwable $exception) {
            ComponentContainer::getErrorLogger()
                ->logError('payment/create', 'Exception: ' . $exception->getMessage(), true);

            return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
        }
    }

    public function actionCreateNew()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $giftCardData = Yii::$app->request->post('giftcard', []);
        $paymentMethodId = intval(Yii::$app->request->post('method', 0));

        if (!isset($giftCardData['student_name'])) return self::getJsonErrorResult('No student name');
        if (!isset($giftCardData['student_phone'])) return self::getJsonErrorResult('No student phone');
        if (!isset($giftCardData['type'])) return self::getJsonErrorResult('No payment type selected');
        if (!isset($giftCardData['email'])) return self::getJsonErrorResult('No email');
        $giftCardType = GiftCardType::findOne(['id' => $giftCardData['type'], 'active' => GiftCardType::STATUS_ACTIVE]);
        if (!$giftCardType) return self::getJsonErrorResult('Unknown type');

        try {
            $paymentApi = PaymentApiFactory::getPaymentApi($paymentMethodId);
        } catch (PaymentServiceException $ex) {
            return self::getJsonErrorResult('Wrong payment method');
        }

        $transaction = Yii::$app->db->beginTransaction();

        $giftCard = new GiftCard();
        $giftCard->name = $giftCardType->name;
        $giftCard->amount = $giftCardType->amount;
        $giftCard->status = GiftCard::STATUS_NEW;
        $giftCard->customer_name = $giftCardData['student_name'];
        $giftCard->phoneFormatted = $giftCardData['student_phone'];
        $giftCard->customer_email = $giftCardData['email'];
        $additionalData = ['payment_method' => $paymentMethodId];
        if ($giftCardData['parents_name'] && $giftCardData['parents_phone']) {
            $additionalData['parents_name'] = $giftCardData['parents_name'];
            $additionalData['parents_phone'] = $giftCardData['parents_phone'];
        }
        $giftCard->additionalData = $additionalData;

        if (!$giftCard->save()) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('payment/create-new', print_r($giftCard->getErrors(), true), true);
            return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
        }

        $returnUrl = urlencode(Url::to(['payment/complete', 'gc' => $giftCard->code], true));
        $details = [
            'description' => sprintf(
                'Оплата занятий по предмету %s',
                $giftCard->name
            ),
            'ip' => Yii::$app->request->userIP,
            'paymentDetails' => [
                'студент' => $giftCard->customer_name,
                'предмет' => $giftCard->name,
            ]
        ];

        try {
            $transactionResponse = $paymentApi->payCreate($giftCard->amount, "gc-$giftCard->id", $returnUrl, $details);

            if ($transactionResponse->getTransactionId()) {
                $additionalData = $giftCard->additionalData;
                $additionalData['transaction_id'] = $transactionResponse->getTransactionId();
                $giftCard->additionalData = $additionalData;
                $giftCard->save();
            }
            
            $transaction->commit();

            return self::getJsonOkResult(['redirectUrl' => $transactionResponse->getRedirectUrl()]);
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('payment/create-new', 'Exception: ' . $exception->getMessage(), true);

            return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
        }
    }

    public function actionComplete()
    {
        $params = $this->getPageParams();
        if ($giftCardCode = Yii::$app->request->get('gc')) {
            $giftCard = GiftCard::findOne(['code' => $giftCardCode]);
            if ($giftCard) {
                $params['success'] = $giftCard->status == GiftCard::STATUS_PAID;
                $params['amount'] = $giftCard->amount;
                $params['giftCard'] = $giftCard;
            }
        } elseif ($contractId = Yii::$app->request->get('payment')) {
            $contract = Contract::findOne($contractId);
            if ($contract) {
                $params['success'] = (Contract::STATUS_PAID === $contract->status);
                $params['amount'] = $contract->amount;
                $params['course'] = $contract->courseConfig->legal_name;
                $params['discount'] = (Contract::STATUS_ACTIVE === $contract->discount);
                $params['lessons'] = intval(round($contract->amount / ($contract->discount ? $contract->courseConfig->lesson_price_discount : $contract->courseConfig->lesson_price)));
            }
        }
        return $this->render('complete', $params);
    }

    public function actionPrint()
    {
        if ($giftCardCode = Yii::$app->request->get('gc')) {
            $giftCard = GiftCard::findOne(['code' => $giftCardCode]);
            if ($giftCard && $giftCard->status == GiftCard::STATUS_PAID) {
                $giftCardDoc = new \common\resources\documents\GiftCard($giftCard);
                return Yii::$app->response->sendContentAsFile(
                    $giftCardDoc->save(),
                    'flyer.pdf',
                    ['inline' => true, 'mimeType' => 'application/pdf']
                );
            }
        }

        throw new NotFoundHttpException('Wrong URL');
    }
}
