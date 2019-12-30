<?php

namespace common\components\telegram\commands;

use common\components\telegram\text\PublicMain;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Allows handle "<- Back" message
 *
 * @property string $name 
 * @method Message getMessage() Optional. New incoming message of any kind — text, photo, sticker, etc.
 */
trait StepableTrait
{
    protected function handleMessage(Message $message): ?Conversation
    {
        $conversation = new Conversation(
            $message->getFrom()->getId(),
            $message->getChat()->getId()
        );

        if (!$conversation->exists() || $conversation->getCommand() !== $this->name) {
            $conversation = new Conversation(
                $message->getFrom()->getId(),
                $message->getChat()->getId(),
                $this->name
            );
            $conversation->notes = ['step' => 1];
            $conversation->update();
            return $conversation;
        }
        
        if (PublicMain::TO_BACK === $this->getMessage()->getText(true)) {
            if ($conversation->notes['step'] <= 1) {
                $conversation->stop();
                return null;
            }
            $conversation->notes['step']--;
            $conversation->update();
        } else {
            $conversation->notes['step']++;
            $conversation->update();
        }
        
        return $conversation;
    }

    protected function stepBack(Conversation $conversation): ServerResponse
    {
        $conversation->notes['step'] -= 2;
        $conversation->update();
        return $this->execute();
    }
}
