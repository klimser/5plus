<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-external',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'external\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
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
        ],
        'request' => [
            'enableCsrfValidation' => false,
        ],
        'externalBasicAuth' => [
            'class'             => 'skeeks\yii2\httpBasicAuth\HttpBasicAuthComponent',
            'login'             => $params['external_login'],
            'password'          => $params['external_password'],
        ],
    ],
    'params' => $params,
];
