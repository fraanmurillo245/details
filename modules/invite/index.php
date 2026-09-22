<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$slug = $_section; // en /invite/<slug>/, $_section transporta el slug de la boda

$stmt = $app->db->prepare(
    "SELECT w.*, p.blocks_json, p.theme_json, p.gift_message FROM weddings w
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
$colorPrimary = $theme['color_primary'] ?? '#b76e79';
$colorSecondary = $theme['color_secondary'] ?? '#faf6f2';

$tpl = InviteTemplate::get($wedding['template'] ?? 'classic');
$fonts = ['name' => $tpl['font_name'], 'heading' => $tpl['font_heading'], 'body' => $tpl['font_body']];
$heroLeft = $tpl['hero_align'] === 'left';

function render_divider(array $tpl, bool $left = false): string
{
    $margin = $left ? '1.25rem 0' : '1.25rem auto';
    if ($tpl['divider'] === 'square') {
        return '<div style="width:9px;height:9px;margin:' . $margin . ';background:var(--color-primary);transform:rotate(45deg);"></div>';
    }
    if ($tpl['divider'] === 'leaf') {
        return '<svg width="26" height="26" viewBox="0 0 24 24" style="margin:' . $margin . ';display:block;fill:var(--color-primary);opacity:.65"><path d="M12 2c-4.5 4-8 8.5-8 13a8 8 0 0016 0c0-4.5-3.5-9-8-13z"/></svg>';
    }
    return '<div class="divider" style="margin:' . $margin . '"></div>';
}

$stmt = $app->db->prepare('SELECT filename, original_name FROM wedding_photos WHERE id_wedding = ? ORDER BY sort_order ASC, id_photo ASC');
$stmt->bind_param('i', $idWedding);
$stmt->execute();
$photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$coverPhoto = $photos ? Photo::url($idWedding, $photos[0]['filename']) : null;

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

        Notifications::rsvpGuestConfirmation($wedding, $email, $name, $attending);
        $stmt = $app->db->prepare('SELECT email FROM accounts WHERE id_account = ? LIMIT 1');
        $stmt->bind_param('i', $wedding['id_account']);
        $stmt->execute();
        $ownerEmail = (string)($stmt->get_result()->fetch_assoc()['email'] ?? '');
        $stmt->close();
        Notifications::rsvpOwnerAlert($wedding, $ownerEmail, $wedding['partner1_name'], $name, $attending, $companions);

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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400&family=Cormorant+Garamond:wght@400;500;600&family=Great+Vibes&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --color-primary: <?= App::e($colorPrimary) ?>;
        --color-secondary: <?= App::e($colorSecondary) ?>;
        --font-name: <?= $fonts['name'] ?>;
        --font-heading: <?= $fonts['heading'] ?>;
        --font-body: <?= $fonts['body'] ?>;
    }
    body { background: var(--color-secondary); font-family: var(--font-body); color: #2a2422; }
    .heading { font-family: var(--font-heading); }
    .couple-name { font-family: var(--font-name); }
    .eyebrow { font-family: var(--font-body); font-size: 0.75rem; color: var(--color-primary); <?= $tpl['eyebrow_css'] ?> }
    .divider { width: 48px; height: 2px; margin: 1.25rem auto; background: var(--color-primary); opacity: 0.55; }
    .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; }
    .hero-photo { background-size: cover; background-position: center; }
    .hero-overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,.25), rgba(0,0,0,.15) 40%, var(--color-secondary) 96%); }
    .hero-plain { background: linear-gradient(160deg, color-mix(in srgb, var(--color-primary) 18%, var(--color-secondary)), var(--color-secondary) 70%); }
    .section { max-width: 640px; margin: 0 auto; padding: 4rem 1.5rem; text-align: center; }
    .rsvp-card { background: rgba(255,255,255,0.7); border: 1px solid color-mix(in srgb, var(--color-primary) 25%, transparent); }
    input[type=text], input[type=email], input[type=number], select, textarea {
        font-family: var(--font-body); border-color: color-mix(in srgb, var(--color-primary) 30%, transparent);
    }
    .btn-primary {
        background: var(--color-primary); color: white; border: none; transition: opacity .2s;
    }
    .btn-primary:hover { opacity: 0.9; }
    .gallery-item { transition: transform .3s ease; }
    .gallery-item:hover { transform: scale(1.03); }
    .scroll-cue { animation: bounce 2s infinite; }
    @keyframes bounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(8px); } }
</style>
</head>
<body>

<?php if (in_array('cover', $blocks, true)): ?>
<section class="hero <?= $coverPhoto ? 'hero-photo' : 'hero-plain' ?>"
          style="<?= $heroLeft ? 'justify-content:flex-start;' : '' ?><?= $coverPhoto ? "background-image:url('" . App::e($coverPhoto) . "')" : '' ?>">
    <?php if ($coverPhoto): ?><div class="hero-overlay"></div><?php endif; ?>
    <div class="relative z-10 <?= $heroLeft ? 'text-left px-10 sm:px-20 max-w-xl' : 'text-center px-6' ?> <?= $coverPhoto ? 'text-white' : '' ?>">
        <p class="eyebrow mb-4" style="<?= $coverPhoto ? 'color:white;opacity:.85' : '' ?>"><?= App::e(t('invite_tagline')) ?></p>
        <h1 class="couple-name <?= $heroLeft ? 'text-5xl sm:text-6xl' : 'text-6xl sm:text-7xl' ?> leading-tight"><?= App::e($wedding['partner1_name']) ?> <span style="color: var(--color-primary)">&amp;</span> <?= App::e($wedding['partner2_name']) ?></h1>
        <?= render_divider($tpl, $heroLeft) ?>
        <?php if ($wedding['event_date']): ?>
            <p class="heading text-xl tracking-wide"><?= App::e(date('d.m.Y', strtotime($wedding['event_date']))) ?></p>
        <?php endif; ?>
        <p class="mt-2 text-sm <?= $coverPhoto ? 'text-white/80' : 'opacity-70' ?>"><?= App::e(t('invite_subtitle')) ?></p>
        <div class="scroll-cue mt-12 text-2xl <?= $coverPhoto ? 'text-white/70' : 'opacity-50' ?>">&#8595;</div>
    </div>
