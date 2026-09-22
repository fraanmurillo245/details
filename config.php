<?php
define('IN_APP', true);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log');
error_reporting(E_ALL);

$_config = [
    'url'    => 'https://app.detailsinvitaciones.com',
    'secret' => 'CAMBIAR: php -r "echo bin2hex(random_bytes(32));"',
    // Módulos accesibles sin login: la invitación pública, su RSVP, el alta
    // de cuentas y la recuperación de contraseña.
    'public_modules' => ['invite', 'signup', 'forgot'],
    // Sugerencia de diseño por IA (fase 2): clave por variable de entorno,
    // nunca hardcodeada ni versionada.
    'anthropic_api_key' => getenv('ANTHROPIC_API_KEY') ?: '',
    // Pago único (tarifa plana) vía Stripe Checkout, exigido al publicar la
    // invitación (no al darse de alta). El importe se guarda en la tabla
    // settings (editable desde /admin/settings/), no aquí. Sin esta clave,
    // publicar es gratis (útil en desarrollo).
    'stripe_secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
    // Envío de correo. Sin smtp_host configurado, se usa mail() de PHP
    // (requiere sendmail/postfix local en el servidor).
    'smtp_host'      => getenv('SMTP_HOST') ?: '',
    'smtp_port'      => (int)(getenv('SMTP_PORT') ?: 587),
    'smtp_secure'    => getenv('SMTP_SECURE') ?: 'tls', // tls | ssl | none
    'smtp_user'      => getenv('SMTP_USER') ?: '',
    'smtp_pass'      => getenv('SMTP_PASS') ?: '',
    'mail_from'      => getenv('MAIL_FROM') ?: 'no-reply@detailsinvitaciones.com',
    'mail_from_name' => getenv('MAIL_FROM_NAME') ?: 'details',
];
date_default_timezone_set('Europe/Madrid');
mb_internal_encoding('UTF-8');

include_once __DIR__ . '/inc/db.php';     // $mysqli
include_once __DIR__ . '/inc/vars.php';   // $_module/$_section/$_id + u()
include_once __DIR__ . '/inc/i18n.php';   // $_lang + t()

// Autoload: la clase Foo vive en class/foo.php
spl_autoload_register(function ($cls) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $cls)) return;
    $f = __DIR__ . '/class/' . strtolower($cls) . '.php';
    if (is_file($f)) include_once $f;
});

$app = new App();
$header = new Headers();
$header->checkHeaders();

// Auth por cookie firmada uid:hmac_sha256(uid, secret). Las páginas/cron que no
// requieren login definen NO_LOGIN antes de incluir config.php. Además, los
// módulos listados en public_modules (la invitación pública) nunca la exigen.
$_is_public_module = in_array($_module, $_config['public_modules'], true);
if (!defined('NO_LOGIN') && !$_is_public_module) {
    $logged = false;
    $_user = null;
    if (!empty($_COOKIE['auth'])) {
        [$uid, $sig] = array_pad(explode(':', $_COOKIE['auth'], 2), 2, '');
        if (ctype_digit($uid) && hash_equals(hash_hmac('sha256', $uid, $_config['secret']), $sig)) {
            $stmt = $mysqli->prepare('SELECT id_user, id_account, email, rol, is_admin FROM users WHERE id_user = ? AND active = 1 LIMIT 1');
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $_user = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();
            $logged = (bool)$_user;
        }
    }
    if (!$logged) { $app->redirect('login.php'); }
}
