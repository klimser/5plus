<?php
return [
    'noticeEmail' => 'klimser@inbox.ru',
    'mailFrom' => 'robot.5plus@yandex.ru',
    'siteUrl' => 'http://5plus.test',

    'db-dsn' => 'mysql:host=localhost;dbname=5plus',
    'db-username' => 'root',
    'db-password' => '1234',
    'db-tablePrefix' => 'cms_',

    'tinifyKey' => '',

    'reCaptcha-siteKey' => '-',
    'reCaptcha-secret' => '-',

    'paymo-url' => 'https://test.checkout.pays.uz',
    'paymo-storeId' => '20',
    'paymo-key' => '',
    'paymo-login' => 'qfeLfE7chea_toM129_0uGDyqJ4a',
    'paymo-password' => 'dW0hwo8x0e8Y9N8oW1vEbClG5Dca',

    'paygram-login' => 'qfeLfE7chea_toM129_0uGDyqJ4a',
    'paygram-password' => 'dW0hwo8x0e8Y9N8oW1vEbClG5Dca',
    'paygram-template-map' => [
        \common\models\Notify::TEMPLATE_PUPIL_DEBT => 18,
        \common\models\Notify::TEMPLATE_PUPIL_LOW => 19,
        \common\models\Notify::TEMPLATE_PARENT_DEBT => 20,
        \common\models\Notify::TEMPLATE_PARENT_LOW => 21,
    ],
];