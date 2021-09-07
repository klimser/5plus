<?php
namespace common\components\helpers;

class StringGenerator
{
    private const COLLECTION_NUMBERS = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
    private const COLLECTION_LETTERS = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'k', 'm', 'p', 'q', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
    private const COLLECTION_UPPERCASE = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'K', 'L', 'M', 'N', 'P', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

    public static function generate(int $stringLength, bool $allowNumbers = true, bool $allowLetters = false, bool $allowUppercase = false): string
    {
        if ($stringLength <= 0) {
            return '';
        }
        if (!$allowNumbers && !$allowLetters && !$allowUppercase) {
            throw new \Exception('You must allow at least one char collection');
        }
        $charset = [];
        if ($allowNumbers) {
            $charset = array_merge($charset, self::COLLECTION_NUMBERS);
        }
        if ($allowLetters) {
            $charset = array_merge($charset, self::COLLECTION_LETTERS);
        }
        if ($allowUppercase) {
            $charset = array_merge($charset, self::COLLECTION_UPPERCASE);
        }

        $result = '';
        $charsetLength = count($charset) - 1;
        for ($i = 0; $i < $stringLength; $i++) {
            $result .= $charset[mt_rand(0, $charsetLength)];
        }

        return $result;
    }
}
