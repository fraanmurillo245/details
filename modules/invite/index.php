<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$slug = $_section; // en /invite/<slug>/, $_section transporta el slug de la boda

$stmt = $app->db->prepare(
    "SELECT w.*, p.blocks_json, p.theme_json FROM weddings w
     LEFT JOIN wedding_pages p ON p.id_wedding = w.id_wedding
     WHERE w.slug = ? AND w.status = 'published' LIMIT 1"
);
$stmt->bind_param('s', $slug);
$stmt->execute();
$wedding = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$wedding) {
    http_response_code(404);
    echo '<!doctype html><html><body><p>Invitación no encontrada.</p></body></html>';
    return;
}

$idWedding = (int)$wedding['id_wedding'];
$blocks = json_decode($wedding['blocks_json'] ?? '[]', true) ?: [];
$theme = json_decode($wedding['theme_json'] ?? '{}', true) ?: [];
$colorPrimary = $theme['color_primary'] ?? '#4f46e5';
$colorSecondary = $theme['color_secondary'] ?? '#f5f5f4';
$fontClass = ['serif' => 'font-serif', 'sans' => 'font-sans', 'script' => 'font-serif italic'][$theme['font'] ?? 'serif'] ?? 'font-serif';

$allergensCatalog = $app->db->query('SELECT id_allergen, name FROM allergens ORDER BY name ASC')->fetch_all(MYSQLI_ASSOC);
$rsvpSent = false;
$rsvpError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rsvp_name'])) {
    $name = trim((string)$_POST['rsvp_name']);
    $email = trim((string)($_POST['rsvp_email'] ?? ''));
    $attending = ($_POST['rsvp_attending'] ?? '') === 'yes';
    $companions = max(0, (int)($_POST['rsvp_companions'] ?? 0));
    $message = trim((string)($_POST['rsvp_message'] ?? ''));
    $postedAllergens = array_map('intval', $_POST['rsvp_allergens'] ?? []);

    if ($name === '') {
        $rsvpError = t('rsvp_name_required');
    } else {
        $status = $attending ? 'confirmed' : 'declined';
        $stmt = $app->db->prepare(
            'INSERT INTO guests (id_wedding, name, email, rsvp_status, rsvp_companions, rsvp_message, rsvp_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())'
        );
        $stmt->bind_param('isssis', $idWedding, $name, $email, $status, $companions, $message);
        $stmt->execute();
        $idGuest = (int)$stmt->insert_id;
        $stmt->close();

        if ($postedAllergens) {
            $stmt = $app->db->prepare('INSERT INTO guest_allergens (id_guest, id_allergen, created_at) VALUES (?, ?, NOW())');
            foreach ($postedAllergens as $idAllergen) {
                $stmt->bind_param('ii', $idGuest, $idAllergen);
                $stmt->execute();
            }
            $stmt->close();
        }
        $rsvpSent = true;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= App::e($wedding['partner1_name'] . ' & ' . $wedding['partner2_name']) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
    :root { --color-primary: <?= App::e($colorPrimary) ?>; --color-secondary: <?= App::e($colorSecondary) ?>; }
</style>
</head>
<body class="<?= App::e($fontClass) ?>" style="background: var(--color-secondary);">
<main class="mx-auto max-w-lg px-6 py-12 space-y-16">

<?php if (in_array('cover', $blocks, true)): ?>
<section class="text-center space-y-3">
    <h1 class="text-4xl" style="color: var(--color-primary);"><?= App::e($wedding['partner1_name']) ?> &amp; <?= App::e($wedding['partner2_name']) ?></h1>
    <?php if ($wedding['event_date']): ?>
        <p class="text-lg"><?= App::e(date('d/m/Y', strtotime($wedding['event_date']))) ?></p>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if (in_array('countdown', $blocks, true) && $wedding['event_date']): ?>
<section class="text-center" data-countdown="<?= App::e($wedding['event_date']) ?>">
    <p class="text-sm uppercase tracking-widest opacity-70"><?= App::e(t('countdown_label')) ?></p>
    <div id="countdown-box" class="text-2xl mt-2"></div>
</section>
<?php endif; ?>

<?php if (in_array('location', $blocks, true) && $wedding['venue_name']): ?>
<section class="text-center space-y-1">
    <p class="text-sm uppercase tracking-widest opacity-70"><?= App::e(t('block_location')) ?></p>
    <p class="text-lg"><?= App::e($wedding['venue_name']) ?></p>
    <p class="opacity-80"><?= App::e($wedding['venue_address']) ?></p>
</section>
<?php endif; ?>

<?php if (in_array('rsvp', $blocks, true)): ?>
<section class="space-y-4">
    <h2 class="text-2xl text-center" style="color: var(--color-primary);"><?= App::e(t('rsvp_title')) ?></h2>

    <?php if ($rsvpSent): ?>
        <p class="text-center rounded-md bg-green-50 text-green-700 px-4 py-3"><?= App::e(t('rsvp_thanks')) ?></p>
    <?php else: ?>
    <?php if ($rsvpError): ?>
        <p class="text-center rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm"><?= App::e($rsvpError) ?></p>
    <?php endif; ?>
    <form method="post" class="space-y-4 rounded-lg bg-white/60 p-6">
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('name')) ?></label>
            <input name="rsvp_name" required class="w-full rounded-md border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('email')) ?></label>
            <input type="email" name="rsvp_email" class="w-full rounded-md border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('rsvp_will_attend')) ?></label>
            <select name="rsvp_attending" class="w-full rounded-md border border-gray-300 px-3 py-2">
                <option value="yes"><?= App::e(t('yes')) ?></option>
                <option value="no"><?= App::e(t('no')) ?></option>
            </select>
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('rsvp_companions')) ?></label>
            <input type="number" min="0" max="10" name="rsvp_companions" value="0" class="w-full rounded-md border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm mb-2"><?= App::e(t('allergens')) ?></label>
            <div class="grid grid-cols-2 gap-2">
            <?php foreach ($allergensCatalog as $a): ?>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="rsvp_allergens[]" value="<?= (int)$a['id_allergen'] ?>">
                    <?= App::e($a['name']) ?>
                </label>
            <?php endforeach; ?>
            </div>
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('rsvp_message')) ?></label>
            <textarea name="rsvp_message" rows="3" class="w-full rounded-md border border-gray-300 px-3 py-2"></textarea>
        </div>
        <button type="submit" class="btn w-full justify-center"><?= App::e(t('rsvp_submit')) ?></button>
    </form>
    <?php endif; ?>
</section>
<?php endif; ?>

</main>
<script src="/assets/js/app.js"></script>
</body>
</html>
