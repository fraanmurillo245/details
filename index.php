<?php
include_once __DIR__ . '/config.php';

// 1) ?module=/?section= NO sirve páginas -> redirige a la amigable.
if (isset($_GET['module']) || isset($_GET['section'])) {
    $app->redirect(u(_clean_var($_GET['module'] ?? '', 'dashboard'),
                     _clean_var($_GET['section'] ?? ''),
                     _clean_var($_GET['id'] ?? '')));
}
// 2) Barra final obligatoria.
$p = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($p !== '' && substr($p, -1) !== '/') {
    $qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    $app->redirect(rtrim($_config['url'], '/') . $p . '/' . $qs);
}

// La invitación pública (/invite/<slug>/) tiene su propio layout, sin el panel.
// Aquí $_section es el slug de la boda, no una acción de módulo.
if ($_module === 'invite') {
    include __DIR__ . '/modules/invite/index.php';
    exit;
}

// Módulos standalone (sin el panel) que sí siguen el enrutado normal
// módulo/sección: alta de cuentas y recuperación de contraseña.
if (in_array($_module, ['signup', 'forgot'], true)) {
    $standalone_path = __DIR__ . '/modules/' . $_module . '/' . $_section . '.php';
    include is_file($standalone_path) ? $standalone_path : __DIR__ . '/modules/' . $_module . '/index.php';
    exit;
}

$section_path = __DIR__ . '/modules/' . $_module . '/' . $_section . '.php';

$nav = [
    'dashboard' => ['label' => t('nav_dashboard'), 'icon' => 'home'],
    'weddings'  => ['label' => t('nav_weddings'), 'icon' => 'heart'],
    'guests'    => ['label' => t('nav_guests'), 'icon' => 'users'],
    'tables'    => ['label' => t('nav_tables'), 'icon' => 'grid'],
    'design'    => ['label' => t('nav_design'), 'icon' => 'palette'],
];
if (!empty($_user['is_admin'])) {
    $nav['admin'] = ['label' => t('nav_admin'), 'icon' => 'shield'];
}
?>
<!doctype html>
<html lang="<?= App::e($_lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= App::e(t('app_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<script>
try {
    if (document.cookie.includes('theme=dark')) document.documentElement.classList.add('dark');
} catch (e) {}
</script>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-[#fbfbfd] text-gray-900 dark:bg-gray-950 dark:text-gray-100">
<div class="flex min-h-screen">
    <aside class="w-60 shrink-0 border-r border-gray-100 dark:border-gray-800 bg-white/70 dark:bg-gray-900/40 p-5">
        <a href="<?= App::e(u('dashboard')) ?>" class="block mb-8">
            <img src="/assets/img/logo.png" alt="<?= App::e(t('app_name')) ?>" class="h-8 w-auto">
        </a>
        <nav class="space-y-1">
            <?php foreach ($nav as $mod => $item): ?>
            <a href="<?= App::e(u($mod)) ?>" class="nav-link <?= $_module === $mod ? 'active' : '' ?>">
                <?= App::e($item['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="flex-1">
        <header class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-8 py-4">
            <div></div>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-gray-500"><?= App::e($_user['email'] ?? '') ?></span>
                <a href="/logout.php" class="btn"><?= App::e(t('logout')) ?></a>
            </div>
        </header>
        <main class="p-8">
            <?php is_file($section_path) ? include $section_path : print '<p>404</p>'; ?>
        </main>
    </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
