<?php
// corregir_correos.php - Corregir correos de usuarios validados
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "<h2>🔧 Corrigiendo correos de usuarios validados</h2>";

// Obtener usuarios que tienen correo institucional pero su Correo principal es diferente
$sql = "SELECT Id, Nombre, Correo, correo_institucional 
        FROM usuarios 
        WHERE Rol = 'Estudiante' 
        AND correo_institucional IS NOT NULL 
        AND correo_institucional != '' 
        AND Correo != correo_institucional";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Correo Actual</th><th>Correo Institucional</th><th>Acción</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Id'] . "</td>";
        echo "<td>" . $row['Nombre'] . "</td>";
        echo "<td style='color:red;'>" . $row['Correo'] . "</td>";
        echo "<td style='color:green;'>" . $row['correo_institucional'] . "</td>";
        
        // Actualizar
        $stmt = $conn->prepare("UPDATE usuarios SET Correo = ? WHERE Id = ?");
        $stmt->bind_param("si", $row['correo_institucional'], $row['Id']);
        
        if ($stmt->execute()) {
            echo "<td style='color:green;'>✅ Corregido</td>";
        } else {
            echo "<td style='color:red;'>❌ Error</td>";
        }
        $stmt->close();
        
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color:green;'>✅ Todos los correos han sido corregidos.</p>";
} else {
    echo "<p style='color:green;'>✅ No hay usuarios que necesiten corrección.</p>";
}

$conn->close();
?>