<?php
// ajax_docentes_materia.php - Obtener docentes asignados a una materia
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

if (!isset($_GET['materia_id']) || !is_numeric($_GET['materia_id'])) {
    echo json_encode(['error' => 'ID de materia no válido']);
    exit();
}

$materia_id = (int)$_GET['materia_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión']);
    exit();
}

// Obtener el docente asignado a la materia
$stmt = $conn->prepare("SELECT docente_asignado FROM materias WHERE id = ?");
$stmt->bind_param("i", $materia_id);
$stmt->execute();
$result = $stmt->get_result();
$docente_asignado = $result->fetch_assoc()['docente_asignado'] ?? '';
$stmt->close();

// Obtener el ID del docente
$docente_id = 0;
if (!empty($docente_asignado)) {
    $stmt = $conn->prepare("SELECT Id FROM usuarios WHERE Nombre = ? AND Rol = 'Docente'");
    $stmt->bind_param("s", $docente_asignado);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $docente_id = $result->fetch_assoc()['Id'];
    }
    $stmt->close();
}

$conn->close();

echo json_encode([
    'docente_nombre' => $docente_asignado,
    'docente_id' => $docente_id
]);
?>