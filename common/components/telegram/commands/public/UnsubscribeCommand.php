<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\telegram\Request;
use common\models\User;
use Longman\TelegramBot\Commands\UserCommand;

/**
 * Unsubscribe command
 */
class UnsubscribeCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'unsubscribe';

    /**
     * @var string
     */
    protected $description = 'Отключить подписку на уведомления об оплате';

    /**
     * @var string
     */
    protected $usage = '/unsubscribe';

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
        $chatId = $this->getMessage()->getChat()->getId();

        $data = [
            'chat_id' => $chatId,
        ];

        $user = User::findOne(['tg_chat_id' => $chatId]);
        if ($user) {
            $user->tg_chat_id = null;
            $user->save();
            $data['text'] = "Подписка на уведомления отключена";
        } else {
            $data['text'] = "Вы не подписаны на уведомления. Введите /subscribe чтобы подписаться.";
        }
        return Request::sendMessage($data);
    }
}
