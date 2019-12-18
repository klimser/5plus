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
trait ConversationTrait
{
    protected function flushConversation(): void
    {
        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        if ($conversation->exists()) {
            $conversation->stop();
        }
    }

    protected function addNote(Conversation $conversation, string $key, string $value)
    {
        $conversation->notes[$key] = $value;
        $conversation->update();
    }

    protected function removeNote(Conversation $conversation, string $key)
    {
        if (array_key_exists($key, $conversation->notes)) {
            unset($conversation->notes[$key]);
            $conversation->update();
        }
    }
}
