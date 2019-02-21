<?php

namespace console\controllers;

use common\components\ComponentContainer;
use common\components\helpers\WordForm;
use common\components\paygram\PaygramApiException;
use common\components\PaymentComponent;
use common\components\telegram\Request;
use common\models\Debt;
use common\models\GroupPupil;
use common\models\Notify;
use common\models\User;
use yii;
use yii\console\Controller;

/**
 * NotifierController is used to send notifications to users.
 */
class NotifierController extends Controller
{
    const QUANTITY_LIMIT = 40;
    const TIME_LIMIT = 50;
    /**
     * Search for a not sent notifications and sends it.
     * @return int
     */
    public function actionSend()
    {
        $currentTime = intval(date('H'));
        if ($currentTime >= 20 || $currentTime < 9) return yii\console\ExitCode::OK;

        $condition = ['status' => Notify::STATUS_NEW];

        $tryTelegram = false;
        if (array_key_exists('telegramPublic', \Yii::$app->components)) {
            \Yii::$app->db->open();
            ComponentContainer::getTelegramPublic()->telegram;
            $tryTelegram = true;
        }

        $quantity = 0;
        $startTime = microtime(true);
        while (true) {
            $toSend = Notify::findOne($condition);
            if (!$toSend) break;

            $toSend->status = Notify::STATUS_SENDING;
            $toSend->save();

            $sendSms = true;
            if ($tryTelegram && $toSend->user->tg_chat_id) {
                $message = null;
                switch ($toSend->template_id) {
                    case Notify::TEMPLATE_PUPIL_DEBT:
                        $message = "У вас задолженность в группе \"{$toSend->group->legal_name}\" - {$toSend->parameters['debt']} " . WordForm::getLessonsForm($toSend->parameters['debt'])
                            . '. [Оплатить онлайн](' . PaymentComponent::getPaymentLink($toSend->user_id, $toSend->group_id)->url . ')';
                        break;
                    case Notify::TEMPLATE_PUPIL_LOW:
                        $message = "В группе \"{$toSend->group->legal_name}\" у вас осталось {$toSend->parameters['paid_lessons']} " . WordForm::getLessonsForm($toSend->parameters['paid_lessons'])
                            . ' [Оплатить онлайн](' . PaymentComponent::getPaymentLink($toSend->user_id, $toSend->group_id)->url . ')';
                        break;
                    case Notify::TEMPLATE_PARENT_DEBT:
                        $child = User::findOne($toSend->parameters['child_id']);
                        $message = "У студента {$child->name} задолженность в группе \"{$toSend->group->legal_name}\" - {$toSend->parameters['debt']} " . WordForm::getLessonsForm($toSend->parameters['debt'])
                            . '. [Оплатить онлайн](' . PaymentComponent::getPaymentLink($child->id, $toSend->group_id)->url . ')';
                        break;
                    case Notify::TEMPLATE_PARENT_LOW:
                        $child = User::findOne($toSend->parameters['child_id']);
                        $message = "У студента {$child->name} в группе \"{$toSend->group->legal_name}\" осталось {$toSend->parameters['paid_lessons']} " . WordForm::getLessonsForm($toSend->parameters['paid_lessons'])
                            . ' [Оплатить онлайн](' . PaymentComponent::getPaymentLink($child->id, $toSend->group_id)->url . ')';
                        break;
                }
                if ($message) {
                    /** @var \Longman\TelegramBot\Entities\ServerResponse $response */
                    $response = Request::sendMessage([
                        'chat_id' => $toSend->user->tg_chat_id,
                        'parse_mode' => 'Markdown',
                        'text' => $message
                    ]);
                    if ($response->isOk()) {
                        $sendSms = false;
                        $toSend->status = Notify::STATUS_SENT;
                        $toSend->save();
                    }
                }
            }

            if ($sendSms) {
                try {
                    $params = [];
                    switch ($toSend->template_id) {
                        case Notify::TEMPLATE_PUPIL_DEBT:
                            $params['group_name'] = $toSend->group->legal_name;
                            $params['debt'] = $toSend->parameters['debt'] . ' ' . WordForm::getLessonsForm($toSend->parameters['debt']);
                            $params['link'] = PaymentComponent::getPaymentLink($toSend->user_id, $toSend->group_id)->url;
                            break;
                        case Notify::TEMPLATE_PUPIL_LOW:
                            $params['group_name'] = $toSend->group->legal_name;
                            $params['paid_lessons'] = $toSend->parameters['paid_lessons'] . ' ' . WordForm::getLessonsForm($toSend->parameters['paid_lessons']);
                            $params['link'] = PaymentComponent::getPaymentLink($toSend->user_id, $toSend->group_id)->url;
                            break;
                        case Notify::TEMPLATE_PARENT_DEBT:
                            $child = User::findOne($toSend->parameters['child_id']);
                            $params['student_name'] = $child->name;
                            $params['group_name'] = $toSend->group->legal_name;
                            $params['debt'] = $toSend->parameters['debt'] . ' ' . WordForm::getLessonsForm($toSend->parameters['debt']);
                            $params['link'] = PaymentComponent::getPaymentLink($child->id, $toSend->group_id)->url;
                            break;
                        case Notify::TEMPLATE_PARENT_LOW:
                            $child = User::findOne($toSend->parameters['child_id']);
                            $params['student_name'] = $child->name;
                            $params['group_name'] = $toSend->group->legal_name;
                            $params['paid_lessons'] = $toSend->parameters['paid_lessons'] . ' ' . WordForm::getLessonsForm($toSend->parameters['paid_lessons']);
                            $params['link'] = PaymentComponent::getPaymentLink($child->id, $toSend->group_id)->url;
                            break;
                    }

                    if ($toSend->user->phone) {
                        ComponentContainer::getPaygramApi()
                            ->sendSms($toSend->template_id, substr($toSend->user->phone, -12, 12), $params);
                    }
                    $toSend->status = Notify::STATUS_SENT;
                } catch (PaygramApiException $exception) {
                    $toSend->status = Notify::STATUS_ERROR;
                    ComponentContainer::getErrorLogger()
                        ->logError('notifier/send', $exception->getMessage(), true);
                }
                $toSend->save();
            }

            $quantity++;
            $currentTime = microtime(true);
            if($quantity >= self::QUANTITY_LIMIT || $currentTime - $startTime > self::TIME_LIMIT) break;
        }
        return yii\console\ExitCode::OK;
    }

