<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$id = (int)$_id;
$w = [
    'id_wedding' => 0, 'slug' => '', 'partner1_name' => '', 'partner2_name' => '',
    'event_date' => '', 'venue_name' => '', 'venue_address' => '', 'status' => 'draft',
];
if ($id > 0) {
    $stmt = $app->db->prepare('SELECT * FROM weddings WHERE id_wedding = ? AND id_account = ? LIMIT 1');
    $stmt->bind_param('ii', $id, $idAccount);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($found) $w = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = _clean_var(trim((string)$_POST['slug']), '');
    $p1 = trim((string)$_POST['partner1_name']);
    $p2 = trim((string)$_POST['partner2_name']);
    $date = trim((string)$_POST['event_date']);
    $venueName = trim((string)$_POST['venue_name']);
    $venueAddr = trim((string)$_POST['venue_address']);
    $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';
    $eventDate = $date !== '' ? date('Y-m-d H:i:s', strtotime($date)) : null;

    if ($id > 0) {
        $stmt = $app->db->prepare(
            'UPDATE weddings SET slug=?, partner1_name=?, partner2_name=?, event_date=?, venue_name=?, venue_address=?, status=?, updated_at=NOW()
             WHERE id_wedding=? AND id_account=?'
        );
        $stmt->bind_param('sssssssii', $slug, $p1, $p2, $eventDate, $venueName, $venueAddr, $status, $id, $idAccount);
        $stmt->execute();
        $stmt->close();
        $app->log('wedding.update', $id, $idAccount);
    } else {
        $stmt = $app->db->prepare(
            'INSERT INTO weddings (id_account, slug, partner1_name, partner2_name, event_date, venue_name, venue_address, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->bind_param('isssssss', $idAccount, $slug, $p1, $p2, $eventDate, $venueName, $venueAddr, $status);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        $stmt->close();
        // Ficha de diseño vacía asociada a la boda recién creada.
        $stmt = $app->db->prepare('INSERT INTO wedding_pages (id_wedding, blocks_json, theme_json, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
        $emptyBlocks = '[]';
        $emptyTheme = '{}';
        $stmt->bind_param('iss', $id, $emptyBlocks, $emptyTheme);
        $stmt->execute();
        $stmt->close();
        $app->log('wedding.create', $id, $idAccount);
    }
    $app->redirect(u('weddings'));
}
?>
<h1 class="heading text-2xl mb-4"><?= App::e($id ? t('edit') : t('add')) ?></h1>

<form method="post" class="max-w-2xl card p-6 space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('partner1_name')) ?></label>
            <input name="partner1_name" required value="<?= App::e($w['partner1_name']) ?>" class="field">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('partner2_name')) ?></label>
            <input name="partner2_name" required value="<?= App::e($w['partner2_name']) ?>" class="field">
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('slug')) ?></label>
            <input name="slug" required pattern="[A-Za-z0-9][A-Za-z0-9._-]*" value="<?= App::e($w['slug']) ?>" class="field">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('event_date')) ?></label>
            <input type="datetime-local" name="event_date" value="<?= App::e($w['event_date'] ? date('Y-m-d\TH:i', strtotime($w['event_date'])) : '') ?>" class="field">
        </div>
    </div>
    <div>
        <label class="block text-sm mb-1"><?= App::e(t('venue_name')) ?></label>
        <input name="venue_name" value="<?= App::e($w['venue_name']) ?>" class="field">
    </div>
    <div>
        <label class="block text-sm mb-1"><?= App::e(t('venue_address')) ?></label>
        <input name="venue_address" value="<?= App::e($w['venue_address']) ?>" class="field">
    </div>
    <div>
        <label class="block text-sm mb-1"><?= App::e(t('status')) ?></label>
        <select name="status" class="field">
            <option value="draft" <?= $w['status'] === 'draft' ? 'selected' : '' ?>><?= App::e(t('draft')) ?></option>
            <option value="published" <?= $w['status'] === 'published' ? 'selected' : '' ?>><?= App::e(t('published')) ?></option>
        </select>
    </div>
    <button type="submit" class="btn-brand"><?= App::e(t('save')) ?></button>
</form>
