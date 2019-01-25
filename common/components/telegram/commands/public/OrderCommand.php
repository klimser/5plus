<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\telegram\Request;
use common\models\User;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Order command
 */
class OrderCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'order';

    /**
     * @var string
     */
    protected $description = 'Оставить заявку на бесплатное занятие';

    /**
     * @var string
     */
    protected $usage = '/order';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chatId = $message->getChat()->getId();
        $data = [
            'chat_id' => $chatId,
        ];

        //If a conversation is busy, execute the conversation command after handling the message
        $conversation = new Conversation(
            $message->getFrom()->getId(),
            $chatId
        );

        //Fetch conversation command if it exists and execute it
        if ($conversation->exists()) {
            if ($conversation->getCommand() != $this->name) {
                return $this->telegram->executeCommand($conversation->getCommand());
            } else {

            }
        } else {
            $conversation = new Conversation(
                $message->getFrom()->getId(),
                $chatId,
                $this->name
            );
            $conversation->notes = ['step' => 1];
            $conversation->update();
        }
        $data = array_merge($data, self::getSubscribeRequestData());

        return Request::sendMessage($data);
    }

    public static function getSubscribeRequestData(): array
    {
        $data = [];
        $data['text'] = "Для получения финансовой информации подтвердите свой телефон.";
        $keyboard = new Keyboard([
            ['text' => 'Подтвердить телефон', 'request_contact' => true],
        ]);
        $keyboard->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->setSelective(false);
        $data['reply_markup'] = $keyboard;

        return $data;
    }

    private function processSubscription(): ServerResponse
    {
        $message = $this->getMessage();
        $contact = $message->getContact() ?: $message->getReplyToMessage()->getContact();
        $data = ['chat_id' => $message->getChat()->getId()];

        $query = User::find()
            ->andWhere(['status' => [User::STATUS_ACTIVE, User::STATUS_INACTIVE]])
            ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS, User::ROLE_COMPANY]])
            ->andWhere(['or', ['phone' => $contact->getPhoneNumber()], ['phone2' => $contact->getPhoneNumber()]]);


        if ($message->getFrom()->getId() != $contact->getUserId()) {
            $data['text'] = 'Подписка на уведомления об оплате возможна только для себя';
        } else {
            if (!$message->getContact()) {
                $query->andWhere(['like', 'name', $message->getText()]);
            }
            $users = $query->all();
            if (count($users) == 1) {
                $data['text'] = $this->setUserSubscription(reset($users));
            } elseif (empty($users)) {
                if ($message->getContact()) {
                    $data['text'] = 'Пользователь с таким номером телефона не найден. Если вы занимаетесь в учебном центре обратитесь к менеджерам с просьбой скорректировать ваш номер телефона.';
                } else {
                    $data['reply_to_message_id'] = $message->getReplyToMessage()->getMessageId();
                    $data['reply_markup'] = ['force_reply' => true, 'selective' => true];
                    $data['text'] = 'Не удалось найти пользователя по указанным параметрам, попробуйте ещё раз. Напишите вашу фамилию или имя.';
                }
            } else {
                $data['reply_to_message_id'] = $message->getContact() ? $message->getMessageId() : $message->getReplyToMessage()->getMessageId();
                $data['reply_markup'] = ['force_reply' => true, 'selective' => true];
                $data['text'] = 'Найдено несколько пользователей. Напишите, пожалуйста, вашу фамилию или имя.';
            }
        }

        return Request::sendMessage($data);
    }

    /**
     * @param User $user
     * @return string
     */
    private function setUserSubscription(User $user): string
    {
        $user->tg_chat_id = $this->getMessage()->getChat()->getId();
        if ($user->save()) {
            $conversation = new Conversation(
                $this->getMessage()->getFrom()->getId(),
                $this->getMessage()->getChat()->getId()
            );
            if ($conversation->exists()) $conversation->stop();
            return 'Подписка на уведомления успешно включена.';
        } else {
            \Yii::$app->errorLogger->logError('public-bot/subscribe', print_r($user->getErrors(), true), true);
            return 'Произошла ошибка, не удалось включить подписку, мы уже знаем о случившемся и как можно скорее исправим это.';
        }
    }
}
