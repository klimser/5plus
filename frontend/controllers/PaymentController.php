<?php

namespace frontend\controllers;

use common\components\ComponentContainer;
use common\components\extended\Controller;
use common\components\MoneyComponent;
use common\components\paymo\PaymoApiException;
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
use himiklab\yii2\recaptcha\ReCaptchaValidator;
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

    public function actionIndex()
    {
        $type = Yii::$app->request->get('type');
        if (!in_array($type, ['new', 'pupil'])) {
            $type = null;
        }
        return $this->render('index' . ($type ? "-$type" : ''), $this->getPageParams($type));
    }

    public function actionFind()
    {
        $validator = new ReCaptchaValidator();

        if (!$validator->validate(Yii::$app->request->post('reCaptcha'))) {
            Yii::$app->session->addFlash('error', 'Проверка на робота не пройдена');
            return $this->render('index', $this->getPageParams('pupil'));
        } else {
            $phoneFull = '+998' . substr(preg_replace('#\D#', '', Yii::$app->request->post('phoneFormatted')), -9);
            /** @var User[] $users */
            $users = User::find()
                ->andWhere(['or', ['phone' => $phoneFull], ['phone2' => $phoneFull]])
                ->andWhere(['role' => [User::ROLE_PARENTS, User::ROLE_COMPANY, User::ROLE_PUPIL]])
                ->all();
            if (count($users) == 0) {
                Yii::$app->session->addFlash('error', 'По данному номеру студенты не найдены');
                return $this->render('index', $this->getPageParams('pupil'));
            } else {
                $params = $this->getPageParams('pupil');
                $params['user'] = null;
                $params['users'] = [];
                if (count($users) == 1) {
                    $user = reset($users);
                    if ($user->role == User::ROLE_PUPIL) $params['user'] = $user;
                    else {
                        if (count($user->children) == 1) $params['user'] = reset($user->children);
                        else $params['users'] = $user->children;
                    }
                } else $params['users'] = $users;

                return $this->render('find', $params);
            }
        }
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
        if (!Yii::$app->request->isAjax) throw new BadRequestHttpException('Wrong request');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $pupilId = Yii::$app->request->post('pupil');
        $groupId = Yii::$app->request->post('group');
        $amount = intval(Yii::$app->request->post('amount'));

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
                Company::findOne(Company::COMPANY_SUPER_ID),
                $pupil,
                $amount,
                $group
            );

            $paymoId = ComponentContainer::getPaymoApi()->payCreate($contract->amount * 100, $contract->number, [
                'студент' => $pupil->name,
                'группа' => $group->legal_name,
                'занятий' => intval(round($contract->amount / ($contract->discount ? $group->lesson_price_discount : $group->lesson_price))),
            ]);
            $contract->payment_type = Contract::PAYMENT_TYPE_PAYMO;
            $contract->external_id = $paymoId;
            $contract->status = Contract::STATUS_PROCESS;
            if (!$contract->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('payment/create', print_r($contract->getErrors(), true), true);
                return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
            }
            return self::getJsonOkResult([
                'payment_url' => ComponentContainer::getPaymoApi()->paymentUrl,
                'payment_id' => $contract->external_id,
                'store_id' => ComponentContainer::getPaymoApi()->storeId,
                'redirect_link' => urlencode(Url::to(['payment/complete', 'payment' => $contract->id], true)),
            ]);
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
        if (!Yii::$app->request->isAjax) throw new BadRequestHttpException('Wrong request');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $giftCardData = Yii::$app->request->post('giftcard', []);

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
            if ($giftCardData['parents_name'] && $giftCardData['parents_phone']) {
                $giftCard->additionalData = [
                    'parents_name' => $giftCardData['parents_name'],
                    'parents_phone' => $giftCardData['parents_phone'],
                ];
            }

            if (!$giftCard->save()) {
                $transaction->rollBack();
                ComponentContainer::getErrorLogger()
                    ->logError('payment/create-new', print_r($giftCard->getErrors(), true), true);
                return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
            }

            $paymoId = ComponentContainer::getPaymoApi()->payCreate($giftCard->amount * 100, "gc-{$giftCard->id}", [
                'студент' => $giftCard->customer_name,
                'предмет' => $giftCard->name,
            ]);

            $transaction->commit();
            return self::getJsonOkResult([
                'payment_url' => ComponentContainer::getPaymoApi()->paymentUrl,
                'payment_id' => $paymoId,
                'store_id' => ComponentContainer::getPaymoApi()->storeId,
                'redirect_link' => urlencode(Url::to(['payment/complete', 'gc' => $giftCard->code], true)),
            ]);
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
