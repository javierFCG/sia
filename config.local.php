<?php
// config.example.php - Plantilla para el repositorio
// Copia este archivo a config.php y completa con tus credenciales reales

// ==============================================
// CREDENCIALES DE BASE DE DATOS
// ==============================================
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'tu_usuario');
define('DB_PASSWORD', 'tu_contraseña_aqui');
define('DB_NAME', 'tu_base_de_datos');

date_default_timezone_set('America/Mexico_City');

// ==============================================
// CONFIGURACIÓN DE CORREO
// ==============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'tu_correo@gmail.com');
define('SMTP_PASS', 'tu_app_password');
define('SMTP_PORT', 587);

// ==============================================
// URL BASE
// ==============================================
define('SITE_URL', 'https://tudominio.com/');

// ==============================================
// RUTAS
// ==============================================
define('UPLOAD_DIR', 'uploads/');
define('DOCUMENTOS_DIR', UPLOAD_DIR . 'documentos/');
define('PERFILES_DIR', UPLOAD_DIR . 'perfiles/');

// ==============================================
// CONFIGURACIÓN DEL SISTEMA
// ==============================================
define('SISTEMA_NOMBRE', 'Sistema Integral Académico ITSZ');
define('SISTEMA_VERSION', '2.0.0');
define('INSTITUCION', 'Tecnológico Superior de Zongolica');

// ==============================================
// FUNCIÓN DE CONEXIÓN
// ==============================================
function getDB() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión a la base de datos: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    $conn->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    return $conn;
}
?>