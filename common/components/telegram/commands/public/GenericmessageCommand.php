<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use common\models\User;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
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

        if ($message->getContact()) {
            return $this->processSubscribtion();
        }

        return Request::emptyResponse();
    }

    private function processSubscribtion(): ServerResponse
    {
        $message = $this->getMessage();
        if ($message->getFrom()->getId() != $message->getContact()->getUserId()) {
            $data = [
                'chat_id' => $message->getChat()->getId(),
                'text'    => 'Подписка на уведомления об оплате возможна только для себя',
            ];
            return Request::sendMessage($data);
        } else {
            $users = User::find()
                ->andWhere(['status' => [User::STATUS_ACTIVE, User::STATUS_INACTIVE]])
                ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS, User::ROLE_COMPANY]])
                ->andWhere(['or', ['phone' => $message->getContact()->getPhoneNumber()], ['phone2' => $message->getContact()->getPhoneNumber()]])
                ->all();
            if (empty($users)) {
                $data = [
                    'chat_id' => $message->getChat()->getId(),
                    'text'    => 'Пользователь с таким номером телефона не найден. Если вы занимаетесь в учебном центре обратитесь к менеджерам с просьбой скорректировать ваш номер телефона.',
                ];
                return Request::sendMessage($data);
            } elseif (count($users) == 1) {
                $user = reset($users);
                $user->tg_chat_id = $message->getChat()->getId();
                $user->save();
                $data = [
                    'chat_id' => $message->getChat()->getId(),
                    'text'    => 'Подписка на уведомления успешно включена.',
                ];
                return Request::sendMessage($data);
            } else {
                $data['reply_to_message_id'] = $message->getMessageId();
                $data['reply_markup'] = ['force_reply' => true, 'selective' => true];
            }
        }
    }
}
