<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

if (empty($_user['is_admin'])) { $app->redirect(u('dashboard')); }

$totalAccounts = (int)$app->db->query('SELECT COUNT(*) AS n FROM accounts')->fetch_assoc()['n'];
$payingAccounts = (int)$app->db->query("SELECT COUNT(DISTINCT id_account) AS n FROM payments WHERE status = 'paid'")->fetch_assoc()['n'];
$totalRevenueCents = (int)$app->db->query("SELECT COALESCE(SUM(amount_cents),0) AS n FROM payments WHERE status = 'paid'")->fetch_assoc()['n'];
$monthRevenueCents = (int)$app->db->query("SELECT COALESCE(SUM(amount_cents),0) AS n FROM payments WHERE status = 'paid' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetch_assoc()['n'];
$totalWeddings = (int)$app->db->query('SELECT COUNT(*) AS n FROM weddings')->fetch_assoc()['n'];
$publishedWeddings = (int)$app->db->query("SELECT COUNT(*) AS n FROM weddings WHERE status = 'published'")->fetch_assoc()['n'];
$totalGuests = (int)$app->db->query('SELECT COUNT(*) AS n FROM guests')->fetch_assoc()['n'];
$newAccounts30d = (int)$app->db->query('SELECT COUNT(*) AS n FROM accounts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetch_assoc()['n'];

$conversionRate = $totalAccounts > 0 ? round($payingAccounts / $totalAccounts * 100, 1) : 0;

$recentPayments = $app->db->query(
    "SELECT p.id_payment, p.amount_cents, p.currency, p.created_at, a.name AS account_name, a.email
     FROM payments p LEFT JOIN accounts a ON a.id_account = p.id_account
     WHERE p.status = 'paid' ORDER BY p.id_payment DESC LIMIT 12"
)->fetch_all(MYSQLI_ASSOC);

$currentFeeCents = Settings::flatFeeCents($app);
$currentFeeCurrency = Settings::flatFeeCurrency($app);
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="heading text-2xl"><?= App::e(t('admin_dashboard')) ?></h1>
    <span class="rounded-full px-3 py-1 text-xs" style="background: var(--brand-tint); color: var(--brand-solid-dark)">
        <?= App::e(t('admin_current_price')) ?>: <?= App::e(App::money($currentFeeCents, $currentFeeCurrency)) ?>
    </span>
</div>

<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="card p-4">
        <div class="text-2xl font-semibold"><?= $totalAccounts ?></div>
        <div class="text-sm opacity-70"><?= App::e(t('admin_total_accounts')) ?></div>
        <div class="text-xs text-gray-400 mt-1"><?= App::e(t('admin_new_30d', $newAccounts30d)) ?></div>
    </div>
    <div class="card p-4">
        <div class="text-2xl font-semibold text-green-600"><?= $payingAccounts ?></div>
        <div class="text-sm opacity-70"><?= App::e(t('admin_paying_accounts')) ?></div>
        <div class="text-xs text-gray-400 mt-1"><?= App::e(t('admin_conversion', $conversionRate)) ?></div>
    </div>
    <div class="card p-4">
        <div class="text-2xl font-semibold"><?= App::e(App::money($totalRevenueCents)) ?></div>
        <div class="text-sm opacity-70"><?= App::e(t('admin_total_revenue')) ?></div>
        <div class="text-xs text-gray-400 mt-1"><?= App::e(t('admin_revenue_month', App::money($monthRevenueCents))) ?></div>
    </div>
    <div class="card p-4">
        <div class="text-2xl font-semibold"><?= $publishedWeddings ?> / <?= $totalWeddings ?></div>
        <div class="text-sm opacity-70"><?= App::e(t('admin_published_weddings')) ?></div>
        <div class="text-xs text-gray-400 mt-1"><?= App::e(t('admin_total_guests', $totalGuests)) ?></div>
    </div>
</div>

<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 card overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="font-medium text-sm"><?= App::e(t('admin_recent_payments')) ?></h2>
            <a href="/ajax/admin/export_payments.php" class="btn text-xs"><?= App::e(t('export_csv')) ?></a>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-800 text-left">
                    <th class="px-4 py-2"><?= App::e(t('couple')) ?></th>
                    <th class="px-4 py-2"><?= App::e(t('email')) ?></th>
                    <th class="px-4 py-2"><?= App::e(t('admin_amount')) ?></th>
                    <th class="px-4 py-2"><?= App::e(t('admin_date')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentPayments as $p): ?>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <td class="px-4 py-2"><?= App::e($p['account_name']) ?></td>
                    <td class="px-4 py-2 text-gray-500"><?= App::e($p['email']) ?></td>
                    <td class="px-4 py-2"><?= App::e(App::money((int)$p['amount_cents'], $p['currency'])) ?></td>
                    <td class="px-4 py-2 text-gray-500"><?= App::e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentPayments): ?>
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500"><?= App::e(t('no_results')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card p-6 space-y-3">
        <h2 class="font-medium text-sm"><?= App::e(t('admin_quick_links')) ?></h2>
        <a href="<?= App::e(u('admin', 'accounts')) ?>" class="btn w-full justify-center"><?= App::e(t('admin_view_accounts')) ?></a>
        <a href="<?= App::e(u('admin', 'settings')) ?>" class="btn w-full justify-center"><?= App::e(t('admin_edit_price')) ?></a>
    </div>
</div>
