<?php
define('NO_LOGIN', true);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
include_once __DIR__ . '/../config.php';

// Cron diario (cPanel > Cron Jobs): php /ruta/a/_crons/send_reminders.php
// Recordatorios automáticos a cuentas que aún no han pagado la tarifa plana:
//  - 3 días sin conexión: recuerda que la invitación sigue ahí, lista para retomar.
//  - 7 días sin conexión: más personal, ofrece ayuda / pregunta si algo no convenció.
// reminder_log evita reenviar el mismo aviso dos veces (UNIQUE id_account+reminder_type).

$sql = "SELECT u.id_user, u.id_account, u.email, u.language, a.name AS account_name,
               COALESCE(u.last_login_at, u.created_at) AS last_seen
        FROM users u
        JOIN accounts a ON a.id_account = u.id_account
        WHERE u.rol = 'owner' AND u.active = 1 AND a.active = 1
          AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.id_account = u.id_account AND p.status = 'paid')
          AND COALESCE(u.last_login_at, u.created_at) IS NOT NULL";
$result = $mysqli->query($sql);
if (!$result) {
    fwrite(STDERR, "Error consultando cuentas: " . $mysqli->error . "\n");
    exit(1);
}

$logStmt = $mysqli->prepare('INSERT IGNORE INTO reminder_log (id_account, reminder_type, sent_at) VALUES (?, ?, NOW())');
$sentStmt = $mysqli->prepare("SELECT 1 FROM reminder_log WHERE id_account = ? AND reminder_type = ? LIMIT 1");

$sent3 = 0;
$sent7 = 0;

while ($row = $result->fetch_assoc()) {
    $idAccount = (int)$row['id_account'];
    $lang = $row['language'] ?: 'es';
    $daysSince = (int)floor((time() - strtotime($row['last_seen'])) / 86400);

    if ($daysSince >= 7) {
        $type = '7day';
    } elseif ($daysSince >= 3) {
        $type = '3day';
    } else {
        continue;
    }

    $sentStmt->bind_param('is', $idAccount, $type);
    $sentStmt->execute();
    $already = (bool)$sentStmt->get_result()->fetch_assoc();
    if ($already) continue;

    $toName = $row['account_name'] ?: '';
    $ok = $type === '7day'
        ? Notifications::reminderDay7($row['email'], $toName, $lang)
        : Notifications::reminderDay3($row['email'], $toName, $lang);

    if (!$ok) {
        fwrite(STDERR, "Fallo enviando recordatorio $type a cuenta $idAccount ({$row['email']})\n");
        continue;
    }

    $logStmt->bind_param('is', $idAccount, $type);
    $logStmt->execute();
    if ($type === '7day') $sent7++; else $sent3++;
}

$logStmt->close();
$sentStmt->close();

echo "Recordatorios enviados: $sent3 de 3 días, $sent7 de 7 días.\n";