</section>
<?php endif; ?>

<?php if (in_array('countdown', $blocks, true) && $wedding['event_date']): ?>
<section class="section" data-countdown="<?= App::e($wedding['event_date']) ?>">
    <p class="eyebrow"><?= App::e(t('countdown_label')) ?></p>
    <?= render_divider($tpl) ?>
    <div class="flex items-center justify-center gap-4 sm:gap-8">
        <div class="rounded-xl border px-6 py-4" style="border-color: color-mix(in srgb, var(--color-primary) 25%, transparent)">
            <div class="heading text-4xl" style="color: var(--color-primary)" id="cd-days">-</div>
            <div class="text-xs uppercase tracking-widest opacity-60 mt-1">Días</div>
        </div>
        <div class="rounded-xl border px-6 py-4" style="border-color: color-mix(in srgb, var(--color-primary) 25%, transparent)">
            <div class="heading text-4xl" style="color: var(--color-primary)" id="cd-hours">-</div>
            <div class="text-xs uppercase tracking-widest opacity-60 mt-1">Horas</div>
        </div>
        <div class="rounded-xl border px-6 py-4" style="border-color: color-mix(in srgb, var(--color-primary) 25%, transparent)">
            <div class="heading text-4xl" style="color: var(--color-primary)" id="cd-minutes">-</div>
            <div class="text-xs uppercase tracking-widest opacity-60 mt-1">Minutos</div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (in_array('location', $blocks, true) && $wedding['venue_name']): ?>
<section class="section">
    <p class="eyebrow"><?= App::e(t('block_location')) ?></p>
    <?= render_divider($tpl) ?>
    <h2 class="heading text-3xl mb-2"><?= App::e($wedding['venue_name']) ?></h2>
    <p class="opacity-75 mb-6"><?= App::e($wedding['venue_address']) ?></p>
    <?php if ($wedding['venue_address']): ?>
    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($wedding['venue_address']) ?>" target="_blank" rel="noopener"
       class="inline-block rounded-full border px-6 py-2 text-sm heading" style="border-color: var(--color-primary); color: var(--color-primary)">
        <?= App::e(t('directions')) ?>
    </a>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if (in_array('gallery', $blocks, true) && $photos): ?>
<section class="section" style="max-width: 880px">
    <p class="eyebrow"><?= App::e(t('block_gallery')) ?></p>
    <?= render_divider($tpl) ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <?php foreach ($photos as $p): ?>
            <a href="<?= App::e(Photo::url($idWedding, $p['filename'])) ?>" target="_blank" rel="noopener" class="gallery-item block overflow-hidden rounded-lg">
                <img src="<?= App::e(Photo::url($idWedding, $p['filename'])) ?>" class="h-40 w-full object-cover" loading="lazy">
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (in_array('gift', $blocks, true) && !empty($wedding['gift_message'])): ?>
<section class="section">
    <p class="eyebrow"><?= App::e(t('block_gift')) ?></p>
    <?= render_divider($tpl) ?>
    <p class="text-lg leading-relaxed opacity-90"><?= nl2br(App::e($wedding['gift_message'])) ?></p>
</section>
<?php endif; ?>

<?php if (in_array('rsvp', $blocks, true)): ?>
<section class="section">
    <p class="eyebrow"><?= App::e(t('rsvp_title')) ?></p>
    <?= render_divider($tpl) ?>

    <?php if ($rsvpSent): ?>
        <p class="rounded-md bg-green-50 text-green-700 px-4 py-3"><?= App::e(t('rsvp_thanks')) ?></p>
    <?php else: ?>
    <?php if ($rsvpError): ?>
        <p class="rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm mb-4"><?= App::e($rsvpError) ?></p>
    <?php endif; ?>
    <form method="post" class="rsvp-card rounded-xl p-6 sm:p-8 space-y-4 text-left">
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('name')) ?></label>
            <input type="text" name="rsvp_name" required class="w-full rounded-md border px-3 py-2 bg-white/70">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('email')) ?></label>
            <input type="email" name="rsvp_email" class="w-full rounded-md border px-3 py-2 bg-white/70">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('rsvp_will_attend')) ?></label>
            <select name="rsvp_attending" class="w-full rounded-md border px-3 py-2 bg-white/70">
                <option value="yes"><?= App::e(t('yes')) ?></option>
                <option value="no"><?= App::e(t('no')) ?></option>
            </select>
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('rsvp_companions')) ?></label>
            <input type="number" min="0" max="10" name="rsvp_companions" value="0" class="w-full rounded-md border px-3 py-2 bg-white/70">
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
            <textarea name="rsvp_message" rows="3" class="w-full rounded-md border px-3 py-2 bg-white/70"></textarea>
        </div>
        <button type="submit" class="btn-primary w-full justify-center rounded-md py-3 heading text-sm tracking-wide uppercase"><?= App::e(t('rsvp_submit')) ?></button>
    </form>
    <?php endif; ?>
</section>
<?php endif; ?>

<footer class="text-center pb-10 text-xs opacity-50 heading">
    <?= App::e($wedding['partner1_name'] . ' & ' . $wedding['partner2_name']) ?>
</footer>

<script src="/assets/js/app.js"></script>
</body>
</html>
