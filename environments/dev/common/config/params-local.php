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
        \common\components\SmsConfirmation::TEMPLATE_CONFIRMATION_CODE => 21,
        \common\components\AgeValidator::TEMPLATE_AGE_CONFIRMATION => 610,
    ],

    'click-url' => '',
    'click-merchantId' => '456',
    'click-serviceId' => '987',
    'click-merchantUserId' => '123',
    'click-secretKey' => 'test',

    'payme-url' => '',
    'payme-merchantId' => '',
    'payme-login' => 'test',
    'payme-password' => 'test',

    'smsbroker-url' => 'http://91.204.239.44/broker-api',
    'smsbroker-login' => 'test',
    'smsbroker-password' => 'test',
    'smsbroker-sender-name' => 'FIVEPLUS',

    'apelsin-url' => 'https://oplata.kapitalbank.uz',
    'apelsin-cashId' => 'test',
    'apelsin-login' => 'test',
    'apelsin-password' => 'test',
];
