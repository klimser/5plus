<?php

namespace common\components;

use common\components\apelsin\ApelsinApi;
use common\components\bitrix\Bitrix;
use common\components\click\ClickApi;
use common\components\paygram\PaygramApi;
use common\components\payme\PaymeApi;
use common\components\paymo\PaymoApi;
use common\components\SmsBroker\SmsBrokerApi;
use Yii;

class ComponentContainer
{
    public static function getMailQueue(): MailQueue
    {
        return Yii::$app->mailQueue;
    }
    public static function getNotifyQueue(): NotifyQueue
    {
        return Yii::$app->notifyQueue;
    }
    public static function getErrorLogger(): Error
    {
        return Yii::$app->errorLogger;
    }
    public static function getActionLogger(): Action
    {
        return Yii::$app->actionLogger;
    }
    public static function getTinifier(): Tinifier
    {
        return Yii::$app->tinifier;
    }
    public static function getPaymoApi(): PaymoApi
    {
        return Yii::$app->paymoApi;
    }
    public static function getPaygramApi(): PaygramApi
    {
        return Yii::$app->paygramApi;
    }
    public static function getTelegramAdminNotifier(): Telegram
    {
        return Yii::$app->telegramAdminNotifier;
    }
    public static function getTelegramPublic(): Telegram
    {
        return Yii::$app->telegramPublic;
    }
    public static function getBitrix(): Bitrix
    {
        return Yii::$app->bitrix;
    }
    public static function getBotPush(): BotPush
    {
        return Yii::$app->botPush;
    }
    public static function getClickApi(): ClickApi
    {
        return Yii::$app->clickApi;
    }
    public static function getPaymeApi(): PaymeApi
    {
        return Yii::$app->paymeApi;
    }
    public static function getSmsBrokerApi(): SmsBrokerApi
    {
        return Yii::$app->smsBrokerApi;
    }
    public static function getAgeValidator(): AgeValidator
    {
        return Yii::$app->ageValidator;
    }
    public static function getApelsinApi(): ApelsinApi
    {
        return Yii::$app->apelsinApi;
    }
}
