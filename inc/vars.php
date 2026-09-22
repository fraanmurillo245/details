<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

function _clean_var($v, $default = '') {
    if (!isset($v)) return $default;
    $v = (string)$v;
    // 1er carácter alfanumérico evita "../"; luego se permiten . _ -
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $v)) return $default;
    return $v;
}

$_path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$_segs = $_path === '' ? [] : explode('/', $_path);
$_module  = _clean_var($_segs[0] ?? null, 'dashboard');
$_section = _clean_var($_segs[1] ?? null, 'index');
$_id      = _clean_var($_segs[2] ?? null, '');
$_slug    = _clean_var($_segs[3] ?? null, '');
$_sub     = _clean_var($_segs[4] ?? null, '');
$_sub2    = _clean_var($_segs[5] ?? null, '');

// Helper: SIEMPRE construir enlaces con u() -> URL absoluta acabada en "/".
// Nota: 'index' solo se omite de la URL cuando no hay id (para que /modulo/
// quede bonito); si hay id, se mantiene explícito para que no "se cuele" en
// la posición de $_section al parsear la ruta (p.ej. u('guests','index',42)
// -> /guests/index/42/, nunca /guests/42/).
function u($module = 'dashboard', $section = '', $id = '') {
    $base = rtrim($GLOBALS['_config']['url'] ?? '', '/');
    if ($module === 'dashboard' && $section === '' && (string)$id === '') return $base . '/';
    $segs = [$module];
    $section = $section === '' ? 'index' : $section;
    if ($section !== 'index' || (string)$id !== '') $segs[] = $section;
    if ((string)$id !== '') $segs[] = $id;
    return $base . '/' . implode('/', $segs) . '/';
}
