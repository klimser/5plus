<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\telegram\commands\ConversationTrait;
use common\components\telegram\Request;
use common\components\telegram\text\PublicMain;
use Longman\TelegramBot\Commands\UserCommand;

/**
 * User "/contact" command
 *
 * Command that shows contact info for customers.
 */
class ContactCommand extends UserCommand
{
    use ConversationTrait;
    
    /**
     * @var string
     */
    protected $name = 'contact';
    /**
     * @var string
     */
    protected $description = 'Связаться с нами';
    /**
     * @var string
     */
    protected $usage = '/contact';
    /**
     * @var string
     */
    protected $version = '1.1.0';

    public function execute()
    {
        $this->flushConversation();
        
        $chatId = $this->getMessage()->getChat()->getId();

        Request::sendContact([
            'chat_id' => $chatId,
            'phone_number' => PublicMain::CONTACT_PHONE,
            'first_name' => PublicMain::CONTACT_NAME,
            'last_name' => PublicMain::CONTACT_SURNAME,
            'vcard' => PublicMain::CONTACT_VCARD,
        ]);

        return Request::sendMessage([
            'chat_id' => $chatId,
            'parse_mode' => 'MarkdownV2',
            'text' => PublicMain::CONTACT_MESSAGE,
            'disable_web_page_preview' => true,
        ]);
    }
}
