<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'authManager' => [
            'class' => \yii\rbac\PhpManager::class,
            'ruleFile' => '@backend/config/rbac/rules.php',
            'itemFile' => '@backend/config/rbac/items.php',
            'assignmentFile' => '@backend/config/rbac/assignments.php',
            'defaultRoles' => ['root', 'manager', 'pupil', 'parents', 'teacher'],
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
            'rules' => [
                [
                    'pattern' => 'user/index/<year:(\d{4}|-1)>/<letter:([A-ZА-ЯЁ]|ALL)>/<page>',
                    'route' => 'user/index',
                    'defaults' => ['page' => 1, 'letter' => 'ALL', 'year' => -1],
                ],
            ]
        ],
        'request' => [
            'csrfParam' => '_csrf_be',
            'csrfCookie' => [
                'httpOnly' => true,
                'secure' => !YII_ENV_DEV,
                'sameSite' => (PHP_VERSION_ID >= 70300 ? \yii\web\Cookie::SAME_SITE_LAX : null),
            ],
        ],
        'assetManager' => [
            'bundles' => [
                \yii\jui\JuiAsset::class => [
                    'js' => [
                        YII_ENV_DEV ? 'jquery-ui.js' : '//code.jquery.com/ui/1.12.1/jquery-ui.min.js',
                    ],
                    'jsOptions' => [
                        'crossorigin' => 'anonymous',
                    ],
                    'css' => [
                        YII_ENV_DEV ? 'themes/base/jquery-ui.css' : '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.min.css',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
];
