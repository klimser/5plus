<?php

namespace common\components;

use common\components\apelsin\ApelsinApi;
use common\components\AppApelsin\AppApelsinApi;
use common\components\AppPayme\AppPaymeApi;
use common\components\click\ClickApi;
use common\components\paybox\PayboxApi;
use common\components\paygram\PaygramApi;
use common\components\payme\PaymeApi;
use common\components\paymo\PaymoApi;
use common\components\paynet\PaynetApi;
use common\components\SmsBroker\SmsBrokerApi;
use skeeks\yii2\httpBasicAuth\HttpBasicAuthComponent;
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
    public static function getAppPaymeApi(): AppPaymeApi
    {
        return Yii::$app->appPaymeApi;
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
    public static function getAppApelsinApi(): AppApelsinApi
    {
        return Yii::$app->appApelsinApi;
    }
    public static function getPayboxApi(): PayboxApi
    {
        return Yii::$app->payboxApi;
    }
    public static function getPaynetApi(): PaynetApi
    {
        return Yii::$app->paynetApi;
    }

    public static function getExternalBasicAuth(): HttpBasicAuthComponent
    {
        return \Yii::$app->externalBasicAuth;
    }

    public static function getApi(): ApiComponent
    {
        return \Yii::$app->api;
    }

    public static function getSmsApi(): SmsServiceComponent
    {
        return \Yii::$app->smsApi;
    }
}
