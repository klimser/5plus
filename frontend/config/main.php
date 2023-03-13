<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'view' => [
            'class' => \frontend\components\extended\View::class,
        ],
        'request' => [
            'enableCsrfValidation' => false,
            'csrfParam' => '_csrf_fe',
            'csrfCookie' => [
                'httpOnly' => true,
                'secure' => !YII_ENV_DEV,
                'sameSite' => (PHP_VERSION_ID >= 70300 ? \yii\web\Cookie::SAME_SITE_LAX : null),
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            //'suffix' => '.html',
            'normalizer' => [
                'class' => \yii\web\UrlNormalizer::class,
            ],
            'rules' => [
                ['class' => \frontend\components\WebpageUrlRule::class],
                'pay/<key:[a-z0-9]+>' => 'payment/link',
                'api/payment/apelsin/info' => 'api/app-apelsin-info',
                'api/payment/apelsin/check' => 'api/app-apelsin-check',
                'api/payment/apelsin/pay' => 'api/app-apelsin-pay',
            ],
        ],
    ],
    'params' => $params,
];
