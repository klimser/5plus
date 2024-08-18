<?php
$params = array_merge(
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'name' => '5plus',
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
            'schemaCacheDuration' => 86400,
        ],
        'assetManager' => [
            'bundles' => [
                \yii\web\JqueryAsset::class => [
                    'js' => [
                        YII_ENV_DEV ? 'jquery.js' : '//code.jquery.com/jquery-3.5.1.min.js',
                    ],
                    'jsOptions' => [
                        'crossorigin' => 'anonymous',
                        'integrity' => YII_ENV_DEV ? false : 'sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=',
                    ],
                ],
                \yii\jui\JuiAsset::class => [
                    'js' => [
                        YII_ENV_DEV ? 'jquery-ui.js' : '//code.jquery.com/ui/1.12.1/jquery-ui.min.js',
                    ],
                    'jsOptions' => [
                        'crossorigin' => 'anonymous',
                        'integrity' => YII_ENV_DEV ? false : 'sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=',
                    ],
                    'css' => [
                        YII_ENV_DEV ? 'themes/smoothness/jquery-ui.css' : '//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css',
                    ]
                ],
                \yii\bootstrap4\BootstrapAsset::class => [
                    'css' => []
                ],
                \yii\bootstrap4\BootstrapPluginAsset::class => [
                    'js' => [
                        YII_ENV_DEV ? 'js/bootstrap.bundle.js' : '//stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js',
                    ],
                    'jsOptions' => [
                        'crossorigin' => 'anonymous',
                        'integrity' => YII_ENV_DEV ? false : 'sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd',
                    ],
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
            'class' => \himiklab\yii2\recaptcha\ReCaptchaConfig::class,
            'siteKeyV2' => $params['reCaptcha-siteKey'],
            'secretV2' => $params['reCaptcha-secret'],
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
        'clickApi' => [
            'class' => \common\components\click\ClickApi::class,
            'paymentUrl' => $params['click-url'],
            'merchantId' => $params['click-merchantId'],
            'serviceId' => $params['click-serviceId'],
            'merchantUserId' => $params['click-merchantUserId'],
            'secretKey' => $params['click-secretKey'],
        ],
        'paymeApi' => [
            'class' => \common\components\payme\PaymeApi::class,
            'paymentUrl' => $params['payme-url'],
            'merchantId' => $params['payme-merchantId'],
            'login' => $params['payme-login'],
            'password' => $params['payme-password'],
        ],
        'smsBrokerApi' => [
            'class' => \common\components\SmsBroker\SmsBrokerApi::class,
            'baseUrl' => $params['smsbroker-url'],
            'login' => $params['smsbroker-login'],
            'password' => $params['smsbroker-password'],
            'sender' => $params['smsbroker-sender-name'],
        ],
        'ageValidator' => [
            'class' => \common\components\AgeValidator::class,
        ],
        'apelsinApi' => [
            'class' => \common\components\apelsin\ApelsinApi::class,
            'paymentUrl' => $params['apelsin-url'],
            'cashId' => $params['apelsin-cashId'],
            'login' => $params['apelsin-login'],
            'password' => $params['apelsin-password'],
        ],
        'payboxApi' => [
            'class' => \common\components\paybox\PayboxApi::class,
            'merchantId' => $params['paybox-merchantId'],
            'secretKey' => $params['paybox-secretKey'],
        ],
        'appPaymeApi' => [
            'class' => \common\components\AppPayme\AppPaymeApi::class,
            'login' => $params['app-payme-login'],
            'password' => $params['app-payme-password'],
            'subjectMap' => $params['app-payme-subject-map'],
        ],
        'appApelsinApi' => [
            'class' => \common\components\AppApelsin\AppApelsinApi::class,
            'login' => $params['app-apelsin-login'],
            'password' => $params['app-apelsin-password'],
            'subjectMap' => $params['app-apelsin-subject-map'],
        ],
        'paynetApi' => [
            'class' => \common\components\paynet\PaynetApi::class,
            'login' => $params['paynet-login'],
            'password' => $params['paynet-password'],
            'subjectMap' => $params['paynet-subject-map'],
        ],
        'api' => [
            'class' => \common\components\ApiComponent::class,
            'url' => $params['api-url'],
            'login' => $params['api-login'],
            'password' => $params['api-password'],
        ]
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
