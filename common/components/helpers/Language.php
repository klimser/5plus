<?php

namespace common\components\helpers;

class Language
{
    public const LANGUAGE_RU = 'ru';
    public const LANGUAGE_UZ = 'uz';
    public const LANGUAGE_EN = 'en';
    public const ALLOWED_LANGUAGES = [self::LANGUAGE_RU, self::LANGUAGE_UZ, self::LANGUAGE_EN];
    public const LABELS = [
        self::LANGUAGE_RU => 'Русский',
        self::LANGUAGE_UZ => 'O\'zbek',
        self::LANGUAGE_EN => 'English',
    ];
}