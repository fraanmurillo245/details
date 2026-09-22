<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
// Desde PHP 8.1, mysqli lanza excepción en los fallos de conexión por defecto;
// se desactiva para poder comprobar connect_errno como antes y no filtrar
// rutas del servidor en un fatal error sin controlar.
mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = new mysqli('localhost', 'usuario', 'password', 'basededatos');
if ($mysqli->connect_errno) { die('Error de conexión a la base de datos.'); }
$mysqli->set_charset('utf8mb4');
