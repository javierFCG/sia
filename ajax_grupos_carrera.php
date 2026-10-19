<?php
// ajax_grupos_carrera.php - Obtener grupos de una carrera específica
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

if (!isset($_GET['carrera_id']) || !is_numeric($_GET['carrera_id'])) {
    echo json_encode(['grupos' => []]);
    exit();
}

$carrera_id = (int)$_GET['carrera_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión']);
    exit();
}

$stmt = $conn->prepare("
    SELECT id, nombre, semestre, turno, capacidad 
    FROM grupos 
    WHERE carrera_id = ? 
    ORDER BY semestre, nombre
");
$stmt->bind_param("i", $carrera_id);
$stmt->execute();
$grupos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode(['grupos' => $grupos]);
?>