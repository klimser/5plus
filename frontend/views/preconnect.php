<?php

$hosts = [
    'https://www.gstatic.com',
    'https://www.google.com',
    'https://connect.facebook.net',
    'https://www.googletagmanager.com',
    'https://mc.yandex.ru',
    'https://maps.googleapis.com',
    'https://maps.gstatic.com',
    'https://www.google-analytics.com',
];

foreach ($hosts as $host): ?>
<link rel="preconnect" href="<?= $host; ?>">
<link rel="dns-prefetch" href="<?= $host; ?>">
<?php
endforeach;
