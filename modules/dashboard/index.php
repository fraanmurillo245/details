<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$wedding = Wedding::currentFor($app, $idAccount);

$stats = ['guests' => 0, 'confirmed' => 0, 'declined' => 0, 'pending' => 0];
if ($wedding) {
    $idWedding = (int)$wedding['id_wedding'];
    $stmt = $app->db->prepare(
        "SELECT rsvp_status, COUNT(*) AS n FROM guests WHERE id_wedding = ? GROUP BY rsvp_status"
    );
    $stmt->bind_param('i', $idWedding);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $stats[$row['rsvp_status']] = (int)$row['n'];
        $stats['guests'] += (int)$row['n'];
    }
    $stmt->close();
}
?>
<h1 class="text-lg font-semibold mb-4"><?= App::e(t('nav_dashboard')) ?></h1>

<?php if (!$wedding): ?>
<div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
    <p class="mb-3"><?= App::e(t('no_wedding_yet')) ?></p>
    <a href="<?= App::e(u('weddings', 'add')) ?>" class="btn"><?= App::e(t('add')) ?></a>
</div>
<?php else: ?>
<div class="mb-4 text-sm opacity-70"><?= App::e($wedding['partner1_name'] . ' & ' . $wedding['partner2_name']) ?></div>
<div class="grid grid-cols-4 gap-4">
    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
        <div class="text-2xl font-semibold"><?= (int)$stats['guests'] ?></div>
        <div class="text-sm opacity-70"><?= App::e(t('nav_guests')) ?></div>
    </div>
    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
        <div class="text-2xl font-semibold text-green-600"><?= (int)$stats['confirmed'] ?></div>
        <div class="text-sm opacity-70"><?= App::e(t('rsvp_confirmed')) ?></div>
    </div>
    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
        <div class="text-2xl font-semibold text-red-600"><?= (int)$stats['declined'] ?></div>
        <div class="text-sm opacity-70"><?= App::e(t('rsvp_declined')) ?></div>
    </div>
    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
        <div class="text-2xl font-semibold text-gray-500"><?= (int)$stats['pending'] ?></div>
        <div class="text-sm opacity-70"><?= App::e(t('rsvp_pending')) ?></div>
    </div>
</div>
<?php endif; ?>
