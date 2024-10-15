<?php

namespace console\controllers;

use backend\components\TranslitComponent;
use backend\models\WelcomeLesson;
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
use Longman\TelegramBot\Request;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * NotifierController is used to send notifications to users.
 */
class NotifierController extends Controller
{
    const QUANTITY_LIMIT = 40;
    const TIME_LIMIT     = 50;

    /**
     * Search for a not sent notifications and sends it.
     *
     * @return int
     * @throws \Exception
     */
    public function actionSend()
    {
        $currentTime = intval(date('H'));
        if ($currentTime >= 20 || $currentTime < 9) {
            return ExitCode::OK;
        }

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
                    case Notify::TEMPLATE_STUDENT_DEBT:
                        $message = 'У вас задолженность в группе *' . Entity::escapeMarkdownV2($toSend->course->courseConfig->legal_name) . '*'
                            . Entity::escapeMarkdownV2(" - {$toSend->parameters['debt']} " . WordForm::getLessonsForm($toSend->parameters['debt']) . '.')
                            . ' [' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($toSend->user_id, $toSend->course_id)->url . ')';
                        break;
                    case Notify::TEMPLATE_STUDENT_LOW:
                        $message = 'В группе *' . Entity::escapeMarkdownV2($toSend->course->courseConfig->legal_name) . '*'
                            . Entity::escapeMarkdownV2(
                                " у вас осталось {$toSend->parameters['paid_lessons']} " . WordForm::getLessonsForm($toSend->parameters['paid_lessons']) . '.'
                            )
                            . ' [' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($toSend->user_id, $toSend->course_id)->url . ')';
                        break;
                    case Notify::TEMPLATE_PARENT_DEBT:
                        $child = User::findOne($toSend->parameters['child_id']);
                        $message = 'У студента ' . Entity::escapeMarkdownV2($toSend->user->telegramSettings['trusted'] ? $child->name : $child->nameHidden)
                            . ' задолженность в группе *' . Entity::escapeMarkdownV2($toSend->course->courseConfig->legal_name) . '*'
                            . Entity::escapeMarkdownV2(" - {$toSend->parameters['debt']} " . WordForm::getLessonsForm($toSend->parameters['debt']) . '.')
                            . ' [' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($child->id, $toSend->course_id)->url . ')';
                        break;
                    case Notify::TEMPLATE_PARENT_LOW:
                        $child = User::findOne($toSend->parameters['child_id']);
                        $message = 'У студента ' . Entity::escapeMarkdownV2($toSend->user->telegramSettings['trusted'] ? $child->name : $child->nameHidden)
                            . ' в группе *' . Entity::escapeMarkdownV2($toSend->course->courseConfig->legal_name) . '*'
                            . Entity::escapeMarkdownV2(" осталось {$toSend->parameters['paid_lessons']} " . WordForm::getLessonsForm($toSend->parameters['paid_lessons']) . '.')
                            . ' [' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($child->id, $toSend->course_id)->url . ')';
                        break;
                }
                if ($message) {
                    $sendSms = false;
                    $push = new BotPush();
                    $push->chat_id = $toSend->user->tg_chat_id;
                    $push->message_data = [
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
                    $courseName = TranslitComponent::text($toSend->course->courseConfig->legal_name);
                    $paymentLink = PaymentComponent::getPaymentLink($toSend->user_id, $toSend->course_id)->url;
                    switch ($toSend->template_id) {
                        case Notify::TEMPLATE_STUDENT_DEBT:
                            $smsText = sprintf(
                                'U vas zadolzhennost v gruppe "%s" - %s. Oplata online - %s'
                                . "\n" . 'Sizning "%s" guruhida "%d dars" qarzdorligingiz mavjud. Onlayn to\'lov - %s',
                                $courseName,
                                $toSend->parameters['debt'] . ' ' . TranslitComponent::text(WordForm::getLessonsForm($toSend->parameters['debt'])),
                                $paymentLink,
                                $courseName,
                                (int) $toSend->parameters['debt'],
                                $paymentLink,
                            );
                            break;
                        case Notify::TEMPLATE_STUDENT_LOW:
                            $smsText = sprintf(
                                'V gruppe "%s" u vas ostalos %s. Oplata online - %s'
                                . "\n" . '"%s" guruhida "%d dars" qoldi. Onlayn to\'lov - %s',
                                $courseName,
                                $toSend->parameters['paid_lessons'] . ' ' . TranslitComponent::text(WordForm::getLessonsForm($toSend->parameters['paid_lessons'])),
                                $paymentLink,
                                $courseName,
                                (int) $toSend->parameters['paid_lessons'],
                                $paymentLink,

                            );
                            break;
                        case Notify::TEMPLATE_PARENT_DEBT:
                            $child = User::findOne($toSend->parameters['child_id']);
                            $childName = TranslitComponent::text($child->name);
                            $paymentLink = PaymentComponent::getPaymentLink($child->id, $toSend->course_id)->url;
                            $smsText = sprintf(
                                'U studenta %s zadolzhennost v gruppe "%s" - %s. Oplata online - %s'
                                . "\n" . 'Student %s "%s" guruhida qarzdorligi mavjud. Onlayn to\'lov - %s',
                                $childName,
                                $courseName,
                                $toSend->parameters['debt'] . ' ' . TranslitComponent::text(WordForm::getLessonsForm($toSend->parameters['debt'])),
                                $paymentLink,
                                $childName . 'ning',
                                $courseName,
                                $paymentLink,
                            );
                            break;
                        case Notify::TEMPLATE_PARENT_LOW:
                            $child = User::findOne($toSend->parameters['child_id']);
                            $childName = TranslitComponent::text($child->name);
                            $paymentLink = PaymentComponent::getPaymentLink($child->id, $toSend->course_id)->url;
                            $smsText = sprintf(
                                'U studenta %s v gruppe "%s" ostalos %s. Oplata online - %s'
                                . "\n" . 'Student %s "%s" guruhida "%d dars" qoldi. Onlayn to\'lov - %s',
                                $childName,
                                $courseName,
                                $toSend->parameters['paid_lessons'] . ' ' . TranslitComponent::text(WordForm::getLessonsForm($toSend->parameters['paid_lessons'])),
                                $paymentLink,
                                $childName . 'ning',
                                $courseName,
                                $toSend->parameters['paid_lessons'],
                                $paymentLink,
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
     *
     * @return int
     */
    public function actionCreate()
    {
        $monthLimit = new DateTime('-30 days');

        /** @var CourseStudent[] $courseStudents */
        $courseStudents = CourseStudent::find()
            ->joinWith('user')
            ->andWhere([CourseStudent::tableName() . '.active' => CourseStudent::STATUS_ACTIVE])
            ->andWhere(['<', CourseStudent::tableName() . '.paid_lessons', 0])
            ->andWhere(['!=', User::tableName() . '.status', User::STATUS_LOCKED])
            ->with('course')
            ->all();
        foreach ($courseStudents as $courseStudent) {

            /*----------------------  TEMPLATE ID 1 ---------------------------*/
            /** @var Notify $queuedNotification */
            $queuedNotification = Notify::find()
                ->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id, 'template_id' => Notify::TEMPLATE_STUDENT_DEBT])
                ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                ->one();
            if (!$queuedNotification) {
                /** @var Notify[] $sentNotifications */
                $sentNotifications = Notify::find()
                    ->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id, 'template_id' => Notify::TEMPLATE_STUDENT_DEBT])
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
                        ->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id])
                        ->andWhere(['<', 'paid_lessons', 0])
                        ->select('SUM(paid_lessons)')
                        ->scalar();
                    ComponentContainer::getNotifyQueue()->add(
                        $courseStudent->user,
                        Notify::TEMPLATE_STUDENT_DEBT,
                        ['debt' => abs($lessonDebt)],
                        $courseStudent->course
                    );
                }
            }
            /*----------------------  END TEMPLATE ID 1 ---------------------------*/

            /*----------------------  TEMPLATE ID 2 ---------------------------*/
            if ($courseStudent->user->parent_id) {
                $parent = $courseStudent->user->parent;
                /** @var Notify[] $queuedNotificationsDraft */
                $queuedNotificationsDraft = Notify::find()
                    ->andWhere(['user_id' => $parent->id, 'course_id' => $courseStudent->course_id, 'template_id' => Notify::TEMPLATE_PARENT_DEBT])
                    ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                    ->all();
                $queuedNotifications = [];
                foreach ($queuedNotificationsDraft as $notification) {
                    if ($notification->parameters['child_id'] == $courseStudent->user_id) {
                        $queuedNotifications[] = $notification;
                    }
                }

                if (empty($queuedNotifications)) {
                    /** @var Notify[] $sentNotificationsDraft */
                    $sentNotificationsDraft = Notify::find()
                        ->andWhere(['user_id' => $parent->id, 'course_id' => $courseStudent->course_id, 'template_id' => Notify::TEMPLATE_PARENT_DEBT])
                        ->andWhere(['status' => Notify::STATUS_SENT])
                        ->andWhere(['>', 'sent_at', $monthLimit->format('Y-m-d H:i:s')])
                        ->orderBy(['sent_at' => SORT_DESC])
                        ->all();
                    /** @var Notify[] $sentNotifications */
                    $sentNotifications = [];
                    foreach ($sentNotificationsDraft as $notification) {
                        if ($notification->parameters['child_id'] == $courseStudent->user_id) {
                            $sentNotifications[] = $notification;
                        }
                    }

                    $needSent = true;
                    if (!empty($sentNotifications)) {
                        $lastNotification = reset($sentNotifications);
                        $needSent = (date_diff(new DateTime('now'), $lastNotification->sentDate)->days >= pow(2, count($sentNotifications) - 1));
                    }

                    if ($needSent) {
                        $lessonDebt = CourseStudent::find()
                            ->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id])
                            ->andWhere(['<', 'paid_lessons', 0])
                            ->select('SUM(paid_lessons)')
                            ->scalar();
                        ComponentContainer::getNotifyQueue()->add(
                            $parent,
                            Notify::TEMPLATE_PARENT_DEBT,
                            ['debt' => abs($lessonDebt), 'child_id' => $courseStudent->user_id],
                            $courseStudent->course
                        );
                    }
                }
            }
            /*----------------------  END TEMPLATE ID 2 ---------------------------*/
        }

        $nextWeek = new DateTime('+7 days');
        /** @var CourseStudent[] $courseStudents */
        $courseStudents = CourseStudent::find()
            ->joinWith('user')
            ->andWhere([CourseStudent::tableName() . '.active' => CourseStudent::STATUS_ACTIVE])
            ->andWhere(['BETWEEN', CourseStudent::tableName() . '.paid_lessons', 0, 2])
            ->andWhere(['!=', User::tableName() . '.status', User::STATUS_LOCKED])
            ->andWhere([
                'or',
                [CourseStudent::tableName() . '.date_end' => null],
                ['>', CourseStudent::tableName() . '.date_end', $nextWeek->format('Y-m-d')]
            ])
            ->with('course')
            ->all();
        foreach ($courseStudents as $courseStudent) {

            /*----------------------  TEMPLATE ID 3 ---------------------------*/
            $queuedNotification = Notify::find()
                ->andWhere([
                    'user_id' => $courseStudent->user_id,
                    'course_id' => $courseStudent->course_id,
                    'template_id' => [Notify::TEMPLATE_STUDENT_LOW, Notify::TEMPLATE_STUDENT_DEBT],
                ])
                ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                ->one();
            if (!$queuedNotification) {
                /** @var Notify[] $sentNotifications */
                $sentNotifications = Notify::find()
                    ->andWhere([
                        'user_id' => $courseStudent->user_id,
                        'course_id' => $courseStudent->course_id,
                        'template_id' => [Notify::TEMPLATE_STUDENT_LOW, Notify::TEMPLATE_STUDENT_DEBT],
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
                        $courseStudent->user,
                        Notify::TEMPLATE_STUDENT_LOW,
                        ['paid_lessons' => $courseStudent->paid_lessons],
                        $courseStudent->course
                    );
                }
            }
            /*----------------------  END TEMPLATE ID 3 ---------------------------*/

            /*----------------------  TEMPLATE ID 4 ---------------------------*/
            if ($courseStudent->user->parent_id) {
                $parent = $courseStudent->user->parent;
                /** @var Notify[] $queuedNotificationsDraft */
                $queuedNotificationsDraft = Notify::find()
                    ->andWhere([
                        'user_id' => $parent->id,
                        'course_id' => $courseStudent->course_id,
                        'template_id' => [Notify::TEMPLATE_PARENT_LOW, Notify::TEMPLATE_PARENT_DEBT],
                    ])
                    ->andWhere(['!=', 'status', Notify::STATUS_SENT])
                    ->all();
                $queuedNotifications = [];
                foreach ($queuedNotificationsDraft as $notification) {
                    if ($notification->parameters['child_id'] == $courseStudent->user_id) {
                        $queuedNotifications[] = $notification;
                    }
                }

                if (empty($queuedNotifications)) {
                    /** @var Notify[] $sentNotificationsDraft */
                    $sentNotificationsDraft = Notify::find()
                        ->andWhere([
                            'user_id' => $parent->id,
                            'course_id' => $courseStudent->course_id,
                            'template_id' => [Notify::TEMPLATE_PARENT_LOW, Notify::TEMPLATE_PARENT_DEBT],
                        ])
                        ->andWhere(['status' => Notify::STATUS_SENT])
                        ->andWhere(['>', 'sent_at', $monthLimit->format('Y-m-d H:i:s')])
                        ->orderBy(['sent_at' => SORT_DESC])
                        ->all();
                    /** @var Notify[] $sentNotifications */
                    $sentNotifications = [];
                    foreach ($sentNotificationsDraft as $notification) {
                        if ($notification->parameters['child_id'] == $courseStudent->user_id) {
                            $sentNotifications[] = $notification;
                        }
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
                            ['paid_lessons' => $courseStudent->paid_lessons, 'child_id' => $courseStudent->user_id],
                            $courseStudent->course
                        );
                    }
                }
            }
            /*----------------------  END TEMPLATE ID 4 ---------------------------*/
        }

        return ExitCode::OK;
    }


    /**
     * Schedules notifications about upcoming welcome lesson.
     *
     * @return int
     * @throws \Exception
     */
    public function actionWelcomeLesson()
    {
        /** @var WelcomeLesson[] $welcomeLessons */
        $welcomeLessons = WelcomeLesson::find()
            ->alias('wl')
            ->leftJoin(
                Notify::tableName() . ' n',
                'wl.course_id = n.course_id AND wl.user_id = n.user_id AND n.template_id = :welcomeLessonTemplate AND n.created_at BETWEEN :date_from AND :date_to',
                [
                    ':welcomeLessonTemplate' => Notify::TEMPLATE_WELCOME_LESSON,
                    ':date_from' => (new \DateTime('-11 minutes'))->format('Y-m-d H:i:s'),
                    ':date_to' => (new \DateTime('+11 minutes'))->format('Y-m-d H:i:s'),
                ]
            )
            ->andWhere(['wl.status' => WelcomeLesson::STATUS_UNKNOWN])
            ->andWhere([
                'between',
                'wl.lesson_date',
                (new DateTime('+1 hour 50 minutes'))->format('Y-m-d H:i:s'),
                (new DateTime('+2 hours'))->format('Y-m-d H:i:s'),
            ])
            ->andWhere(['n.id' => null])
            ->all();

        $tryTelegram = array_key_exists('telegramPublic', Yii::$app->components);

        $quantity = 0;
        $startTime = microtime(true);
        foreach ($welcomeLessons as $welcomeLesson) {
            $paramDate = $welcomeLesson->getLessonDateTime()->format('d.m.y');
            $paramTime = $welcomeLesson->getLessonDateTime()->format('H:i');
            $sendSms = true;
            $isSent = false;
            if ($tryTelegram && $welcomeLesson->user->tg_chat_id && $welcomeLesson->user->telegramSettings['subscribe']) {
                ComponentContainer::getTelegramPublic()->telegram;
                $message = Entity::escapeMarkdownV2('Hello Friend! Хотим напомнить, что сегодня, ') . '*' . Entity::escapeMarkdownV2($paramDate) . '*, в *'
                    . Entity::escapeMarkdownV2($paramTime) . '* мы ждем тебя на пробный урок по предмету *'
                    . Entity::escapeMarkdownV2($welcomeLesson->course->subject->name['ru'])
                    . '*' . Entity::escapeMarkdownV2(' в твоём любимом "Пять с Плюсом"😊 Это тут: ул. Ойбек 16')
                    . "\n\n" . Entity::escapeMarkdownV2('С нетерпением ждем тебя!');

                $response = Request::sendMessage([
                    'chat_id' => $welcomeLesson->user->tg_chat_id,
                    'text' => $message,
                    'parse_mode' => 'MarkdownV2',
                    'disable_web_page_preview' => true,
                ]);

                if ($response->isOk()) {
                    $isSent = true;
                    $sendSms = false;

                    Request::sendVenue([
                        'chat_id' => $welcomeLesson->user->tg_chat_id,
                        'latitude' => PublicMain::LOCATION_LATITUDE,
                        'longitude' => PublicMain::LOCATION_LONGITUDE,
                        'title' => PublicMain::LOCATION_TITLE,
                        'address' => PublicMain::LOCATION_ADDRESS,
                        'google_place_id' => PublicMain::GOOGLE_PLACE_ID,
                    ]);
                } else {
                    ComponentContainer::getErrorLogger()->logError(
                        'notify/send',
                        print_r(
                            [
                                'error_code' => $response->getErrorCode(),
                                'error_message' => $response->getDescription(),
                                'result' => $response->getResult(),
                            ],
                            true,
                        ),
                        true,
                    );
                }
            }

            if ($sendSms) {
                ++$quantity;
                $smsText = sprintf(
                    'Napominaem! %s v %s u vas probnoe zanyatie po predmetu "%s" v uchebnom centre "5+". Adres: ulitsa Oybek, 16'
                    . "\n" . 'Sizga eslatamiz! %s soat %s da "5+" o\'quv markazida "%s" fanidan sinov darsingiz bor. Manzil: Oybek ko\'chasi, 16-uy',
                    $paramDate,
                    $paramTime,
                    TranslitComponent::text($welcomeLesson->course->subject->name['ru']),
                    $paramDate,
                    $paramTime,
                    TranslitComponent::text($welcomeLesson->course->subject->name['uz'] ?? $welcomeLesson->course->subject->name['ru']),
                );

                try {
                    if ($welcomeLesson->user->phone) {
                        ComponentContainer::getSmsBrokerApi()->sendSingleMessage(
                            substr($welcomeLesson->user->phone, -12, 12),
                            $smsText,
                            'fsn' . $welcomeLesson->user->id . '_' . time()
                        );
                    }
                    $isSent = true;
                } catch (SmsBrokerApiException $exception) {
                    ComponentContainer::getErrorLogger()
                        ->logError('notifier/send', $exception->getMessage(), true);
                }
            }

            if ($isSent) {
                $notification = new Notify();
                $notification->user_id = $welcomeLesson->user->id;
                $notification->course_id = $welcomeLesson->course?->id;
                $notification->template_id = Notify::TEMPLATE_WELCOME_LESSON;
                $notification->parameters = [
                    'date' => $welcomeLesson->getLessonDateTime()->format('d.m.y'),
                    'time' => $welcomeLesson->getLessonDateTime()->format('H:i'),
                ];
                $notification->status = Notify::STATUS_SENT;

                $notification->save();
            }

            if ($quantity >= self::QUANTITY_LIMIT || microtime(true) - $startTime > self::TIME_LIMIT) {
                break;
            }
        }

        return ExitCode::OK;
    }
}