<?php
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'cpaneluser_boda');
define('DB_USER', 'cpaneluser_boda_user');
define('DB_PASS', 'CAMBIA_ESTA_CONTRASENA');

define('SITE_URL', 'https://bodaesttebanymaria.com');
define('ADMIN_PASSWORD', 'CAMBIA_ESTA_CONTRASENA_ADMIN');
define('ADMIN_EMAIL', 'tu-correo@example.com');
define('MAIL_FROM', 'notificaciones@bodaesttebanymaria.com');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    die('Error de conexion a la base de datos.');
}

$conn->set_charset('utf8mb4');

