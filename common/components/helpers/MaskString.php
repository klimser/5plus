<?php
namespace common\components\helpers;

class MaskString
{
    public static function generate(string $string, int $left, int $right): string
    {
        $strLength = mb_strlen($string, 'UTF-8');
        return $strLength <= ($left + $right)
            ? $string
            : ($left > 0 ? mb_substr($string, 0, $left, 'UTF-8') : '')
                . str_pad('', $strLength - $left - $right, '*')
                . ($right > 0 ? mb_substr($string, 0 - $right, null, 'UTF-8') : '');
    }
}
