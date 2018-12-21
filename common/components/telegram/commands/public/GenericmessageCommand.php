<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use common\components\telegram\Request;
use common\models\User;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Commands\SystemCommand;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Execution if MySQL is required but not available
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     */
    public function executeNoDb()
    {
        //Do nothing
        return Request::emptyResponse();
    }

    /**
     * Execute command
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        //If a conversation is busy, execute the conversation command after handling the message
        $conversation = new Conversation(
            $message->getFrom()->getId(),
            $message->getChat()->getId()
        );

        //Fetch conversation command if it exists and execute it
        if ($conversation->exists() && ($command = $conversation->getCommand())) {
            return $this->telegram->executeCommand($command);
        }

        if ($message->getContact()
            || ($message->getReplyToMessage() && $message->getReplyToMessage()->getContact())) {
            return $this->processSubscription();
        }

        return Request::emptyResponse();
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
            return 'Подписка на уведомления успешно включена.';
        } else {
            \Yii::$app->errorLogger->logError('public-bot/subscribe', print_r($user->getErrors(), true), true);
            return 'Произошла ошибка, не удалось включить подписку, мы уже знаем о случившемся и как можно скорее исправим это.';
        }
    }
}
