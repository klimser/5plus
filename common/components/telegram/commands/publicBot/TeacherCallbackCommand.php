<?php

namespace common\components\telegram\commands\publicBot;

use common\components\helpers\TelegramHelper;
use Longman\TelegramBot\Request;
use common\models\Teacher;
use Longman\TelegramBot\Entities\CallbackQuery;

/**
 * Teacher callback command
 */
class TeacherCallbackCommand
{
    public static function process(CallbackQuery $callbackQuery)
    {
        $data = $callbackQuery->getData();
        if (preg_match('#^teacher_info (\d+)$#', $data, $dataParts)) {
            if ($teacher = Teacher::findOne($dataParts[1])) {
                $message = '';
                if ($teacher->photo) {
                    Request::sendPhoto([
                        'chat_id' => $callbackQuery->getMessage()->getChat()->getId(),
                        'photo' => preg_replace('#^\/\/#', 'https://', $teacher->imageUrl),
                        'caption' => $teacher->officialName,
                    ]);
                } else {
                    $message .= '*' . TelegramHelper::escapeMarkdownV2($teacher->officialName) . "*\n";
                }
                
                $message .= str_replace(['{{', '}}'], '*', TelegramHelper::escapeMarkdownV2($teacher->descriptionForEdit));
                Request::sendMessage([
                    'chat_id' => $callbackQuery->getMessage()->getChat()->getId(),
                    'parse_mode' => 'MarkdownV2',
                    'text' => $message,
                ]);
            }
        }
    }
}
