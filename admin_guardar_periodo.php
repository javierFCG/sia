<?php
// admin_guardar_periodo.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_POST['nombre'], $_POST['fecha_inicio'], $_POST['fecha_fin'], $_POST['estado'])) {
    die("Datos incompletos.");
}

$nombre = $_POST['nombre'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$estado = $_POST['estado'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt = $conn->prepare("INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nombre, $fecha_inicio, $fecha_fin, $estado);

if ($stmt->execute()) {
    header("Location: admin_gestionar_periodos.php?success=creado");
    exit();
} else {
    echo "Error al crear: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>