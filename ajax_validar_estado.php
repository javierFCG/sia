<?php
// ajax_validar_estado.php - Verificar estado de validación
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['validado' => false, 'error' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['validado' => false, 'error' => 'Error de conexión']);
    exit();
}

// Verificar si el usuario ha sido validado (tiene correo institucional)
$stmt = $conn->prepare("SELECT correo_institucional, num_control, validado FROM usuarios WHERE Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verificar si ya tiene correo institucional (ha sido validado)
    $validado = !empty($user['correo_institucional']);
    
    echo json_encode([
        'validado' => $validado,
        'correo_institucional' => $user['correo_institucional'] ?? null,
        'num_control' => $user['num_control'] ?? null
    ]);
} else {
    echo json_encode(['validado' => false, 'error' => 'Usuario no encontrado']);
}

$conn->close();
?>