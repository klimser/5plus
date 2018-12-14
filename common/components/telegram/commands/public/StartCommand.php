<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use common\components\telegram\Request;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;

/**
 * Start command
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Начать использовать бота';

    /**
     * @var string
     */
    protected $usage = '/start';

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
        $text    = "Учебный центр \"5 с плюсом\" приветствует вас!\nВведите /help чтобы просмотреть доступные команды!";
        $data = [
            'chat_id' => $chatId,
            'text'    => $text,
        ];
        return Request::sendMessage($data);
    }
}
