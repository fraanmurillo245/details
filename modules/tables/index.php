<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$wedding = Wedding::currentFor($app, $idAccount, (int)$_id);

if (!$wedding) {
    echo '<p>' . App::e(t('no_wedding_yet')) . ' <a class="btn-brand" href="' . App::e(u('weddings', 'add')) . '">' . App::e(t('add')) . '</a></p>';
    return;
}
$idWedding = (int)$wedding['id_wedding'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $capacity = max(1, min(50, (int)($_POST['capacity'] ?? 8)));
    if ($name !== '') {
        $stmt = $app->db->prepare('INSERT INTO seating_tables (id_wedding, name, capacity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
        $stmt->bind_param('isi', $idWedding, $name, $capacity);
        $stmt->execute();
        $stmt->close();
        $app->log('table.create', $idWedding, $idAccount);
    }
    $app->redirect(u('tables', 'index', $idWedding));
}

$stmt = $app->db->prepare(
    'SELECT t.id_table, t.name, t.capacity, COUNT(g.id_guest) AS assigned
     FROM seating_tables t
     LEFT JOIN guests g ON g.id_table = t.id_table
     WHERE t.id_wedding = ?
     GROUP BY t.id_table
     ORDER BY t.name ASC'
);
$stmt->bind_param('i', $idWedding);
$stmt->execute();
$tables = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="heading text-2xl"><?= App::e(t('nav_tables')) ?> — <?= App::e($wedding['partner1_name'] . ' & ' . $wedding['partner2_name']) ?></h1>
</div>

<div class="grid grid-cols-3 gap-6">
    <div class="card p-6 space-y-3">
        <h2 class="font-medium text-sm"><?= App::e(t('add_table')) ?></h2>
        <form method="post" class="space-y-3">
            <div>
                <label class="block text-xs mb-1"><?= App::e(t('table_name')) ?></label>
                <input name="name" required placeholder="Mesa 1" class="field">
            </div>
            <div>
                <label class="block text-xs mb-1"><?= App::e(t('table_capacity')) ?></label>
                <input type="number" name="capacity" min="1" max="50" value="8" class="field">
            </div>
            <button type="submit" class="btn-brand w-full justify-center"><?= App::e(t('add')) ?></button>
        </form>
    </div>

    <div class="col-span-2 card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-800 text-left">
                    <th class="px-4 py-2"><?= App::e(t('table_name')) ?></th>
                    <th class="px-4 py-2"><?= App::e(t('table_capacity')) ?></th>
                    <th class="px-4 py-2"><?= App::e(t('table_assigned')) ?></th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tables as $t): ?>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <td class="px-4 py-2"><?= App::e($t['name']) ?></td>
                    <td class="px-4 py-2"><?= (int)$t['capacity'] ?></td>
                    <td class="px-4 py-2">
                        <span class="rounded-full px-2 py-0.5 text-xs <?= (int)$t['assigned'] > (int)$t['capacity'] ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' ?>">
                            <?= (int)$t['assigned'] ?> / <?= (int)$t['capacity'] ?>
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right">
                        <button type="button" class="btn" data-tip="<?= App::e(t('delete')) ?>"
                                onclick="confirmDelete('tables', <?= (int)$t['id_table'] ?>, '<?= App::e(t('confirm_delete')) ?>')">🗑</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$tables): ?>
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500"><?= App::e(t('no_results')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
