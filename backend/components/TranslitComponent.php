<?php

namespace backend\components;

class TranslitComponent
{
    protected static $_symbolTable = [
        // RUSSIAN
        'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'g',  'д' => 'd',  'е' => 'e',  'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z',  'и' => 'i',  'й' => 'j',  'к' => 'k',  'л' => 'l',  'м' => 'm',  'н' => 'n',  'о' => 'o',
        'п' => 'p',  'р' => 'r',  'с' => 's',  'т' => 't',  'у' => 'u',  'ф' => 'f',  'х' => 'kh', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shh','ъ' => "''", 'ы' => 'y',  'ь' => "'",  'э' => 'e\'','ю' => 'yu',
        'я' => 'ya',

        'А' => 'A',  'Б' => 'B',  'В' => 'V',  'Г' => 'G',  'Д' => 'D',  'Е' => 'E',  'Ё' => 'Yo', 'Ж' => 'Zh',
        'З' => 'Z',  'И' => 'I',  'Й' => 'J',  'К' => 'K',  'Л' => 'L',  'М' => 'M',  'Н' => 'N',  'О' => 'O',
        'П' => 'P',  'Р' => 'R',  'С' => 'S',  'Т' => 'T',  'У' => 'U',  'Ф' => 'F',  'Х' => 'Kh', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shh','Ъ' => "''", 'Ы' => 'Y',  'Ь' => "'",  'Э' => 'E\'','Ю' => 'Yu',
        'Я' => 'Ya',
        // UKRAINE
        'є' => 'e\'', 'і' => 'i',  'ї' => 'yi',  'ґ' => 'g',  'ў' => 'u',
        'Є' => 'E\'', 'І' => 'I',  'Ї' => 'Yi',  'Ґ' => 'G',  'Ў' => 'U',
        // OTHER EUROPEAN
        'š' => 's', 'č' => 'c', 'ž' => 'z', 'ē' => 'e', 'ģ' => 'g', 'ņ' => 'n', 'ā' => 'a', 'ū' => 'u', 'ķ' => 'k',
        'ö' => 'o', 'ä' => 'a', 'ü' => 'u', 'ß' => 's', 'ù' => 'u', 'à' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
        'ò' => 'o',

        'Š' => 'S', 'Č' => 'C', 'Ž' => 'Z', 'Ē' => 'E', 'Ģ' => 'G', 'Ņ' => 'N', 'Ā' => 'A', 'Ū' => 'U', 'Ķ' => 'K',
        'Ö' => 'O', 'Ä' => 'A', 'Ü' => 'U',
    ];

    /**
     * @param string $str
     * @return string
     */
    public static function text(string $str): string
    {
        $symbolList = preg_split('//u', $str, null, PREG_SPLIT_NO_EMPTY);

        for ($i = 0; $i < count($symbolList); $i++) {
            if (array_key_exists($symbolList[$i], self::$_symbolTable)) {
                $symbolList[$i] = self::$_symbolTable[$symbolList[$i]];
            }
        }
        return implode('', $symbolList);
    }

    /**
     * @param string $str
     * @return string
     */
    public static function filename(string $str): string
    {
        $str = self::text($str);
        $str = str_replace(' ', '-', $str);
        return str_replace(['\'', '"', '\\', '\/', '?'], '', $str);
    }
}
