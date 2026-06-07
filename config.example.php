<?php
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'cpaneluser_boda');
define('DB_USER', 'cpaneluser_boda_user');
define('DB_PASS', 'CAMBIA_ESTA_CONTRASENA');

define('SITE_URL', 'https://bodaesttebanymaria.com');
define('ADMIN_PASSWORD', 'CAMBIA_ESTA_CONTRASENA_ADMIN');
define('ADMIN_EMAIL', 'tu-correo@example.com');
define('MAIL_FROM', 'noreply@bodaesttebanymaria.com');

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db_error = '';

if ($conn->connect_error) {
    $db_error = $conn->connect_error;
    $conn = null;
} else {
    $conn->set_charset('utf8mb4');
}

define('DB_AVAILABLE', $conn instanceof mysqli);
