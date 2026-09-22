<?php
define('NO_LOGIN', true);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
include_once __DIR__ . '/../config.php';

// Uso: php _crons/make_admin.php email@ejemplo.com
[, $email] = $argv + [null, null];
if (!$email) {
    fwrite(STDERR, "Uso: php _crons/make_admin.php email@ejemplo.com\n");
    exit(1);
}

$stmt = $mysqli->prepare('UPDATE users SET is_admin = 1 WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$ok = $stmt->affected_rows > 0;
$stmt->close();

if ($ok) {
    echo "Listo: $email ya es administrador de la plataforma.\n";
} else {
    fwrite(STDERR, "No se ha encontrado ningún usuario con ese email.\n");
    exit(1);
}
