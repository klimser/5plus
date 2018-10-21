<?php
namespace common\components\helpers;

class Spreadsheet
{
    /**
     * @param int $number
     * @return string
     */
    private static function getLetterByNumber(int $number): string
    {
        return chr(ord('A') + ($number - 1));
    }

    /**
     * @param int $number
     * @return string
     * @throws \Exception
     */
    public static function getColumnByNumber(int $number): string
    {
        if ($number <= 0 || $number > 18278) throw new \Exception('Wrong number');

        $str = '';
        if ($number > 702) {
            $ost = $number - 702;
            $str .= self::getLetterByNumber(ceil($ost / 676));
            $number = ($ost - 1) % 676 + 27;
        }

        if ($number > 26) {
            $ost = $number - 26;
            $str .= self::getLetterByNumber(ceil($ost / 26));
            $number = ($ost - 1) % 26 + 1;
        }

        $str .= self::getLetterByNumber($number);

        return $str;
    }
}