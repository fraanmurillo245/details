<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
$mysqli = new mysqli('localhost', 'usuario', 'password', 'basededatos');
if ($mysqli->connect_errno) { die('Error de conexión a la base de datos.'); }
$mysqli->set_charset('utf8mb4');
