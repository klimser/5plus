<?php

namespace common\components\telegram;

use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;

class Telegram extends \Longman\TelegramBot\Telegram
{
    public function processUpdate(Update $update): ServerResponse
    {
        $this->commands_objects = [];

        return parent::processUpdate($update);
    }
}