<?php
/**
 * Instalador web de un solo uso: aplica el esquema de base de datos y
 * genera un secret aleatorio, sin necesitar acceso SSH/terminal.
 *
 * Uso:
 *   1. Rellena inc/db.php con tus credenciales reales de MySQL.
 *   2. Visita este fichero desde el navegador: https://tu-dominio/install.php
 *   3. Copia el secret generado a config.php.
 *   4. BORRA este fichero del servidor.
 */
define('IN_APP', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalación — details</title>
<style>
    body { font-family: -apple-system, Segoe UI, Arial, sans-serif; background: #f1f2fb; margin: 0; padding: 40px 16px; color: #2a2422; }
    .box { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    h1 { font-size: 22px; margin-top: 0; }
    .ok { color: #16a34a; }
    .err { color: #dc2626; }
    .step { padding: 6px 0; border-bottom: 1px solid #eee; font-size: 14px; }
    code, .secret { background: #f1f2fb; padding: 10px 14px; border-radius: 8px; display: block; word-break: break-all; font-size: 13px; margin: 8px 0 16px; }
    .warn { background: #fef3c7; color: #92400e; padding: 14px 16px; border-radius: 8px; font-size: 14px; margin-top: 24px; }
</style>
</head>
<body>
<div class="box">
    <h1>Instalación de <strong>details</strong></h1>
<?php
// Se imprime el shell de la página ANTES de conectar: si inc/db.php falla
// (hace die() con el error de mysqli), el mensaje aparece igualmente
// dentro de esta caja con estilo, en vez de una pantalla en blanco.
flush();

$dbFile = __DIR__ . '/inc/db.php';
if (!is_file($dbFile)) {
    echo '<p class="err"><strong>No encuentro inc/db.php.</strong> Sube primero todos los ficheros del proyecto.</p></div></body></html>';
    exit;
}
include $dbFile; // si las credenciales son incorrectas, termina aquí con die()
?>

    <p>Aplicando el esquema de base de datos:</p>
    <?php
    $allOk = true;
    $files = glob(__DIR__ . '/inc/schemas/*.sql');
    sort($files);
    foreach ($files as $file) {
        $name = basename($file);
        $sql = file_get_contents($file);
        $ok = $mysqli->multi_query($sql);
        if ($ok) {
            do {
                if ($res = $mysqli->store_result()) $res->free();
            } while ($mysqli->more_results() && $mysqli->next_result());
        }
        if ($mysqli->errno) {
            echo '<div class="step"><span class="err">✗</span> ' . htmlspecialchars($name) . '<br><span class="err">' . htmlspecialchars($mysqli->error) . '</span></div>';
            $allOk = false;
            break;
        }
        echo '<div class="step"><span class="ok">✓</span> ' . htmlspecialchars($name) . '</div>';
    }

    if ($allOk) {
        $allergens = [
            'Gluten', 'Crustáceos', 'Huevos', 'Pescado', 'Cacahuetes', 'Soja', 'Lácteos',
            'Frutos de cáscara', 'Apio', 'Mostaza', 'Sésamo', 'Sulfitos', 'Altramuces', 'Moluscos',
        ];
        $stmt = $mysqli->prepare('INSERT IGNORE INTO allergens (name, created_at) VALUES (?, NOW())');
        foreach ($allergens as $name) {
            $stmt->bind_param('s', $name);
            $stmt->execute();
        }
        $stmt->close();

        $secret = bin2hex(random_bytes(32));
        echo '<p class="ok" style="margin-top:16px;"><strong>✓ Base de datos lista.</strong></p>';
        echo '<p style="margin-top:24px;">Copia este código y pégalo en <code>config.php</code>, sustituyendo la línea <code>\'secret\' =&gt; \'...\'</code>:</p>';
        echo '<div class="secret">\'secret\' => \'' . $secret . '\',</div>';
        echo '<div class="warn">⚠️ <strong>Por seguridad, borra este fichero (install.php) del servidor ahora mismo.</strong> Cualquiera que conozca esta URL podría volver a ejecutarlo.</div>';
    } else {
        echo '<p class="err">Hubo un error aplicando el esquema. Revisa el mensaje de arriba.</p>';
    }
    ?>
</div>
</body>
</html>
