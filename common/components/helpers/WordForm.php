<?php
namespace common\components\helpers;

class WordForm
{
    public static function getLessonsForm(int $num): string
    {
        $num = abs($num);
        $teen = $num % 100;
        if ($teen < 10 || $teen > 20) {
            if ($num % 10 == 1) return 'занятие';
            if (in_array($num % 10, [2, 3, 4])) return 'занятия';
        }
        return 'занятий';
    }
}
