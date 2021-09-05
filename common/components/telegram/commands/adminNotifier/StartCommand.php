<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Entity;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * Start command
 */
class StartCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Start command';

    /**
     * @var string
     */
    protected $usage = '/start';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $data = [
            'chat_id' => $this->getMessage()->getChat()->getId(),
            'text'    => Entity::escapeMarkdownV2("Привет!\nЯ буду присылать вам оповещения о новых заявках, отзывах и сообщениях!\nЯ не принимаю никаких команд"),
            'parse_mode' => 'MarkdownV2',
        ];
        return Request::sendMessage($data);
    }
}
