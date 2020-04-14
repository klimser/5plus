<?php
$params = array_merge(
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'user' => [
            'identityClass' => \common\models\User::class,
            'enableAutoLogin' => true,
        ],
        'db' => [
            'class' => \yii\db\Connection::class,
            'charset' => 'utf8mb4',
            'dsn' => $params['db-dsn'],
            'username' => $params['db-username'],
            'password' => $params['db-password'],
            'tablePrefix' => $params['db-tablePrefix'],
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 0,
        ],
        'assetManager' => [
            'bundles' => [
                \yii\web\JqueryAsset::class => [
                    'js' => []
                ],
                \yii\bootstrap\BootstrapAsset::class => [
                    'css' => []
                ],
                \yii\bootstrap\BootstrapPluginAsset::class => [
                    'js' => []
                ],
                \yii\bootstrap4\BootstrapAsset::class => [
                    'css' => []
                ],
                \yii\bootstrap4\BootstrapPluginAsset::class => [
                    'js' => []
                ],
            ],
            'linkAssets' => true,
            'appendTimestamp' => false,
            'hashCallback' => function($path) {
                $getLatestModifyDate = function($filePath) use (&$getLatestModifyDate) {
                    $latestDate = 0;
                    if (is_file($filePath)) $latestDate = max($latestDate, filemtime($filePath));
                    elseif (is_dir($filePath)) {
                        foreach (glob($filePath . '/*') as $childFile) {
                            $latestDate = max($latestDate, $getLatestModifyDate($childFile));
                        }
                    }
                    return $latestDate;
                };
                $path = (is_file($path) ? dirname($path) : $path) . $getLatestModifyDate($path);
                return sprintf('%x', crc32($path . Yii::getVersion()));
            },
        ],
        'formatter' => [
            'class' => \yii\i18n\Formatter::class,
            'defaultTimeZone' => 'Asia/Tashkent',
        ],
        'mailQueue' => [
            'class' => \common\components\MailQueue::class,
        ],
        'notifyQueue' => [
            'class' => \common\components\NotifyQueue::class,
        ],
        'errorLogger' => [
            'class' => \common\components\Error::class,
        ],
        'actionLogger' => [
            'class' => \common\components\Action::class,
        ],
        'reCaptcha' => [
            'name' => 'reCaptcha',
            'class' => \himiklab\yii2\recaptcha\ReCaptcha::class,
            'siteKey' => $params['reCaptcha-siteKey'],
            'secret' => $params['reCaptcha-secret'],
        ],
        'tinifier' => [
            'class' => \common\components\Tinifier::class,
            'apiKey' => $params['tinifyKey'],
        ],
        'paymoApi' => [
            'class' => \common\components\paymo\PaymoApi::class,
            'paymentUrl' => $params['paymo-url'],
            'storeId' => $params['paymo-storeId'],
            'apiKey' => $params['paymo-key'],
            'login' => $params['paymo-login'],
            'password' => $params['paymo-password'],
        ],
        'paygramApi' => [
            'class' => \common\components\paygram\PaygramApi::class,
            'login' => $params['paygram-login'],
            'password' => $params['paygram-password'],
            'templateMap' => $params['paygram-template-map'],
        ],
        'botPush' => [
            'class' => \common\components\BotPush::class,
        ],
    ],
    'aliases' => [
        '@uploads' => '@frontend/web/uploads',
        '@uploadsUrl' => '//5plus.uz/uploads',
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'language' => 'ru-RU',
    'timeZone' => 'Asia/Tashkent',
];
