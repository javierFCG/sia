<?php
// admin_actualizar_periodo.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_POST['id'], $_POST['nombre'], $_POST['fecha_inicio'], $_POST['fecha_fin'], $_POST['estado'])) {
    die("Datos incompletos.");
}

$id = (int)$_POST['id'];
$nombre = $_POST['nombre'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$estado = $_POST['estado'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt = $conn->prepare("UPDATE periodos SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, estado = ? WHERE id = ?");
$stmt->bind_param("ssssi", $nombre, $fecha_inicio, $fecha_fin, $estado, $id);

if ($stmt->execute()) {
    header("Location: admin_gestionar_periodos.php?success=actualizado");
    exit();
} else {
    echo "Error al actualizar: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>