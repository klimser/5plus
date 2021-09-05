<?php
namespace common\components\helpers;

class MaskString
{
    public static function generate(string $string, int $left, int $right, int $asteriskMaxLength = 0): string
    {
        $strLength = mb_strlen($string, 'UTF-8');
        $asteriskLength = $strLength - $left - $right;
        if ($asteriskMaxLength > 0 && $asteriskLength > $asteriskMaxLength) {
            $asteriskLength = $asteriskMaxLength;
        }
        return $strLength <= ($left + $right)
            ? mb_substr($string, 0, 1, 'UTF-8') . str_pad('', $strLength - 1, '*')
            : ($left > 0 ? mb_substr($string, 0, $left, 'UTF-8') : '')
                . str_pad('', $asteriskLength, '*')
                . ($right > 0 ? mb_substr($string, 0 - $right, null, 'UTF-8') : '');
    }
}
