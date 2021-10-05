<?php

namespace frontend\controllers;

use common\components\AgeValidator;
use common\components\ComponentContainer;
use common\components\extended\Controller;
use common\components\helpers\MaskString;
use common\components\helpers\Phone;
use common\components\MoneyComponent;
use common\components\paymo\PaymoApiException;
use common\models\AgeConfirmation;
use common\models\Company;
use common\models\Contract;
use common\models\GiftCard;
use common\models\GiftCardType;
use common\models\Group;
use common\models\GroupPupil;
use common\models\Module;
use common\models\PaymentLink;
use common\models\User;
use common\models\Webpage;
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
            case 'pupil':
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
        if (!in_array($type, ['new', 'pupil'])) {
            $type = null;
        }
        return $this->render('index' . ($type ? "-$type" : ''), $this->getPageParams($type));
    }

    public function actionFind()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Only post allowed');
        }

        $pageParams = $this->getPageParams('pupil');
        $validator = new ReCaptchaValidator2();
        $reCaptcha = Yii::$app->request->post('reCaptcha');
        try {
            if (!$reCaptcha || !$validator->validate($reCaptcha)) {
                throw new \Exception('Recaptcha failed');
            }
        } catch (\Exception $ex) {
            Yii::$app->session->addFlash('error', 'Проверка на робота не пройдена');
            return $this->render('index-pupil', $pageParams);
        }

        $phoneFull = '+998' . substr(preg_replace('#\D#', '', Yii::$app->request->post('phoneFormatted')), -9);
        return $this->processPhone($phoneFull);
    }

    private function processPhone($phoneFull)
    {
        $pageParams = $this->getPageParams('pupil');
        $users = User::findActiveCustomersByPhone($phoneFull);
        if (count($users) == 0) {
            Yii::$app->session->addFlash('error', 'По данному номеру студенты не найдены');
            return $this->render('index-pupil', $pageParams);
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
            $pageParams = $this->getPageParams('pupil');
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
        $pageParams = $this->getPageParams('pupil');
        $userId = Yii::$app->session->get('userId');
        $phoneFull = Yii::$app->session->get('phoneFull');
        if (!$userId && $phoneFull) {
            return $this->processPhone($phoneFull);
        }
        if ($userId && ($user = User::findActiveCustomerById($userId))) {
            return $user->isAgeConfirmed() ? $this->renderPaymentForm($user) : $this->renderAgeConfirmationForm($user, $phoneFull, $pageParams);
        }
        
        return $this->redirect(Url::to(['webpage', 'id' => $pageParams['webpage']->id, 'type' => 'pupil']));
    }

    private function renderPaymentForm(User $user)
    {
        $pageParams = $this->getPageParams('pupil');
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
                    'phone' => $phone ? Phone::getPhoneFormatted($phone) : null
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
        if (ComponentContainer::getAgeValidator()->add($phoneFull, 7, $users)) {
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
        $groupPupils = [];
        if ($paymentLink) {
            $groupPupils = GroupPupil::findAll(['group_id' => $paymentLink->group_id, 'user_id' => $paymentLink->user_id]);
        }
        $params = $this->getPageParams('pupil');
        $params['paymentLink'] = $paymentLink;
        $params['groupPupils'] = $groupPupils;

        return $this->render('link', $params);
    }

    public function actionCreate()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $pupilId = Yii::$app->request->post('pupil');
        $groupId = Yii::$app->request->post('group');
        $amount = intval(Yii::$app->request->post('amount'));
        $paymentMethodId = intval(Yii::$app->request->post('method', 0));

        if (!$pupilId) return self::getJsonErrorResult('No pupil ID');
        if (!$groupId) return self::getJsonErrorResult('No pupil ID');
        if ($amount < 1000) return self::getJsonErrorResult('Wrong payment amount');

        /** @var User $pupil */
        $pupil = User::find()->andWhere(['id' => $pupilId, 'role' => User::ROLE_PUPIL, 'status' => User::STATUS_ACTIVE])->one();
        /** @var Group $group */
        $group = Group::find()->andWhere(['id' => $groupId, 'active' => Group::STATUS_ACTIVE])->one();

        if (!$pupil) return self::getJsonErrorResult('Pupil not found');
        if (!$group) return self::getJsonErrorResult('Group not found');

        $groupPupil = GroupPupil::find()->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id, 'active' => GroupPupil::STATUS_ACTIVE])->one();
        if (!$groupPupil) return self::getJsonErrorResult('Для этого студента внесение оплаты невозможно');

        try {
            $contract = MoneyComponent::addPupilContract(
                Company::findOne(Company::COMPANY_EXCLUSIVE_ID),
                $pupil,
                $amount,
                $group
            );

            $redirectUrl = null;
            $returnUrl = urlencode(Url::to(['payment/complete', 'payment' => $contract->id], true));
            switch ($paymentMethodId) {
                case Contract::PAYMENT_TYPE_ATMOS:
                    $paymoApi = ComponentContainer::getPaymoApi();
                    $paymoId = $paymoApi->payCreate($contract->amount, $contract->number, [
                        'студент' => $pupil->name,
                        'группа' => $group->legal_name,
                        'занятий' => intval(round($contract->amount / ($contract->discount ? $group->lesson_price_discount : $group->lesson_price))),
                    ]);
                    $contract->payment_type = Contract::PAYMENT_TYPE_ATMOS;
                    $contract->external_id = $paymoId;
                    $contract->status = Contract::STATUS_PROCESS;
                    $redirectUrl = "$paymoApi->paymentUrl/invoice/get?storeId=$paymoApi->storeId&transactionId=$paymoId&redirectLink=$returnUrl";
                    break;
                case Contract::PAYMENT_TYPE_CLICK:
                    $redirectUrl = ComponentContainer::getClickApi()->payCreate($contract->amount, $contract->number, $returnUrl);
                    $contract->payment_type = Contract::PAYMENT_TYPE_CLICK;
                    break;
                case Contract::PAYMENT_TYPE_PAYME:
                    $redirectUrl = ComponentContainer::getPaymeApi()->payCreate($contract->amount, $contract->number, $returnUrl);
                    $contract->payment_type = Contract::PAYMENT_TYPE_PAYME;
                    $contract->status = Contract::STATUS_PROCESS;
                    break;
                default:
                    return self::getJsonErrorResult('Wrong payment method');
            }
            
            if (!$contract->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('payment/create', print_r($contract->getErrors(), true), true);
                return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
            }
            return self::getJsonOkResult(['redirectUrl' => $redirectUrl]);
        } catch (PaymoApiException $exception) {
            ComponentContainer::getErrorLogger()
                ->logError('payment/create', 'Paymo: ' . $exception->getMessage(), true);
            return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
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

        if (!isset($giftCardData['pupil_name'])) return self::getJsonErrorResult('No pupil name');
        if (!isset($giftCardData['pupil_phone'])) return self::getJsonErrorResult('No pupil phone');
        if (!isset($giftCardData['type'])) return self::getJsonErrorResult('No payment type selected');
        if (!isset($giftCardData['email'])) return self::getJsonErrorResult('No email');
        $giftCardType = GiftCardType::findOne(['id' => $giftCardData['type'], 'active' => GiftCardType::STATUS_ACTIVE]);
        if (!$giftCardType) return self::getJsonErrorResult('Unknown type');

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $giftCard = new GiftCard();
            $giftCard->name = $giftCardType->name;
            $giftCard->amount = $giftCardType->amount;
            $giftCard->status = GiftCard::STATUS_NEW;
            $giftCard->customer_name = $giftCardData['pupil_name'];
            $giftCard->phoneFormatted = $giftCardData['pupil_phone'];
            $giftCard->customer_email = $giftCardData['email'];
            $additionalData = ['payment_method' => (int)$paymentMethodId];
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

            $redirectUrl = null;
            $returnUrl = urlencode(Url::to(['payment/complete', 'gc' => $giftCard->code], true));
            switch ($paymentMethodId) {
                case Contract::PAYMENT_TYPE_ATMOS:
                    $paymoApi = ComponentContainer::getPaymoApi();
                    $paymoId = $paymoApi->payCreate($giftCard->amount, "gc-$giftCard->id", [
                        'студент' => $giftCard->customer_name,
                        'предмет' => $giftCard->name,
                    ]);
                    $redirectUrl = "$paymoApi->paymentUrl/invoice/get?storeId=$paymoApi->storeId&transactionId=$paymoId&redirectLink=$returnUrl";
                    break;
                case Contract::PAYMENT_TYPE_CLICK:
                    $redirectUrl = ComponentContainer::getClickApi()->payCreate($giftCard->amount, "gc-$giftCard->id", $returnUrl);
                    break;
                case Contract::PAYMENT_TYPE_PAYME:
                    $redirectUrl = ComponentContainer::getPaymeApi()->payCreate($giftCard->amount, "gc-$giftCard->id", $returnUrl);
                    break;
                default:
                    return self::getJsonErrorResult('Wrong payment method');
            }
            
            $transaction->commit();
            return self::getJsonOkResult(['redirectUrl' => $redirectUrl]);
        } catch (PaymoApiException $exception) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('payment/create-new', 'Paymo: ' . $exception->getMessage(), true);
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
                $params['success'] = $contract->status == Contract::STATUS_PAID;
                $params['amount'] = $contract->amount;
                $params['group'] = $contract->group->legal_name;
                $params['discount'] = $contract->discount == Contract::STATUS_ACTIVE;
                $params['lessons'] = intval(round($contract->amount / ($contract->discount ? $contract->group->lesson_price_discount : $contract->group->lesson_price)));
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
