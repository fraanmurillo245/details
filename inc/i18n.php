<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

$_lang = 'es';
if (!empty($_COOKIE['lang']) && preg_match('/^[a-z]{2}$/', $_COOKIE['lang'])) {
    $_lang = $_COOKIE['lang'];
}
$_langFile = __DIR__ . '/../lang/' . $_lang . '.php';
$_strings = is_file($_langFile) ? include $_langFile : [];

function t($key, ...$args) {
    global $_strings;
    $s = $_strings[$key] ?? $key;
    return $args ? vsprintf($s, $args) : $s;
}
