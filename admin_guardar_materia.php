<?php
// admin_guardar_materia.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_POST['carrera_id'], $_POST['nombre'], $_POST['clave'], $_POST['creditos'], $_POST['semestre'])) {
    die("Datos incompletos.");
}

$carrera_id = (int)$_POST['carrera_id'];
$nombre = $_POST['nombre'];
$clave = $_POST['clave'];
$creditos = (int)$_POST['creditos'];
$semestre = (int)$_POST['semestre'];
$horario = $_POST['horario'] ?? '';
$docente = $_POST['docente'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt = $conn->prepare("INSERT INTO materias (nombre, clave, creditos, semestre, horario, descripcion, docente_asignado, carrera_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssiisssi", $nombre, $clave, $creditos, $semestre, $horario, $descripcion, $docente, $carrera_id);

if ($stmt->execute()) {
    header("Location: admin_gestionar_materias.php?carrera_id=" . $carrera_id . "&success=materia_creada");
    exit();
} else {
    echo "Error al crear: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>