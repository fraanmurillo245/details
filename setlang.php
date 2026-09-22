<?php
define('NO_LOGIN', true);
include_once __DIR__ . '/config.php';

// Selector de idioma: funciona tanto logueado (panel) como sin loguear
// (login/alta), por eso vive fuera del sistema de módulos. Enlace simple
// por GET (sin JS), no cambia nada sensible: solo la cookie de idioma y,
// si hay sesión, la preferencia guardada en users.language.
const ALLOWED_LANGS = ['es', 'en', 'fr', 'it'];

$lang = (string)($_GET['lang'] ?? '');
if (in_array($lang, ALLOWED_LANGS, true)) {
    setcookie('lang', $lang, ['expires' => time() + 60 * 60 * 24 * 365, 'path' => '/', 'samesite' => 'Lax']);

    // Auth por cookie replicada aquí porque NO_LOGIN no rellena $_user.
    if (!empty($_COOKIE['auth'])) {
        [$uid, $sig] = array_pad(explode(':', $_COOKIE['auth'], 2), 2, '');
        if (ctype_digit($uid) && hash_equals(hash_hmac('sha256', $uid, $_config['secret']), $sig)) {
            $stmt = $mysqli->prepare('UPDATE users SET language = ? WHERE id_user = ?');
            $stmt->bind_param('si', $lang, $uid);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$redirect = (string)($_GET['redirect'] ?? '/');
if ($redirect === '' || $redirect[0] !== '/' || (isset($redirect[1]) && $redirect[1] === '/')) {
    $redirect = '/';
}
$app->redirect($redirect);
