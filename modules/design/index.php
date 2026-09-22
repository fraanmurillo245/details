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
$suggestion = json_decode($page['design_suggestion'] ?? '', true) ?: null;

function render_ai_suggestion(array $s, array $availableBlocks, int $idWedding): string
{
    $blockLabels = array_map(fn($k) => $availableBlocks[$k] ?? $k, $s['blocks'] ?? []);
    $html = '<p class="font-medium">' . App::e(t('ai_blocks_label')) . ' ' . App::e(implode(', ', $blockLabels)) . '</p>';
    $html .= '<div class="flex items-center gap-2">';
    $html .= '<span class="inline-block h-5 w-5 rounded-full border" style="background:' . App::e($s['theme']['color_primary'] ?? '') . '"></span>';
    $html .= '<span class="inline-block h-5 w-5 rounded-full border" style="background:' . App::e($s['theme']['color_secondary'] ?? '') . '"></span>';
    $html .= '<span>' . App::e(t('font')) . ': ' . App::e($s['theme']['font'] ?? '') . '</span>';
    $html .= '</div>';
    if (!empty($s['rationale'])) $html .= '<p class="italic opacity-80">' . App::e($s['rationale']) . '</p>';
    $html .= '<button type="button" class="btn" onclick="applySuggestion(' . $idWedding . ')">' . App::e(t('ai_apply')) . '</button>';
    return $html;
}

$stmt = $app->db->prepare('SELECT id_photo, filename, original_name FROM wedding_photos WHERE id_wedding = ? ORDER BY sort_order ASC, id_photo ASC');
$stmt->bind_param('i', $idWedding);
$stmt->execute();
$photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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

<div class="max-w-2xl rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4 mb-6">
    <label class="block text-sm font-medium"><?= App::e(t('photos')) ?></label>
    <div id="photo-grid" class="grid grid-cols-4 gap-3">
        <?php foreach ($photos as $p): ?>
        <div class="relative group" data-photo-id="<?= (int)$p['id_photo'] ?>">
            <img src="<?= App::e(Photo::url($idWedding, $p['filename'])) ?>" class="h-24 w-full object-cover rounded-md border border-gray-200 dark:border-gray-800">
            <button type="button" class="absolute top-1 right-1 rounded-full bg-black/60 text-white text-xs w-5 h-5 leading-5 text-center"
                    onclick="deletePhoto(<?= (int)$p['id_photo'] ?>)">✕</button>
        </div>
        <?php endforeach; ?>
    </div>
    <div>
        <input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" multiple class="text-sm">
        <p class="text-xs opacity-60 mt-1"><?= App::e(t('photos_hint')) ?></p>
    </div>
</div>

<div class="max-w-2xl rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4 mb-6">
    <label class="block text-sm font-medium"><?= App::e(t('ai_design_title')) ?></label>
    <p class="text-sm opacity-70"><?= App::e(t('ai_design_hint')) ?></p>

    <?php if (!AiDesigner::isConfigured()): ?>
        <p class="text-sm rounded-md bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 px-3 py-2"><?= App::e(t('ai_not_configured')) ?></p>
    <?php else: ?>
        <textarea id="ai-style-notes" rows="2" placeholder="<?= App::e(t('ai_style_placeholder')) ?>" class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-transparent px-3 py-2 text-sm"></textarea>
        <button type="button" id="ai-generate-btn" class="btn" onclick="generateSuggestion(<?= (int)$idWedding ?>)"><?= App::e(t('ai_generate')) ?></button>

        <div id="ai-suggestion-box" class="<?= $suggestion ? '' : 'hidden' ?> rounded-md border border-indigo-200 dark:border-indigo-900 bg-indigo-50 dark:bg-indigo-950/40 p-4 space-y-2 text-sm">
            <div id="ai-suggestion-content"><?= $suggestion ? render_ai_suggestion($suggestion, $availableBlocks, $idWedding) : '' ?></div>
        </div>
    <?php endif; ?>
</div>

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

