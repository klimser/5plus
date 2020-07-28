<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\helpers\TelegramHelper;
use common\components\telegram\commands\ConversationTrait;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Exception\TelegramException;

/**
 * User "/help" command
 *
 * Command that lists all available commands and displays them in User and Admin sections.
 */
class HelpCommand extends UserCommand
{
    use ConversationTrait;
    
    /**
     * @var string
     */
    protected $name = 'help';
    /**
     * @var string
     */
    protected $description = 'Показывает список доступных команд';
    /**
     * @var string
     */
    protected $usage = '/help или /help <command>';
    /**
     * @var string
     */
    protected $version = '1.3.0';

    public function execute()
    {
        $message     = $this->getMessage();
        $chatId     = $message->getChat()->getId();
        $commandStr = trim($message->getText(true));
        // Admin commands shouldn't be shown in group chats
        $safeToShow = $message->getChat()->isPrivateChat();
        $data = [
            'chat_id'    => $chatId,
            'parse_mode' => 'MarkdownV2',
        ];
        [$allCommands, $userCommands, $adminCommands] = $this->getUserAdminCommands();
        // If no command parameter is passed, show the list.
        if ($commandStr === '') {
            $data['text'] = '*Команды*:' . PHP_EOL;
            foreach ($userCommands as $user_command) {
                $data['text'] .= TelegramHelper::escapeMarkdownV2('/' . $user_command->getName() . ' - ' . $user_command->getDescription() . PHP_EOL);
            }
            if ($safeToShow && count($adminCommands) > 0) {
                $data['text'] .= PHP_EOL . '*Команды администратора*:' . PHP_EOL;
                foreach ($adminCommands as $admin_command) {
                    $data['text'] .= TelegramHelper::escapeMarkdownV2('/' . $admin_command->getName() . ' - ' . $admin_command->getDescription() . PHP_EOL);
                }
            }
            $data['text'] .= PHP_EOL . 'Для конкретной команды введите: /help <command>';
            return Request::sendMessage($data);
        }
        $commandStr = str_replace('/', '', $commandStr);
        if (isset($allCommands[$commandStr]) && ($safeToShow || !$allCommands[$commandStr]->isAdminCommand())) {
            $command      = $allCommands[$commandStr];
            $data['text'] = TelegramHelper::escapeMarkdownV2(sprintf(
                'Команда: %s (v%s)' . PHP_EOL .
                'Описание: %s' . PHP_EOL .
                'Использование: %s',
                $command->getName(),
                $command->getVersion(),
                $command->getDescription(),
                $command->getUsage()
            ));
            return Request::sendMessage($data);
        }
        $data['text'] = TelegramHelper::escapeMarkdownV2('Справка недоступна: Команда /' . $commandStr . ' не найдена');
        return Request::sendMessage($data);
    }

    /**
     * Get all available User and Admin commands to display in the help list.
     *
     * @return Command[][]
     * @throws TelegramException
     */
    protected function getUserAdminCommands()
    {
        // Only get enabled Admin and User commands that are allowed to be shown.
        /** @var Command[] $commands */
        $commands = array_filter($this->telegram->getCommandsList(), function ($command) {
            /** @var Command $command */
            return !$command->isSystemCommand() && $command->showInHelp() && $command->isEnabled();
        });
        $user_commands = array_filter($commands, function ($command) {
            /** @var Command $command */
            return $command->isUserCommand();
        });
        $admin_commands = array_filter($commands, function ($command) {
            /** @var Command $command */
            return $command->isAdminCommand();
        });
        ksort($commands);
        ksort($user_commands);
        ksort($admin_commands);
        return [$commands, $user_commands, $admin_commands];
    }
}
