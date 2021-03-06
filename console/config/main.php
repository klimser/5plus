<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'user' => [
            'class' => \console\components\User::class,
            'identityClass' => \common\models\User::class,
        ],
        'urlManager' => [
            'scriptUrl' => 'https://5plus.uz',
            'baseUrl' => 'https://5plus.uz',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'normalizer' => [
                'class' => \yii\web\UrlNormalizer::class,
            ],
            'rules' => [
                'pay/<key:[a-z0-9]+>' => 'payment/link',
            ],
        ],
    ],
    'params' => $params,
];
