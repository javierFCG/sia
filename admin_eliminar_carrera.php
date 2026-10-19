<?php
// admin_eliminar_carrera.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID no válido.");
}

$carrera_id = $_GET['id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt = $conn->prepare("DELETE FROM carreras WHERE id = ?");
$stmt->bind_param("i", $carrera_id);

if ($stmt->execute()) {
    header("Location: admin_gestionar_carreras.php?success=eliminada");
    exit();
} else {
    echo "Error al eliminar: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>