<?php
namespace common\components\helpers;

class TelegramHelper
{
    public static function escapeMarkdownV2(string $text): string
    {
        return preg_replace([
            '/^([_*\[\]\(\)~`>#\+\-=|{}\.!])/u',
            '/([^\\\\])([_*\[\]\(\)~`>#\+\-=|{}\.!])/u',
        ], [
            '\\\\$1',
            '$1\\\\$2',
        ], $text);
    }
}
