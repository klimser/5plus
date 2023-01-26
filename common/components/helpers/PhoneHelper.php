<?php
namespace common\components\helpers;

class PhoneHelper
{
    public static function getPhoneDigitsOnly(string $phone): string
    {
        return preg_replace('#\D#', '', $phone);
    }

    public static function getPhoneFormatted(string $phone): string
    {
        $digits = self::getPhoneDigitsOnly($phone);
        if (!$digits || strlen($digits) < 9) return $digits;

        return substr($digits, -9, 2) . ' ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
    }

    public static function getPhoneFull(string $phone): ?string
    {
        $digits = self::getPhoneDigitsOnly($phone);
        if (!$digits || strlen($digits) < 9) return $digits;

        return '+998 (' . substr($digits, -9, 2) . ') ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
    }

    public static function getPhoneInternational(string $phone): string
    {
        $digits = self::getPhoneDigitsOnly($phone);
        switch (strlen($digits)) {
            case 9:
                return "+998$digits";
            case 12:
                return "+$digits";
        }

        return $digits;
    }
}
