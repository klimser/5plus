<?php

namespace common\components;

use common\components\paygram\PaygramApi;
use common\components\paymo\PaymoApi;

class ComponentContainer
{
    public static function getMailQueue(): MailQueue
    {
        return \Yii::$app->mailQueue;
    }
    public static function getNotifyQueue(): NotifyQueue
    {
        return \Yii::$app->notifyQueue;
    }
    public static function getErrorLogger(): Error
    {
        return \Yii::$app->errorLogger;
    }
    public static function getActionLogger(): Action
    {
        return \Yii::$app->actionLogger;
    }
    public static function getTinifier(): Tinifier
    {
        return \Yii::$app->tinifier;
    }
    public static function getPaymoApi(): PaymoApi
    {
        return \Yii::$app->paymoApi;
    }
    public static function getPaygramApi(): PaygramApi
    {
        return \Yii::$app->paygramApi;
    }
    public static function getTelegramPublic(): Telegram
    {
        return \Yii::$app->telegramPublic;
    }
}