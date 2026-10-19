<?php
// admin_actualizar_carrera.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_POST['id'], $_POST['nombre'], $_POST['clave'], $_POST['semestres'])) {
    die("Datos incompletos.");
}

$id = (int)$_POST['id'];
$nombre = $_POST['nombre'];
$clave = $_POST['clave'];
$semestres = (int)$_POST['semestres'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt = $conn->prepare("UPDATE carreras SET nombre = ?, clave = ?, duracion_semestres = ? WHERE id = ?");
$stmt->bind_param("ssii", $nombre, $clave, $semestres, $id);

if ($stmt->execute()) {
    header("Location: admin_gestionar_carreras.php?success=actualizada");
    exit();
} else {
    echo "Error al actualizar: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>