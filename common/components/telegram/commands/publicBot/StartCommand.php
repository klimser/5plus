<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use common\components\telegram\commands\ConversationTrait;
use common\components\telegram\Request;
use common\components\telegram\text\PublicMain;
use common\models\User;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;

/**
 * Start command
 */
class StartCommand extends SystemCommand
{
    use ConversationTrait;
    
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

    private function getKeyboard(): Keyboard
    {
        $buttons = [
            PublicMain::BUTTON_INFO,
            PublicMain::BUTTON_CONTACT,
            PublicMain::BUTTON_ORDER,
            PublicMain::BUTTON_ACCOUNT,
        ];
        $keyboard = new Keyboard(...$buttons);
        $keyboard->setResizeKeyboard(true)
            ->setSelective(false);
        return $keyboard;
    }

    public function execute()
    {
        $this->flushConversation();
        
        $payload = trim($this->getMessage()->getText(true));
        if (array_key_exists($payload, $this->telegram->getCommandsList())) {
            return $this->telegram->executeCommand($payload);
        }

        $data = [
            'chat_id' => $this->getMessage()->getChat()->getId(),
            'text'    => Request::escapeMarkdownV2(PublicMain::GREETING),
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => $this->getKeyboard(),
        ];

        return Request::sendMessage($data);
    }
}
