<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\telegram\Request;
use common\models\User;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;

/**
 * Subscribe command
 */
class SubscribeCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'subscribe';

    /**
     * @var string
     */
    protected $description = 'Подписаться на уведомления об оплате';

    /**
     * @var string
     */
    protected $usage = '/subscribe';

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
            $data['text'] = "Вы уже подписаны на уведомления. Чтобы отключить подписку введите /unsubscribe.";
        } else {
            $data = array_merge($data, self::getSubscribeRequestData());
        }
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
}
