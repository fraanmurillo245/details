<?php
define('NO_LOGIN', true);
include_once __DIR__ . '/config.php';

setcookie('auth', '', ['expires' => time() - 3600, 'path' => '/']);
$app->redirect('login.php');
