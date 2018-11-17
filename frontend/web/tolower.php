<?php

list($url, $query) = explode('?', $_SERVER['REQUEST_URI']);
header('Location: https://' . $_SERVER['HTTP_HOST'] . mb_strtolower(urldecode($url), 'UTF-8') . ($query ? '?' . $query : ''), true, 301);