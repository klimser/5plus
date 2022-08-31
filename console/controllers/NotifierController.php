<?php

namespace console\controllers;

use backend\components\TranslitComponent;
use common\components\ComponentContainer;
use common\components\helpers\WordForm;
use common\components\PaymentComponent;
use common\components\SmsBroker\SmsBrokerApiException;
use common\components\telegram\text\PublicMain;
use common\models\BotPush;
use common\models\CourseStudent;
use common\models\Notify;
use common\models\User;
use DateTime;
use Longman\TelegramBot\Entities\Entity;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

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
     * @throws \Exception
     */
    public function actionSend()
    {
        $currentTime = intval(date('H'));
        if ($currentTime >= 20 || $currentTime < 9) return ExitCode::OK;

        $condition = ['status' => Notify::STATUS_NEW];

        $tryTelegram = array_key_exists('telegramPublic', Yii::$app->components);

        $quantity = 0;
        $startTime = microtime(true);
        while ($quantity < self::QUANTITY_LIMIT && microtime(true) - $startTime < self::TIME_LIMIT) {            
            $toSend = Notify::findOne($condition);
            if (!$toSend) {
                sleep(10);
                continue;
            }

            $toSend->status = Notify::STATUS_SENDING;
            $toSend->save();

            $sendSms = true;
            if ($tryTelegram && $toSend->user->tg_chat_id && $toSend->user->telegramSettings['subscribe']) {
                $message = null;
                switch ($toSend->template_id) {
                    case Notify::TEMPLATE_PUPIL_DEBT:
                        $message = 'У вас задолженность в группе *' . Entity::escapeMarkdownV2($toSend->group->legal_name) . '*'
                            . Entity::escapeMarkdownV2(" - {$toSend->parameters['debt']} " . WordForm::getLessonsForm($toSend->parameters['debt']) . '.')
                            . ' [' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($toSend->user_id, $toSend->group_id)->url . ')';
                        break;
                    case Notify::TEMPLATE_PUPIL_LOW:
                        $message = 'В группе *' . Entity::escapeMarkdownV2($toSend->group->legal_name) . '*'
                            . Entity::escapeMarkdownV2(" у вас осталось {$toSend->parameters['paid_lessons']} " . WordForm::getLessonsForm($toSend->parameters['paid_lessons']) . '.')
                            . ' [' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($toSend->user_id, $toSend->group_id)->url . ')';
                        break;
                    case Notify::TEMPLATE_PARENT_DEBT:
                        $child = User::findOne($toSend->parameters['child_id']);
                        $message = 'У студента ' . Entity::escapeMarkdownV2($toSend->user->telegramSettings['trusted'] ? $child->name : $child->nameHidden)
                            . ' задолженность в группе *' . Entity::escapeMarkdownV2($toSend->group->legal_name) . '*'
                            . Entity::escapeMarkdownV2(" - {$toSend->parameters['debt']} " . WordForm::getLessonsForm($toSend->parameters['debt']) . '.')
                            . ' [' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($child->id, $toSend->group_id)->url . ')';
                        break;
                    case Notify::TEMPLATE_PARENT_LOW:
                        $child = User::findOne($toSend->parameters['child_id']);
                        $message = 'У студента ' . Entity::escapeMarkdownV2($toSend->user->telegramSettings['trusted'] ? $child->name : $child->nameHidden)
                            . ' в группе *' . Entity::escapeMarkdownV2($toSend->group->legal_name) . '*'
                            . Entity::escapeMarkdownV2(" осталось {$toSend->parameters['paid_lessons']} " . WordForm::getLessonsForm($toSend->parameters['paid_lessons']) . '.')
                            . ' [' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($child->id, $toSend->group_id)->url . ')';
                        break;
                }
                if ($message) {
                    $sendSms = false;
                    $push = new BotPush();
                    $push->chat_id = $toSend->user->tg_chat_id;
                    $push->messageArray = [
                        'text' => $message,
                        'parse_mode' => 'MarkdownV2',
                        'disable_web_page_preview' => true,
                    ];
                    if ($push->save()) {
                        $toSend->status = Notify::STATUS_SENT;
                        $toSend->sent_at = date('Y-m-d H:i:s');
                    } else {
                        ComponentContainer::getErrorLogger()->logError('notify/send', $push->getErrorsAsString(), true);
                        $toSend->status = Notify::STATUS_ERROR;
                    }
                    $toSend->save();
                }
            }

            if ($sendSms) {
                try {
                    $smsText = '';
                    switch ($toSend->template_id) {
                        case Notify::TEMPLATE_PUPIL_DEBT:
                            $smsText = sprintf(
                                'U vas zadolzhennost v gruppe "%s" - %s. Oplata online - %s',
                                TranslitComponent::text($toSend->group->legal_name),
                                $toSend->parameters['debt'] . ' ' . TranslitComponent::text(WordForm::getLessonsForm($toSend->parameters['debt'])),
                                PaymentComponent::getPaymentLink($toSend->user_id, $toSend->group_id)->url
                            );
                            break;
                        case Notify::TEMPLATE_PUPIL_LOW:
                            $smsText = sprintf(
                                'V gruppe "%s" u vas ostalos %s. Oplata online - %s',
                                TranslitComponent::text($toSend->group->legal_name),
                                $toSend->parameters['paid_lessons'] . ' ' . TranslitComponent::text(WordForm::getLessonsForm($toSend->parameters['paid_lessons'])),
                                PaymentComponent::getPaymentLink($toSend->user_id, $toSend->group_id)->url
                            );
                            break;
                        case Notify::TEMPLATE_PARENT_DEBT:
                            $child = User::findOne($toSend->parameters['child_id']);
                            $smsText = sprintf(
                                'U studenta %s zadolzhennost v gruppe "%s" - %s. Oplata online - %s',
                                TranslitComponent::text($child->name),
                                TranslitComponent::text($toSend->group->legal_name),
                                $toSend->parameters['debt'] . ' ' . TranslitComponent::text(WordForm::getLessonsForm($toSend->parameters['debt'])),
                                PaymentComponent::getPaymentLink($child->id, $toSend->group_id)->url
                            );
                            break;
                        case Notify::TEMPLATE_PARENT_LOW:
                            $child = User::findOne($toSend->parameters['child_id']);
                            $smsText = sprintf(
                            'U studenta %s v gruppe "%s" ostalos %s. Oplata online - %s',
                                TranslitComponent::text($child->name),
                                TranslitComponent::text($toSend->group->legal_name),
                                $toSend->parameters['paid_lessons'] . ' ' . TranslitComponent::text(WordForm::getLessonsForm($toSend->parameters['paid_lessons'])),
                                PaymentComponent::getPaymentLink($child->id, $toSend->group_id)->url
                            );
                            break;
                    }

                    if ($toSend->user->phone) {
                        ComponentContainer::getSmsBrokerApi()->sendSingleMessage(
                            substr($toSend->user->phone, -12, 12),
                            $smsText,
                            'fsn' . $toSend->user->id . '_' . time()
                        );
                    }
                    $toSend->status = Notify::STATUS_SENT;
                    $toSend->sent_at = date('Y-m-d H:i:s');
                } catch (SmsBrokerApiException $exception) {
                    $toSend->status = Notify::STATUS_ERROR;
                    ComponentContainer::getErrorLogger()
                        ->logError('notifier/send', $exception->getMessage(), true);
                }
                $toSend->save();
                $quantity++;
            }
        }
        return ExitCode::OK;
    }

    /**
     * Create notifications when needed
     * @return int
     */
    public function actionCreate()
    {
        $monthLimit = new DateTime('-30 days');

        /** @var CourseStudent[] $groupPupils */
        $groupPupils = CourseStudent::find()
            ->joinWith('user')
            ->andWhere([CourseStudent::tableName() . '.active' => CourseStudent::STATUS_ACTIVE])
            ->andWhere(['<', CourseStudent::tableName() . '.paid_lessons', 0])
            ->andWhere(['!=', User::tableName() . '.status', User::STATUS_LOCKED])
            ->with('group')
            ->all();
        foreach ($groupPupils as $groupPupil) {

            /*----------------------  TEMPLATE ID 1 ---------------------------*/
            /** @var Notify $queuedNotification */
            $queuedNotification = Notify::find()
                ->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupPupil->group_id, 'template_id' => Notify::TEMPLATE_PUPIL_DEBT])
                ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                ->one();
            if (!$queuedNotification) {
                /** @var Notify[] $sentNotifications */
                $sentNotifications = Notify::find()
                    ->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupPupil->group_id, 'template_id' => Notify::TEMPLATE_PUPIL_DEBT])
                    ->andWhere(['status' => Notify::STATUS_SENT])
                    ->andWhere(['>', 'sent_at', $monthLimit->format('Y-m-d H:i:s')])
                    ->orderBy(['sent_at' => SORT_DESC])
                    ->all();
                $needSent = true;
                if (!empty($sentNotifications)) {
                    $lastNotification = reset($sentNotifications);
                    $needSent = (date_diff(new DateTime('now'), $lastNotification->sentDate)->days >= pow(2, count($sentNotifications) - 1));
                }

                if ($needSent) {
                    $lessonDebt = CourseStudent::find()
                        ->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupPupil->group_id])
                        ->andWhere(['<', 'paid_lessons', 0])
                        ->select('SUM(paid_lessons)')
                        ->scalar();
                    ComponentContainer::getNotifyQueue()->add(
                        $groupPupil->user,
                        Notify::TEMPLATE_PUPIL_DEBT,
                        ['debt' => abs($lessonDebt)],
                        $groupPupil->group
                    );
                }
            }
            /*----------------------  END TEMPLATE ID 1 ---------------------------*/

            /*----------------------  TEMPLATE ID 2 ---------------------------*/
            if ($groupPupil->user->parent_id) {
                $parent = $groupPupil->user->parent;
                /** @var Notify[] $queuedNotificationsDraft */
                $queuedNotificationsDraft = Notify::find()
                    ->andWhere(['user_id' => $parent->id, 'group_id' => $groupPupil->group_id, 'template_id' => Notify::TEMPLATE_PARENT_DEBT])
                    ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                    ->all();
                $queuedNotifications = [];
                foreach ($queuedNotificationsDraft as $notification) {
                    if ($notification->parameters['child_id'] == $groupPupil->user_id) $queuedNotifications[] = $notification;
                }

                if (empty($queuedNotifications)) {
                    /** @var Notify[] $sentNotificationsDraft */
                    $sentNotificationsDraft = Notify::find()
                        ->andWhere(['user_id' => $parent->id, 'group_id' => $groupPupil->group_id, 'template_id' => Notify::TEMPLATE_PARENT_DEBT])
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
                        $needSent = (date_diff(new DateTime('now'), $lastNotification->sentDate)->days >= pow(2, count($sentNotifications) - 1));
                    }

                    if ($needSent) {
                        $lessonDebt = CourseStudent::find()
                            ->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupPupil->group_id])
                            ->andWhere(['<', 'paid_lessons', 0])
                            ->select('SUM(paid_lessons)')
                            ->scalar();
                        ComponentContainer::getNotifyQueue()->add(
                            $parent,
                            Notify::TEMPLATE_PARENT_DEBT,
                            ['debt' => abs($lessonDebt), 'child_id' => $groupPupil->user_id],
                            $groupPupil->group
                        );
                    }
                }
            }
            /*----------------------  END TEMPLATE ID 2 ---------------------------*/
        }

        $nextWeek = new DateTime('+7 days');
        /** @var CourseStudent[] $groupPupils */
        $groupPupils = CourseStudent::find()
            ->joinWith('user')
            ->andWhere([CourseStudent::tableName() . '.active' => CourseStudent::STATUS_ACTIVE])
            ->andWhere(['BETWEEN', CourseStudent::tableName() . '.paid_lessons', 0, 2])
            ->andWhere(['!=', User::tableName() . '.status', User::STATUS_LOCKED])
            ->andWhere([
                'or',
                [CourseStudent::tableName() . '.date_end' => null],
                ['>', CourseStudent::tableName() . '.date_end', $nextWeek->format('Y-m-d')]
            ])
            ->with('group')
            ->all();
        foreach ($groupPupils as $groupPupil) {

            /*----------------------  TEMPLATE ID 3 ---------------------------*/
            $queuedNotification = Notify::find()
                ->andWhere([
                    'user_id' => $groupPupil->user_id,
                    'group_id' => $groupPupil->group_id,
                    'template_id' => [Notify::TEMPLATE_PUPIL_LOW, Notify::TEMPLATE_PUPIL_DEBT],
                ])
                ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                ->one();
            if (!$queuedNotification) {
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
                    $needSent = (date_diff(new DateTime('now'), $lastNotification->sentDate)->days >= pow(2, count($sentNotifications)));
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
                        $needSent = (date_diff(new DateTime('now'), $lastNotification->sentDate)->days >= pow(2, count($sentNotifications)));
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

        return ExitCode::OK;
    }
}