    /**
     * Create notifications when needed
     * @return int
     */
    public function actionCreate()
    {
        $monthLimit = new \DateTime('-30 days');

        /** @var Debt[] $debts */
        $debts = Debt::find()
            ->joinWith('user.activeGroupPupils', true, 'INNER JOIN')
            ->andWhere(['!=', User::tableName() . '.status', User::STATUS_LOCKED])
            ->select(Debt::tableName() . '.*')
            ->distinct(true)
            ->with(['user', 'group'])
            ->all();
        foreach ($debts as $debt) {

            /*----------------------  TEMPLATE ID 1 ---------------------------*/
            $queuedNotifications = Notify::find()
                ->andWhere(['user_id' => $debt->user_id, 'group_id' => $debt->group_id, 'template_id' => Notify::TEMPLATE_PUPIL_DEBT])
                ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                ->one();
            if (empty($queuedNotifications)) {
                /** @var Notify[] $sentNotifications */
                $sentNotifications = Notify::find()
                    ->andWhere(['user_id' => $debt->user_id, 'group_id' => $debt->group_id, 'template_id' => Notify::TEMPLATE_PUPIL_DEBT])
                    ->andWhere(['status' => Notify::STATUS_SENT])
                    ->andWhere(['>', 'sent_at', $monthLimit->format('Y-m-d H:i:s')])
                    ->orderBy(['sent_at' => SORT_DESC])
                    ->all();
                $needSent = true;
                if (!empty($sentNotifications)) {
                    $lastNotification = reset($sentNotifications);
                    $needSent = (date_diff(new \DateTime('now'), $lastNotification->sentDate)->days >= pow(2, count($sentNotifications) - 1));
                }

                if ($needSent) {
                    $lessonDebt = GroupPupil::find()
                        ->andWhere(['user_id' => $debt->user_id, 'group_id' => $debt->group_id])
                        ->andWhere(['<', 'paid_lessons', 0])
                        ->select('SUM(paid_lessons)')
                        ->scalar();
                    ComponentContainer::getNotifyQueue()->add(
                        $debt->user,
                        Notify::TEMPLATE_PUPIL_DEBT,
                        ['debt' => abs($lessonDebt)],
                        $debt->group
                    );
                }
            }
            /*----------------------  END TEMPLATE ID 1 ---------------------------*/

            /*----------------------  TEMPLATE ID 2 ---------------------------*/
            if ($debt->user->parent_id) {
                $parent = $debt->user->parent;
                /** @var Notify[] $queuedNotificationsDraft */
                $queuedNotificationsDraft = Notify::find()
                    ->andWhere(['user_id' => $parent->id, 'group_id' => $debt->group_id, 'template_id' => Notify::TEMPLATE_PARENT_DEBT])
                    ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                    ->all();
                $queuedNotifications = [];
                foreach ($queuedNotificationsDraft as $notification) {
                    if ($notification->parameters['child_id'] == $debt->user_id) $queuedNotifications[] = $notification;
                }

                if (empty($queuedNotifications)) {
                    /** @var Notify[] $sentNotificationsDraft */
                    $sentNotificationsDraft = Notify::find()
                        ->andWhere(['user_id' => $parent->id, 'group_id' => $debt->group_id, 'template_id' => Notify::TEMPLATE_PARENT_DEBT])
                        ->andWhere(['status' => Notify::STATUS_SENT])
                        ->andWhere(['>', 'sent_at', $monthLimit->format('Y-m-d H:i:s')])
                        ->orderBy(['sent_at' => SORT_DESC])
                        ->all();
                    /** @var Notify[] $sentNotifications */
                    $sentNotifications = [];
                    foreach ($sentNotificationsDraft as $notification) {
                        if ($notification->parameters['child_id'] == $debt->user_id) $sentNotifications[] = $notification;
                    }

                    $needSent = true;
                    if (!empty($sentNotifications)) {
                        $lastNotification = reset($sentNotifications);
                        $needSent = (date_diff(new \DateTime('now'), $lastNotification->sentDate)->days >= pow(2, count($sentNotifications) - 1));
                    }

                    if ($needSent) {
                        $lessonDebt = GroupPupil::find()
                            ->andWhere(['user_id' => $debt->user_id, 'group_id' => $debt->group_id])
                            ->andWhere(['<', 'paid_lessons', 0])
                            ->select('SUM(paid_lessons)')
                            ->scalar();
                        ComponentContainer::getNotifyQueue()->add(
                            $parent,
                            Notify::TEMPLATE_PARENT_DEBT,
                            ['debt' => abs($lessonDebt), 'child_id' => $debt->user_id],
                            $debt->group
                        );
                    }
                }
            }
            /*----------------------  END TEMPLATE ID 2 ---------------------------*/
        }

        /** @var GroupPupil[] $groupPupils */
        $groupPupils = GroupPupil::find()
            ->joinWith('user')
            ->andWhere([GroupPupil::tableName() . '.active' => GroupPupil::STATUS_ACTIVE])
            ->andWhere(['BETWEEN', GroupPupil::tableName() . '.paid_lessons', 0, 2])
            ->andWhere(['!=', User::tableName() . '.status', User::STATUS_LOCKED])
            ->with(['user', 'group'])
            ->all();
        foreach ($groupPupils as $groupPupil) {

            /*----------------------  TEMPLATE ID 3 ---------------------------*/
            $queuedNotifications = Notify::find()
                ->andWhere([
                    'user_id' => $groupPupil->user_id,
                    'group_id' => $groupPupil->group_id,
                    'template_id' => [Notify::TEMPLATE_PUPIL_LOW, Notify::TEMPLATE_PUPIL_DEBT],
                ])
                ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                ->one();
            if (empty($queuedNotifications)) {
                /** @var Notify[] $sentNotifications */
                $sentNotifications = Notify::find()
                    ->andWhere([
                        'user_id' => $groupPupil->user_id,
                        'group_id' => $groupPupil->group_id,
                        'template_id' => [Notify::TEMPLATE_PUPIL_LOW, Notify::TEMPLATE_PUPIL_DEBT],
                    ])
                    ->andWhere(['status' => Notify::STATUS_SENT])
                    ->andWhere(['>', 'sent_at', $monthLimit->format('Y-m-d H:i:s')])
                    ->orderBy(['sent_at' => SORT_DESC])
                    ->all();
                $needSent = true;
                if (!empty($sentNotifications)) {
                    $lastNotification = reset($sentNotifications);
                    $needSent = (date_diff(new \DateTime('now'), $lastNotification->sentDate)->days >= pow(2, count($sentNotifications)));
                }

                if ($needSent) {
                    ComponentContainer::getNotifyQueue()->add(
                        $groupPupil->user,
                        Notify::TEMPLATE_PUPIL_LOW,
                        ['paid_lessons' => $groupPupil->paid_lessons],
                        $groupPupil->group
                    );
                }
            }
            /*----------------------  END TEMPLATE ID 3 ---------------------------*/

            /*----------------------  TEMPLATE ID 4 ---------------------------*/
            if ($groupPupil->user->parent_id) {
                $parent = $groupPupil->user->parent;
                /** @var Notify[] $queuedNotificationsDraft */
                $queuedNotificationsDraft = Notify::find()
                    ->andWhere([
                        'user_id' => $parent->id,
                        'group_id' => $groupPupil->group_id,
                        'template_id' => [Notify::TEMPLATE_PARENT_LOW, Notify::TEMPLATE_PARENT_DEBT],
                    ])
                    ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                    ->all();
                $queuedNotifications = [];
                foreach ($queuedNotificationsDraft as $notification) {
                    if ($notification->parameters['child_id'] == $groupPupil->user_id) $queuedNotifications[] = $notification;
                }

                if (empty($queuedNotifications)) {
                    /** @var Notify[] $sentNotificationsDraft */
                    $sentNotificationsDraft = Notify::find()
                        ->andWhere([
                            'user_id' => $parent->id,
                            'group_id' => $groupPupil->group_id,
                            'template_id' => [Notify::TEMPLATE_PARENT_LOW, Notify::TEMPLATE_PARENT_DEBT],
                        ])
                        ->andWhere(['status' => Notify::STATUS_SENT])
                        ->andWhere(['>', 'sent_at', $monthLimit->format('Y-m-d H:i:s')])
                        ->orderBy(['sent_at' => SORT_DESC])
                        ->all();
                    /** @var Notify[] $sentNotifications */
                    $sentNotifications = [];
                    foreach ($sentNotificationsDraft as $notification) {
                        if ($notification->parameters['child_id'] == $groupPupil->user_id) $sentNotifications[] = $notification;
                    }
                    $needSent = true;
                    if (!empty($sentNotifications)) {
                        $lastNotification = reset($sentNotifications);
                        $needSent = (date_diff(new \DateTime('now'), $lastNotification->sentDate)->days >= pow(2, count($sentNotifications)));
                    }

                    if ($needSent) {
                        ComponentContainer::getNotifyQueue()->add(
                            $parent,
                            Notify::TEMPLATE_PARENT_LOW,
                            ['paid_lessons' => $groupPupil->paid_lessons, 'child_id' => $groupPupil->user_id],
                            $groupPupil->group
                        );
                    }
                }
            }
            /*----------------------  END TEMPLATE ID 4 ---------------------------*/
        }

        return yii\console\ExitCode::OK;
    }
}