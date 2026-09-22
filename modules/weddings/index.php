<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$stmt = $app->db->prepare(
    'SELECT id_wedding, slug, partner1_name, partner2_name, event_date, status
     FROM weddings WHERE id_account = ? ORDER BY id_wedding DESC'
);
$stmt->bind_param('i', $idAccount);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="heading text-2xl"><?= App::e(t('nav_weddings')) ?></h1>
    <a href="<?= App::e(u('weddings', 'add')) ?>" class="btn-brand"><?= App::e(t('add')) ?></a>
</div>

<div class="card overflow-x-auto">
<table class="w-full text-sm" id="tbl-weddings">
    <thead>
        <tr class="border-b border-gray-200 dark:border-gray-800 text-left">
            <th class="px-4 py-2"><?= App::e(t('couple')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('event_date')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('status')) ?></th>
            <th class="px-4 py-2"></th>
        </tr>
        <tr class="border-b border-gray-200 dark:border-gray-800">
            <th class="px-4 py-1"><input data-table="#tbl-weddings" data-filter-col="0" oninput="filterColumn(this)" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-transparent px-2 py-1 text-xs"></th>
            <th class="px-4 py-1"></th>
            <th class="px-4 py-1"><input data-table="#tbl-weddings" data-filter-col="2" oninput="filterColumn(this)" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-transparent px-2 py-1 text-xs"></th>
            <th class="px-4 py-1"></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr class="border-b border-gray-100 dark:border-gray-800">
            <td class="px-4 py-2"><?= App::e($r['partner1_name'] . ' & ' . $r['partner2_name']) ?></td>
            <td class="px-4 py-2"><?= App::e($r['event_date'] ? date('d/m/Y', strtotime($r['event_date'])) : '-') ?></td>
            <td class="px-4 py-2">
                <span class="rounded-full px-2 py-0.5 text-xs <?= $r['status'] === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' ?>">
                    <?= App::e(t($r['status'])) ?>
                </span>
            </td>
            <td class="px-4 py-2 text-right space-x-2">
                <a href="<?= App::e(u('weddings', 'add', $r['id_wedding'])) ?>" class="btn" data-tip="<?= App::e(t('edit')) ?>">✎</a>
                <button type="button" class="btn" data-tip="<?= App::e(t('delete')) ?>"
                        onclick="confirmDelete('weddings', <?= (int)$r['id_wedding'] ?>, '<?= App::e(t('confirm_delete')) ?>')">🗑</button>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500"><?= App::e(t('no_results')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
