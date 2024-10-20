<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\UserCommands\AccountCommand;
use Longman\TelegramBot\Request;
use common\components\telegram\text\PublicMain;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\TelegramLog;

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
     * @return ServerResponse
     * @throws TelegramException
     */
    public function executeNoDb(): ServerResponse
    {
        //Do nothing
        return Request::emptyResponse();
    }

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        if ($payment = $message->getSuccessfulPayment()) {
            return AccountCommand::handleSuccessfulPayment($payment, $message->getChat()->getId());
        }

        if ($message->getText() === PublicMain::TO_MAIN) {
            return $this->telegram->executeCommand('start');
        }

        //If a conversation is busy, execute the conversation command after handling the message
        $conversation = new Conversation(
            $message->getFrom()->getId(),
            $message->getChat()->getId()
        );

        //Fetch conversation command if it exists and execute it
        if ($conversation->exists() && ($command = $conversation->getCommand())) {
            return $this->telegram->executeCommand($command);
        }

        TelegramLog::debug("TEXT: {$message->getText()}");

        switch ($message->getText()) {
            case PublicMain::BUTTON_CONTACT:
                return $this->telegram->executeCommand('contactinfo');
            case PublicMain::BUTTON_INFO:
                return $this->telegram->executeCommand('info');
            case PublicMain::BUTTON_ORDER:
                return $this->telegram->executeCommand('order');
            case PublicMain::BUTTON_REGISTER:
                return $this->telegram->executeCommand('login');
            case PublicMain::BUTTON_ACCOUNT:
                return $this->telegram->executeCommand('account');
            default:
                return $this->telegram->executeCommand('start');
        }
    }
}
