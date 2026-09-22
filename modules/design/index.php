<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$wedding = Wedding::currentFor($app, $idAccount, (int)$_id);

if (!$wedding) {
    echo '<p>' . App::e(t('no_wedding_yet')) . ' <a class="btn" href="' . App::e(u('weddings', 'add')) . '">' . App::e(t('add')) . '</a></p>';
    return;
}
$idWedding = (int)$wedding['id_wedding'];

$stmt = $app->db->prepare('SELECT * FROM wedding_pages WHERE id_wedding = ? LIMIT 1');
$stmt->bind_param('i', $idWedding);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$page) {
    $stmt = $app->db->prepare('INSERT INTO wedding_pages (id_wedding, blocks_json, theme_json, created_at, updated_at) VALUES (?, "[]", "{}", NOW(), NOW())');
    $stmt->bind_param('i', $idWedding);
    $stmt->execute();
    $stmt->close();
    $page = ['blocks_json' => '[]', 'theme_json' => '{}'];
}

$availableBlocks = [
    'cover'     => t('block_cover'),
    'countdown' => t('block_countdown'),
    'location'  => t('block_location'),
    'gallery'   => t('block_gallery'),
    'gift'      => t('block_gift'),
    'rsvp'      => t('block_rsvp'),
];
$activeBlocks = json_decode($page['blocks_json'], true) ?: [];
$theme = json_decode($page['theme_json'], true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blocks = array_values(array_intersect(array_map('strval', $_POST['blocks'] ?? []), array_keys($availableBlocks)));
    $newTheme = [
        'color_primary'   => preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color_primary'] ?? '') ? $_POST['color_primary'] : '#4f46e5',
        'color_secondary' => preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color_secondary'] ?? '') ? $_POST['color_secondary'] : '#f5f5f4',
        'font'            => in_array($_POST['font'] ?? '', ['serif', 'sans', 'script'], true) ? $_POST['font'] : 'serif',
    ];
    $stmt = $app->db->prepare('UPDATE wedding_pages SET blocks_json=?, theme_json=?, updated_at=NOW() WHERE id_wedding=?');
    $blocksJson = json_encode($blocks);
    $themeJson = json_encode($newTheme);
    $stmt->bind_param('ssi', $blocksJson, $themeJson, $idWedding);
    $stmt->execute();
    $stmt->close();
    $app->log('design.update', $idWedding, $idAccount);
    $activeBlocks = $blocks;
    $theme = $newTheme;
}
?>
<h1 class="text-lg font-semibold mb-4"><?= App::e(t('nav_design')) ?> — <?= App::e($wedding['partner1_name'] . ' & ' . $wedding['partner2_name']) ?></h1>

<form method="post" class="max-w-2xl rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-6">
    <div>
        <label class="block text-sm mb-2 font-medium"><?= App::e(t('blocks')) ?></label>
        <div class="space-y-2">
        <?php foreach ($availableBlocks as $key => $label): ?>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="blocks[]" value="<?= App::e($key) ?>" <?= in_array($key, $activeBlocks, true) ? 'checked' : '' ?>>
                <?= App::e($label) ?>
            </label>
        <?php endforeach; ?>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('color_primary')) ?></label>
            <input type="color" name="color_primary" value="<?= App::e($theme['color_primary'] ?? '#4f46e5') ?>" class="h-10 w-full rounded-md border border-gray-300 dark:border-gray-700 bg-transparent">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('color_secondary')) ?></label>
            <input type="color" name="color_secondary" value="<?= App::e($theme['color_secondary'] ?? '#f5f5f4') ?>" class="h-10 w-full rounded-md border border-gray-300 dark:border-gray-700 bg-transparent">
        </div>
    </div>
    <div>
        <label class="block text-sm mb-1"><?= App::e(t('font')) ?></label>
        <select name="font" class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-transparent px-3 py-2">
            <?php foreach (['serif' => t('font_serif'), 'sans' => t('font_sans'), 'script' => t('font_script')] as $key => $label): ?>
                <option value="<?= App::e($key) ?>" <?= ($theme['font'] ?? 'serif') === $key ? 'selected' : '' ?>><?= App::e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="flex items-center gap-3">
        <button type="submit" class="btn"><?= App::e(t('save')) ?></button>
        <a href="<?= App::e($_config['url'] . '/invite/' . $wedding['slug'] . '/') ?>" target="_blank" class="btn"><?= App::e(t('preview')) ?></a>
    </div>
</form>
