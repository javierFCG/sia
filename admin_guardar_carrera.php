<?php
// admin_guardar_carrera.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_POST['nombre'], $_POST['clave'], $_POST['semestres'])) {
    die("Datos incompletos.");
}

$nombre = $_POST['nombre'];
$clave = $_POST['clave'];
$semestres = (int)$_POST['semestres'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt = $conn->prepare("INSERT INTO carreras (nombre, clave, duracion_semestres) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $nombre, $clave, $semestres);

if ($stmt->execute()) {
    header("Location: admin_gestionar_carreras.php?success=creada");
    exit();
} else {
    echo "Error al crear: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>