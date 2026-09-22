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

$stmt = $app->db->prepare(
    'SELECT g.id_guest, g.name, g.email, g.phone, g.group_name, g.rsvp_status, g.rsvp_companions, t.name AS table_name,
            GROUP_CONCAT(a.name SEPARATOR ", ") AS allergens
     FROM guests g
     LEFT JOIN guest_allergens ga ON ga.id_guest = g.id_guest
     LEFT JOIN allergens a ON a.id_allergen = ga.id_allergen
     LEFT JOIN seating_tables t ON t.id_table = g.id_table
     WHERE g.id_wedding = ?
     GROUP BY g.id_guest
     ORDER BY g.name ASC'
);
$stmt->bind_param('i', $idWedding);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statusLabel = ['pending' => t('rsvp_pending'), 'confirmed' => t('rsvp_confirmed'), 'declined' => t('rsvp_declined')];
$statusClass = [
    'pending'   => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    'confirmed' => 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
    'declined'  => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
];
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="heading text-2xl"><?= App::e(t('nav_guests')) ?> — <?= App::e($wedding['partner1_name'] . ' & ' . $wedding['partner2_name']) ?></h1>
    <div class="flex items-center gap-2">
        <a href="<?= App::e(u('guests', 'import', $idWedding)) ?>" class="btn"><?= App::e(t('import_csv')) ?></a>
        <a href="<?= App::e('/ajax/guests/export.php?id_wedding=' . $idWedding) ?>" class="btn"><?= App::e(t('export_csv')) ?></a>
        <a href="<?= App::e(rtrim(u('guests', 'add', '0'), '/') . '/' . $idWedding . '/') ?>" class="btn-brand"><?= App::e(t('add')) ?></a>
    </div>
</div>

<div class="card overflow-x-auto">
<table class="w-full text-sm" id="tbl-guests">
    <thead>
        <tr class="border-b border-gray-200 dark:border-gray-800 text-left">
            <th class="px-4 py-2"><?= App::e(t('name')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('group')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('allergens')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('table')) ?></th>
            <th class="px-4 py-2"><?= App::e(t('rsvp_status')) ?></th>
            <th class="px-4 py-2"></th>
        </tr>
        <tr class="border-b border-gray-200 dark:border-gray-800">
            <th class="px-4 py-1"><input data-table="#tbl-guests" data-filter-col="0" oninput="filterColumn(this)" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-transparent px-2 py-1 text-xs"></th>
            <th class="px-4 py-1"><input data-table="#tbl-guests" data-filter-col="1" oninput="filterColumn(this)" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-transparent px-2 py-1 text-xs"></th>
            <th class="px-4 py-1"><input data-table="#tbl-guests" data-filter-col="2" oninput="filterColumn(this)" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-transparent px-2 py-1 text-xs"></th>
            <th class="px-4 py-1"></th>
            <th class="px-4 py-1"></th>
            <th class="px-4 py-1"></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr class="border-b border-gray-100 dark:border-gray-800">
            <td class="px-4 py-2"><?= App::e($r['name']) ?></td>
            <td class="px-4 py-2"><?= App::e($r['group_name']) ?></td>
            <td class="px-4 py-2 text-amber-700 dark:text-amber-400"><?= App::e($r['allergens'] ?: '-') ?></td>
            <td class="px-4 py-2"><?= App::e($r['table_name'] ?: '-') ?></td>
            <td class="px-4 py-2">
                <span class="rounded-full px-2 py-0.5 text-xs <?= $statusClass[$r['rsvp_status']] ?>"><?= App::e($statusLabel[$r['rsvp_status']]) ?></span>
            </td>
            <td class="px-4 py-2 text-right space-x-2">
                <a href="<?= App::e(u('guests', 'add', $r['id_guest'])) ?>" class="btn" data-tip="<?= App::e(t('edit')) ?>">✎</a>
                <button type="button" class="btn" data-tip="<?= App::e(t('delete')) ?>"
                        onclick="confirmDelete('guests', <?= (int)$r['id_guest'] ?>, '<?= App::e(t('confirm_delete')) ?>')">🗑</button>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500"><?= App::e(t('no_results')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
