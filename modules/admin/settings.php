<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

if (empty($_user['is_admin'])) { $app->redirect(u('dashboard')); }

$saved = false;
$currentFeeCents = Settings::flatFeeCents($app);
$currentFeeCurrency = Settings::flatFeeCurrency($app);
$currentLangFeeCents = Settings::extraLanguageCents($app);
$currentLangFeeCurrency = Settings::extraLanguageCurrency($app);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $euros = str_replace(',', '.', trim((string)($_POST['flat_fee'] ?? '')));
    $cents = (int)round((float)$euros * 100);
    $currency = in_array($_POST['currency'] ?? '', ['eur', 'usd', 'gbp'], true) ? $_POST['currency'] : 'eur';

    $langEuros = str_replace(',', '.', trim((string)($_POST['extra_language_fee'] ?? '')));
    $langCents = (int)round((float)$langEuros * 100);
    $langCurrency = in_array($_POST['extra_language_currency'] ?? '', ['eur', 'usd', 'gbp'], true) ? $_POST['extra_language_currency'] : 'eur';

    if ($cents > 0 && $langCents > 0) {
        Settings::set($app, 'flat_fee_cents', (string)$cents);
        Settings::set($app, 'flat_fee_currency', $currency);
        Settings::set($app, 'extra_language_cents', (string)$langCents);
        Settings::set($app, 'extra_language_currency', $langCurrency);
        $app->log('admin.settings_update', 0, (int)$_user['id_account']);
        $currentFeeCents = $cents;
        $currentFeeCurrency = $currency;
        $currentLangFeeCents = $langCents;
        $currentLangFeeCurrency = $langCurrency;
        $saved = true;
    }
}
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="heading text-2xl"><?= App::e(t('admin_settings_title')) ?></h1>
    <a href="<?= App::e(u('admin')) ?>" class="btn"><?= App::e(t('admin_back_to_dashboard')) ?></a>
</div>

<div class="max-w-md card p-6 space-y-4">
    <?php if ($saved): ?>
        <div class="rounded-md bg-green-50 text-green-700 px-3 py-2 text-sm"><?= App::e(t('admin_price_saved')) ?></div>
    <?php endif; ?>
    <p class="text-sm text-gray-500"><?= App::e(t('admin_price_hint')) ?></p>
    <form method="post" class="space-y-4">
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('admin_flat_fee')) ?></label>
            <input type="text" name="flat_fee" required value="<?= App::e(number_format($currentFeeCents / 100, 2, ',', '.')) ?>" class="field">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('admin_currency')) ?></label>
            <select name="currency" class="field">
                <option value="eur" <?= $currentFeeCurrency === 'eur' ? 'selected' : '' ?>>EUR (€)</option>
                <option value="usd" <?= $currentFeeCurrency === 'usd' ? 'selected' : '' ?>>USD ($)</option>
                <option value="gbp" <?= $currentFeeCurrency === 'gbp' ? 'selected' : '' ?>>GBP (£)</option>
            </select>
        </div>
        <hr class="border-gray-100 dark:border-gray-800">
        <p class="text-sm text-gray-500"><?= App::e(t('admin_extra_language_hint')) ?></p>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('admin_extra_language_fee')) ?></label>
            <input type="text" name="extra_language_fee" required value="<?= App::e(number_format($currentLangFeeCents / 100, 2, ',', '.')) ?>" class="field">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('admin_currency')) ?></label>
            <select name="extra_language_currency" class="field">
                <option value="eur" <?= $currentLangFeeCurrency === 'eur' ? 'selected' : '' ?>>EUR (€)</option>
                <option value="usd" <?= $currentLangFeeCurrency === 'usd' ? 'selected' : '' ?>>USD ($)</option>
                <option value="gbp" <?= $currentLangFeeCurrency === 'gbp' ? 'selected' : '' ?>>GBP (£)</option>
            </select>
        </div>
        <button type="submit" class="btn-brand"><?= App::e(t('save')) ?></button>
    </form>
</div>
