<?php
namespace common\components\helpers;

class Html extends \yii\bootstrap4\Html
{
    public static function phoneLink(string $phone, ?string $text = null, array $options = []): string
    {
        return static::a(static::tag('span', $text ?? $phone, ['class' => 'text-nowrap']), "tel:$phone", $options);
    }
}
