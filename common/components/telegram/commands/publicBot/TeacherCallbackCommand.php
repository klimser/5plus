<?php

namespace common\components\telegram\commands\publicBot;

use common\models\BotTeacher;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\Entity;
use Longman\TelegramBot\Request;

/**
 * Teacher callback command
 */
class TeacherCallbackCommand
{
    public static function process(CallbackQuery $callbackQuery)
    {
        $data = $callbackQuery->getData();
        if (preg_match('#^teacher_info (\d+)$#', $data, $dataParts)) {
            if ($teacher = BotTeacher::findOne($dataParts[1])) {
                $message = '';
                if ($teacher->photo) {
                    Request::sendPhoto([
                        'chat_id' => $callbackQuery->getMessage()->getChat()->getId(),
                        'photo' => $teacher->photo,
                        'caption' => $teacher->name['ru'],
                    ]);
                } else {
                    $message .= '*' . Entity::escapeMarkdownV2($teacher->name['ru']) . "*\n";
                }

                if (!empty($teacher->teaser['ru'])) {
                    $message .= str_replace(['{{', '}}'], '*', Entity::escapeMarkdownV2($teacher->teaser['ru']));
                }
                Request::sendMessage([
                    'chat_id' => $callbackQuery->getMessage()->getChat()->getId(),
                    'parse_mode' => 'MarkdownV2',
                    'text' => $message,
                ]);
            }
        }
    }
}
