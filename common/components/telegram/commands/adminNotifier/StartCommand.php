<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use common\components\helpers\TelegramHelper;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;

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
    protected $description = 'Start command';

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
        $data = [
            'chat_id' => $this->getMessage()->getChat()->getId(),
            'text'    => TelegramHelper::escapeMarkdownV2("Привет!\nЯ буду присылать вам оповещения о новых заявках, отзывах и сообщениях!\nЯ не принимаю никаких команд"),
            'parse_mode' => 'MarkdownV2',
        ];
        return Request::sendMessage($data);
    }
}
