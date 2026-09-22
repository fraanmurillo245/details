<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$idWedding = (int)$_id;

$stmt = $app->db->prepare('SELECT * FROM weddings WHERE id_wedding = ? AND id_account = ? LIMIT 1');
$stmt->bind_param('ii', $idWedding, $idAccount);
$stmt->execute();
$wedding = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$wedding) { $app->redirect(u('weddings')); }

$contracted = WeddingLanguages::contracted($app, $idWedding);
$available = WeddingLanguages::available($app, $idWedding);
$priceLabel = App::money(Settings::extraLanguageCents($app), Settings::extraLanguageCurrency($app));

$langNames = [
    'es' => t('lang_es'), 'en' => t('lang_en'), 'fr' => t('lang_fr'), 'it' => t('lang_it'),
];
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="heading text-2xl"><?= App::e(t('languages_title')) ?></h1>
    <a href="<?= App::e(u('weddings')) ?>" class="btn"><?= App::e(t('admin_back_to_dashboard')) ?></a>
</div>

<div class="max-w-xl space-y-6">
    <p class="text-sm text-gray-500"><?= App::e(t('languages_hint', $priceLabel)) ?></p>

    <div class="card divide-y divide-gray-100 dark:divide-gray-800">
        <?php foreach ($contracted as $row): ?>
        <div class="flex items-center justify-between px-4 py-3">
            <span><?= App::e($langNames[$row['language']] ?? strtoupper($row['language'])) ?></span>
            <span class="rounded-full px-2 py-0.5 text-xs bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300">
                <?= App::e($row['is_included'] ? t('languages_included') : t('languages_purchased')) ?>
            </span>
        </div>
        <?php endforeach; ?>
        <?php foreach ($available as $code): ?>
        <div class="flex items-center justify-between px-4 py-3">
            <span><?= App::e($langNames[$code] ?? strtoupper($code)) ?></span>
            <a href="<?= App::e(u('weddings', 'buy_language', $idWedding)) ?>?lang=<?= App::e($code) ?>" class="btn-brand">
                <?= App::e(t('languages_buy', $priceLabel)) ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
