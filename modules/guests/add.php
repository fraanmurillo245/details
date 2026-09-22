<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$idGuest = (int)$_id;
$g = [
    'id_guest' => 0, 'id_wedding' => 0, 'name' => '', 'email' => '', 'phone' => '',
    'group_name' => '', 'max_companions' => 0, 'notes' => '',
];
$selectedAllergens = [];

if ($idGuest > 0) {
    $stmt = $app->db->prepare(
        'SELECT g.* FROM guests g INNER JOIN weddings w ON w.id_wedding = g.id_wedding
         WHERE g.id_guest = ? AND w.id_account = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $idGuest, $idAccount);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) { $app->redirect(u('guests')); }
    $g = $found;

    $stmt = $app->db->prepare('SELECT id_allergen FROM guest_allergens WHERE id_guest = ?');
    $stmt->bind_param('i', $idGuest);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $selectedAllergens[] = (int)$row['id_allergen'];
    $stmt->close();

    $idWedding = (int)$g['id_wedding'];
} else {
    $idWedding = (int)$_slug;
}

$wedding = Wedding::currentFor($app, $idAccount, $idWedding);
if (!$wedding) { $app->redirect(u('weddings')); }
$idWedding = (int)$wedding['id_wedding'];

$allergensCatalog = $app->db->query('SELECT id_allergen, name FROM allergens ORDER BY name ASC')->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)$_POST['name']);
    $email = trim((string)$_POST['email']);
    $phone = trim((string)$_POST['phone']);
    $group = trim((string)$_POST['group_name']);
    $maxCompanions = (int)$_POST['max_companions'];
    $notes = trim((string)$_POST['notes']);
    $postedAllergens = array_map('intval', $_POST['allergens'] ?? []);

    if ($idGuest > 0) {
        $stmt = $app->db->prepare(
            'UPDATE guests SET name=?, email=?, phone=?, group_name=?, max_companions=?, notes=?, updated_at=NOW() WHERE id_guest=?'
        );
        $stmt->bind_param('ssssisi', $name, $email, $phone, $group, $maxCompanions, $notes, $idGuest);
        $stmt->execute();
        $stmt->close();
        $app->log('guest.update', $idGuest, $idAccount);
    } else {
        $stmt = $app->db->prepare(
            'INSERT INTO guests (id_wedding, name, email, phone, group_name, max_companions, notes, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->bind_param('issssis', $idWedding, $name, $email, $phone, $group, $maxCompanions, $notes);
        $stmt->execute();
        $idGuest = (int)$stmt->insert_id;
        $stmt->close();
        $app->log('guest.create', $idGuest, $idAccount);
    }

    // Alérgenos: reemplazar el set completo (borrar + insertar los marcados).
    $stmt = $app->db->prepare('DELETE FROM guest_allergens WHERE id_guest = ?');
    $stmt->bind_param('i', $idGuest);
    $stmt->execute();
    $stmt->close();
    if ($postedAllergens) {
        $stmt = $app->db->prepare('INSERT INTO guest_allergens (id_guest, id_allergen, created_at) VALUES (?, ?, NOW())');
        foreach ($postedAllergens as $idAllergen) {
            $stmt->bind_param('ii', $idGuest, $idAllergen);
            $stmt->execute();
        }
        $stmt->close();
    }

    $app->redirect(u('guests', 'index', $idWedding));
}
?>
<h1 class="heading text-2xl mb-4"><?= App::e($idGuest ? t('edit') : t('add')) ?></h1>

<form method="post" class="max-w-2xl card p-6 space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('name')) ?></label>
            <input name="name" required value="<?= App::e($g['name']) ?>" class="field">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('group')) ?></label>
            <input name="group_name" value="<?= App::e($g['group_name']) ?>" class="field">
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('email')) ?></label>
            <input type="email" name="email" value="<?= App::e($g['email']) ?>" class="field">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('phone')) ?></label>
            <input name="phone" value="<?= App::e($g['phone']) ?>" class="field">
        </div>
    </div>
    <div>
        <label class="block text-sm mb-1"><?= App::e(t('max_companions')) ?></label>
        <input type="number" min="0" max="10" name="max_companions" value="<?= (int)$g['max_companions'] ?>" class="field">
    </div>
    <div>
        <label class="block text-sm mb-2"><?= App::e(t('allergens')) ?></label>
        <div class="grid grid-cols-2 gap-2">
        <?php foreach ($allergensCatalog as $a): ?>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="allergens[]" value="<?= (int)$a['id_allergen'] ?>" <?= in_array((int)$a['id_allergen'], $selectedAllergens, true) ? 'checked' : '' ?>>
                <?= App::e($a['name']) ?>
            </label>
        <?php endforeach; ?>
        </div>
    </div>
    <div>
        <label class="block text-sm mb-1"><?= App::e(t('notes')) ?></label>
        <textarea name="notes" rows="3" class="field"><?= App::e($g['notes']) ?></textarea>
    </div>
    <button type="submit" class="btn-brand"><?= App::e(t('save')) ?></button>
</form>
