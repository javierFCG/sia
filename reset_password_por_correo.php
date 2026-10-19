<?php
// reset_password_por_correo.php - Restablecer contraseña por correo
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "<h2>🔑 Restablecer Contraseña</h2>";

// Mostrar usuarios
$result = $conn->query("SELECT Id, Nombre, Correo, correo_institucional, Rol FROM usuarios ORDER BY Id");
echo "<h3>📋 Usuarios registrados:</h3>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Correo Institucional</th><th>Rol</th><th>Acción</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Id'] . "</td>";
    echo "<td>" . $row['Nombre'] . "</td>";
    echo "<td>" . $row['Correo'] . "</td>";
    echo "<td>" . ($row['correo_institucional'] ?? '—') . "</td>";
    echo "<td>" . $row['Rol'] . "</td>";
    echo "<td>";
    echo "<a href='reset_password_por_correo.php?reset=" . $row['Id'] . "'>🔄 Restablecer (TecNM2025)</a>";
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// Procesar restablecimiento
if (isset($_GET['reset']) && is_numeric($_GET['reset'])) {
    $id = (int)$_GET['reset'];
    $nueva_password = 'TecNM2025';
    $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE Id = ?");
    $stmt->bind_param("si", $hash, $id);
    
    if ($stmt->execute()) {
        // Obtener nombre del usuario
        $stmt2 = $conn->prepare("SELECT Nombre, Correo FROM usuarios WHERE Id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $user = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        
        echo "<p style='color:green; font-weight:bold;'>✅ Contraseña restablecida para <strong>" . $user['Nombre'] . "</strong></p>";
        echo "<p>🔑 Nueva contraseña: <strong>TecNM2025</strong></p>";
        echo "<p>📧 Puedes iniciar sesión con: <strong>" . $user['Correo'] . "</strong></p>";
        echo "<p><a href='Login.html' style='color:#1B396A; font-weight:bold;'>🔐 Ir al Login</a></p>";
    } else {
        echo "<p style='color:red;'>❌ Error al restablecer: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

$conn->close();
?>