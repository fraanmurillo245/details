<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$wedding = Wedding::currentFor($app, $idAccount, (int)$_id);
if (!$wedding) { $app->redirect(u('weddings')); }
$idWedding = (int)$wedding['id_wedding'];

// Cabeceras aceptadas (es/en), no distingue mayúsculas.
$headerMap = [
    'nombre' => 'name', 'name' => 'name',
    'email' => 'email', 'correo' => 'email',
    'telefono' => 'phone', 'teléfono' => 'phone', 'phone' => 'phone',
    'grupo' => 'group_name', 'group' => 'group_name', 'group_name' => 'group_name',
    'acompanantes_max' => 'max_companions', 'acompañantes_max' => 'max_companions', 'max_companions' => 'max_companions',
    'notas' => 'notes', 'notes' => 'notes',
];

$imported = 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['csv']['tmp_name']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {
    $fh = fopen($_FILES['csv']['tmp_name'], 'r');
    if ($fh === false) {
        $error = t('import_error');
    } else {
        $header = fgetcsv($fh);
        if ($header === false) {
            $error = t('import_empty');
        } else {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '');
            $colIndex = [];
            foreach ($header as $i => $h) {
                $key = mb_strtolower(trim($h));
                if (isset($headerMap[$key])) $colIndex[$headerMap[$key]] = $i;
            }

            if (!isset($colIndex['name'])) {
                $error = t('import_no_name_column');
            } else {
                $stmt = $app->db->prepare(
                    'INSERT INTO guests (id_wedding, name, email, phone, group_name, max_companions, notes, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
                );
                while (($row = fgetcsv($fh)) !== false) {
                    $name = trim((string)($row[$colIndex['name']] ?? ''));
                    if ($name === '') continue;
                    $email = trim((string)($row[$colIndex['email']] ?? ''));
                    $phone = trim((string)($row[$colIndex['phone']] ?? ''));
                    $group = trim((string)($row[$colIndex['group_name']] ?? ''));
                    $maxCompanions = (int)($row[$colIndex['max_companions']] ?? 0);
                    $notes = trim((string)($row[$colIndex['notes']] ?? ''));
                    $stmt->bind_param('issssis', $idWedding, $name, $email, $phone, $group, $maxCompanions, $notes);
                    $stmt->execute();
                    $imported++;
                }
                $stmt->close();
                $app->log('guests.import', $idWedding, $idAccount);
            }
        }
        fclose($fh);
    }
}
?>
<h1 class="heading text-2xl mb-4"><?= App::e(t('import_guests_title')) ?></h1>

<div class="max-w-xl card p-6 space-y-4">
    <?php if ($imported > 0): ?>
        <div class="rounded-md bg-green-50 text-green-700 px-3 py-2 text-sm"><?= App::e(t('import_success', $imported)) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="rounded-md bg-red-50 text-red-700 px-3 py-2 text-sm"><?= App::e($error) ?></div>
    <?php endif; ?>

    <p class="text-sm text-gray-500"><?= App::e(t('import_hint')) ?></p>

    <form method="post" enctype="multipart/form-data" class="space-y-3">
        <input type="file" name="csv" accept=".csv,text/csv" required class="text-sm">
        <button type="submit" class="btn-brand"><?= App::e(t('import_submit')) ?></button>
    </form>

    <a href="<?= App::e('/ajax/guests/export.php?id_wedding=' . $idWedding) ?>" class="btn inline-flex"><?= App::e(t('export_csv')) ?></a>
</div>