<script>
const AI_BLOCK_LABELS = <?= json_encode($availableBlocks, JSON_UNESCAPED_UNICODE) ?>;
const AI_TEXT = {
    blocksLabel: <?= json_encode(t('ai_blocks_label')) ?>,
    font: <?= json_encode(t('font')) ?>,
    apply: <?= json_encode(t('ai_apply')) ?>,
    generate: <?= json_encode(t('ai_generate')) ?>,
    saved: <?= json_encode(t('save')) ?>,
};
const AI_ID_WEDDING = <?= (int)$idWedding ?>;
const ERROR_MESSAGES = {
    upload_error: <?= json_encode(t('err_upload_error')) ?>,
    too_large: <?= json_encode(t('err_too_large')) ?>,
    invalid_type: <?= json_encode(t('err_invalid_type')) ?>,
    limit_reached: <?= json_encode(t('err_limit_reached')) ?>,
    storage_error: <?= json_encode(t('err_storage_error')) ?>,
    ai_not_configured: <?= json_encode(t('err_ai_not_configured')) ?>,
    ai_failed: <?= json_encode(t('err_ai_failed')) ?>,
    no_suggestion: <?= json_encode(t('err_no_suggestion')) ?>,
    not_found: <?= json_encode(t('err_not_found')) ?>,
};
function errMsg(code) { return ERROR_MESSAGES[code] || code || 'Error'; }

async function uploadPhoto(idWedding, file) {
    const fd = new FormData();
    fd.append('photo', file);
    fd.append('id_wedding', idWedding);
    const res = await fetch('/ajax/design/upload_photo.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.ok) { notify(errMsg(data.error), 'err'); return; }
    const grid = document.getElementById('photo-grid');
    const div = document.createElement('div');
    div.className = 'relative group';
    div.dataset.photoId = data.photo.id;
    div.innerHTML = '<img src="' + data.photo.url + '" class="h-24 w-full object-cover rounded-md border border-gray-200 dark:border-gray-800">'
        + '<button type="button" class="absolute top-1 right-1 rounded-full bg-black/60 text-white text-xs w-5 h-5 leading-5 text-center" onclick="deletePhoto(' + data.photo.id + ')">✕</button>';
    grid.appendChild(div);
}

async function deletePhoto(id) {
    const res = await api('design/delete_photo', { id });
    if (res.ok) {
        document.querySelector('[data-photo-id="' + id + '"]')?.remove();
    } else {
        notify(errMsg(res.error), 'err');
    }
}

function renderSuggestion(s) {
    const blockLabels = (s.blocks || []).map((k) => AI_BLOCK_LABELS[k] || k).join(', ');
    let html = '<p class="font-medium">' + escapeHtml(AI_TEXT.blocksLabel) + ' ' + escapeHtml(blockLabels) + '</p>';
    html += '<div class="flex items-center gap-2">'
        + '<span class="inline-block h-5 w-5 rounded-full border" style="background:' + escapeHtml(s.theme.color_primary) + '"></span>'
        + '<span class="inline-block h-5 w-5 rounded-full border" style="background:' + escapeHtml(s.theme.color_secondary) + '"></span>'
        + '<span>' + escapeHtml(AI_TEXT.font) + ': ' + escapeHtml(s.theme.font) + '</span></div>';
    if (s.rationale) html += '<p class="italic opacity-80">' + escapeHtml(s.rationale) + '</p>';
    html += '<button type="button" class="btn" onclick="applySuggestion(' + AI_ID_WEDDING + ')">' + escapeHtml(AI_TEXT.apply) + '</button>';
    return html;
}

async function generateSuggestion(idWedding) {
    const btn = document.getElementById('ai-generate-btn');
    const notes = document.getElementById('ai-style-notes').value;
    btn.disabled = true;
    btn.textContent = '...';
    const res = await api('design/suggest', { id_wedding: idWedding, style_notes: notes });
    btn.disabled = false;
    btn.textContent = AI_TEXT.generate;
    if (!res.ok) { notify(errMsg(res.error), 'err'); return; }
    document.getElementById('ai-suggestion-content').innerHTML = renderSuggestion(res.suggestion);
    document.getElementById('ai-suggestion-box').classList.remove('hidden');
}

async function applySuggestion(idWedding) {
    const res = await api('design/apply_suggestion', { id_wedding: idWedding });
    if (res.ok) {
        flashReload(AI_TEXT.saved);
    } else {
        notify(errMsg(res.error), 'err');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('photo-input');
    if (input) {
        input.addEventListener('change', () => {
            Array.from(input.files).forEach((file) => uploadPhoto(AI_ID_WEDDING, file));
            input.value = '';
        });
    }
});
</script>
