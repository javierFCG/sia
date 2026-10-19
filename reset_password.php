<?php
// reset_password.php - RESTABLECER CONTRASEÑA (VERSIÓN SIMPLE)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir configuración
require_once 'config.php';

// Conectar a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

echo "<h2>🔑 Restablecer Contraseña</h2>";

// Verificar si ya hay una contraseña para admin
$sql = "SELECT id, Nombre, password FROM usuarios WHERE Nombre = 'admin'";
$result = $conn->query($sql);

if ($result && $result->num_rows == 1) {
    $row = $result->fetch_assoc();
    echo "<p><strong>Usuario:</strong> " . $row['Nombre'] . "</p>";
    echo "<p><strong>ID:</strong> " . $row['id'] . "</p>";
    
    // Crear nueva contraseña
    $nueva_password = 'password';
    $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    // Actualizar
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE Nombre = 'admin'");
    $stmt->bind_param("s", $hash);
    
    if ($stmt->execute()) {
        echo "<p style='color:green; font-weight:bold;'>✅ Contraseña actualizada correctamente</p>";
        echo "<p style='font-size:18px;'>🔑 Usuario: <strong>admin</strong></p>";
        echo "<p style='font-size:18px;'>🔑 Contraseña: <strong>password</strong></p>";
        echo "<p><a href='Login.html' style='color:#1B396A; font-weight:bold;'>🔐 Ir al Login</a></p>";
    } else {
        echo "<p style='color:red;'>❌ Error al actualizar: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
    echo "<p style='color:red;'>❌ Usuario 'admin' no encontrado</p>";
    
    // Crear usuario admin si no existe
    $hash = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (Nombre, Correo, password, Rol) VALUES ('admin', 'admin@itsz.edu.mx', ?, 'Administrador')");
    $stmt->bind_param("s", $hash);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>✅ Usuario 'admin' creado correctamente</p>";
        echo "<p>🔑 Contraseña: <strong>password</strong></p>";
        echo "<p><a href='Login.html' style='color:#1B396A; font-weight:bold;'>🔐 Ir al Login</a></p>";
    } else {
        echo "<p style='color:red;'>❌ Error al crear usuario: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

$conn->close();
?>