<?php
define('NO_LOGIN', true);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
include_once __DIR__ . '/../config.php';

// Uso: php _crons/create_account.php "Ana & Juan" ana@example.com secreto123
[, $name, $email, $password] = $argv + [null, null, null, null];
if (!$name || !$email || !$password) {
    fwrite(STDERR, "Uso: php _crons/create_account.php \"Nombre cuenta\" email password\n");
    exit(1);
}

$stmt = $mysqli->prepare('INSERT INTO accounts (name, email, plan, plan_expires_at, active, created_at, updated_at) VALUES (?, ?, "flat", DATE_ADD(NOW(), INTERVAL 1 YEAR), 1, NOW(), NOW())');
$stmt->bind_param('ss', $name, $email);
$stmt->execute();
$idAccount = (int)$stmt->insert_id;
$stmt->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare('INSERT INTO users (id_account, email, password, rol, active, created_at, updated_at) VALUES (?, ?, ?, "owner", 1, NOW(), NOW())');
$stmt->bind_param('iss', $idAccount, $email, $hash);
$stmt->execute();
$stmt->close();

echo "Cuenta #$idAccount creada. Usuario: $email\n";
