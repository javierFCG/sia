<?php
// ajax_actualizar_materia.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

$materia_id = (int)$data['materia_id'];
$campo = $data['campo'];
$valor = $data['valor'];

$permitidos = ['semestre', 'horario', 'creditos'];

if (!in_array($campo, $permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Campo no permitido']);
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error BD']);
    exit();
}

$sql = "UPDATE materias SET $campo = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$tipo = ($campo === 'horario') ? "si" : "ii";
$stmt->bind_param($tipo, $valor, $materia_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>