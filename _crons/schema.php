<?php
define('NO_LOGIN', true);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
include_once __DIR__ . '/../config.php';

$files = glob(__DIR__ . '/../inc/schemas/*.sql');
sort($files);
foreach ($files as $file) {
    echo "Aplicando " . basename($file) . "...\n";
    $sql = file_get_contents($file);
    if ($mysqli->multi_query($sql)) {
        do {
            if ($res = $mysqli->store_result()) $res->free();
        } while ($mysqli->more_results() && $mysqli->next_result());
    }
    if ($mysqli->errno) {
        fwrite(STDERR, "Error en " . basename($file) . ": " . $mysqli->error . "\n");
        exit(1);
    }
}

// Alérgenos habituales (los 14 de la UE) — semilla idempotente.
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

echo "Esquema aplicado correctamente.\n";
