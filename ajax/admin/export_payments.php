<?php
include_once __DIR__ . '/../../config.php';

if (empty($_user['is_admin'])) { http_response_code(403); exit('Acceso denegado'); }

$rows = $app->db->query(
    "SELECT p.id_payment, p.stripe_session_id, p.amount_cents, p.currency, p.status, p.created_at,
            a.name AS account_name, a.email
     FROM payments p LEFT JOIN accounts a ON a.id_account = p.id_account
     ORDER BY p.id_payment DESC"
)->fetch_all(MYSQLI_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="pagos-details-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['fecha', 'cuenta', 'email', 'importe', 'moneda', 'estado', 'stripe_session_id'], ',', '"', '\\');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['created_at'], $r['account_name'], $r['email'],
        number_format($r['amount_cents'] / 100, 2, '.', ''), $r['currency'], $r['status'], $r['stripe_session_id'],
    ], ',', '"', '\\');
}
fclose($out);
