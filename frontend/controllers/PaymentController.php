<?php

namespace frontend\controllers;

use common\components\ComponentContainer;
use common\components\extended\Controller;
use common\components\MoneyComponent;
use common\components\paymo\PaymoApiException;
use common\models\Company;
use common\models\Contract;
use common\models\Group;
use common\models\GroupPupil;
use common\models\PaymentLink;
use common\models\User;
use himiklab\yii2\recaptcha\ReCaptchaValidator;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class PaymentController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionFind()
    {
        $validator = new ReCaptchaValidator();

        if (!$validator->validate(\Yii::$app->request->post('reCaptcha'), $error)) {
            \Yii::$app->session->addFlash('error', 'Проверка на робота не пройдена');
            return $this->render('index');
        } else {
            $phoneFull = '+998' . substr(preg_replace('#\D#', '', \Yii::$app->request->post('phoneFormatted')), -9);
            /** @var User[] $users */
            $users = User::find()
                ->andWhere(['or', ['phone' => $phoneFull], ['phone2' => $phoneFull]])
                ->andWhere(['role' => [User::ROLE_PARENTS, User::ROLE_COMPANY, User::ROLE_PUPIL]])
                ->all();
            if (count($users) == 0) {
                \Yii::$app->session->addFlash('error', 'По данному номеру студенты не найдены');
                return $this->render('index');
            } else {
                $params = ['user' => null, 'users' => []];
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

        return $this->render('link', ['paymentLink' => $paymentLink, 'groupPupils' => $groupPupils]);
    }

    public function actionCreate()
    {
        if (!\Yii::$app->request->isAjax) throw new BadRequestHttpException('Wrong request');
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $pupilId = \Yii::$app->request->post('pupil');
        $groupId = \Yii::$app->request->post('group');
        $amount = intval(\Yii::$app->request->post('amount'));

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

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $contract = MoneyComponent::addPupilContract(
                Company::findOne(Company::COMPANY_SUPER_ID),
                $pupil,
                $amount,
                null,
                $group
            );
            $contract->status = Contract::STATUS_PROCESS;
            $contract->payment_type = Contract::PAYMENT_TYPE_PAYMO;

            $paymoId = ComponentContainer::getPaymoApi()->payCreate($contract->amount * 100, $contract->number, [
                'студент' => $pupil->name,
                'группа' => $group->legal_name,
                'занятий' => intval(round($contract->amount / ($contract->discount ? $group->lesson_price_discount : $group->lesson_price))),
            ]);
            $contract->external_id = $paymoId;
            if (!$contract->save()) {
                $transaction->rollBack();
                ComponentContainer::getErrorLogger()
                    ->logError('payment/create', print_r($contract->getErrors(), true), true);
                return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
            }
            $transaction->commit();
            return self::getJsonOkResult([
                'payment_url' => ComponentContainer::getPaymoApi()->paymentUrl,
                'payment_id' => $contract->external_id,
                'store_id' => ComponentContainer::getPaymoApi()->storeId,
                'redirect_link' => urlencode(Url::to(['payment/complete', 'payment' => $contract->id], true)),
            ]);
        } catch (PaymoApiException $exception) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('payment/create', 'Paymo: ' . $exception->getMessage(), true);
            return self::getJsonErrorResult('Произошла ошибка, оплата не может быть зарегистрирована');
        }
    }

    public function actionComplete()
    {
        $params = [];
        if ($contractId = \Yii::$app->request->get('payment')) {
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
}
