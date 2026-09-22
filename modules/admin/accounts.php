<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

if (empty($_user['is_admin'])) { $app->redirect(u('dashboard')); }

$rows = $app->db->query(
    "SELECT a.id_account, a.name, a.email, a.created_at,
            (SELECT COUNT(*) FROM weddings w WHERE w.id_account = a.id_account) AS weddings_count,
            (SELECT COUNT(*) FROM weddings w WHERE w.id_account = a.id_account AND w.status = 'published') AS published_count,
            (SELECT COALESCE(SUM(p.amount_cents),0) FROM payments p WHERE p.id_account = a.id_account AND p.status = 'paid') AS paid_cents
     FROM accounts a
     ORDER BY a.id_account DESC"
)->fetch_all(MYSQLI_ASSOC);
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="heading text-2xl"><?= App::e(t('admin_accounts_title')) ?></h1>
    <a href="<?= App::e(u('admin')) ?>" class="btn"><?= App::e(t('admin_back_to_dashboard')) ?></a>
</div>

<div class="card overflow-x-auto">
<table class="w-full text-sm" id="tbl-accounts">
    <thead>
        <tr class="border-b border-gray-200 dark:border-gray-800 text-left">
            <th class="px-4 py-2"><?= App::e(t('couple')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('email')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('nav_weddings')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('admin_paid')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('admin_created')) ?></th>
        </tr>
        <tr class="border-b border-gray-200 dark:border-gray-800">
            <th class="px-4 py-1"><input data-table="#tbl-accounts" data-filter-col="0" oninput="filterColumn(this)" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-transparent px-2 py-1 text-xs"></th>
            <th class="px-4 py-1"><input data-table="#tbl-accounts" data-filter-col="1" oninput="filterColumn(this)" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-transparent px-2 py-1 text-xs"></th>
            <th class="px-4 py-1"></th>
            <th class="px-4 py-1"></th>
            <th class="px-4 py-1"></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr class="border-b border-gray-100 dark:border-gray-800">
            <td class="px-4 py-2"><?= App::e($r['name']) ?></td>
            <td class="px-4 py-2 text-gray-500"><?= App::e($r['email']) ?></td>
            <td class="px-4 py-2"><?= (int)$r['published_count'] ?> / <?= (int)$r['weddings_count'] ?></td>
            <td class="px-4 py-2">
                <?php if ((int)$r['paid_cents'] > 0): ?>
                    <span class="rounded-full px-2 py-0.5 text-xs bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300"><?= App::e(App::money((int)$r['paid_cents'])) ?></span>
                <?php else: ?>
                    <span class="rounded-full px-2 py-0.5 text-xs bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300"><?= App::e(t('admin_not_paid')) ?></span>
                <?php endif; ?>
            </td>
            <td class="px-4 py-2 text-gray-500"><?= App::e(date('d/m/Y', strtotime($r['created_at']))) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500"><?= App::e(t('no_results')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
