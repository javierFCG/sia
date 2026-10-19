<?php
// admin_eliminar_calificacion.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['detalle_id']) || !isset($_GET['inscripcion_id'])) {
    die("Datos incompletos");
}

$detalle_id = (int)$_GET['detalle_id'];
$inscripcion_id = (int)$_GET['inscripcion_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt = $conn->prepare("UPDATE inscripciones_detalle SET calificacion = NULL WHERE id = ?");
$stmt->bind_param("i", $detalle_id);

if ($stmt->execute()) {
    header("Location: admin_calificaciones.php?inscripcion_id=$inscripcion_id&accion=editar&mensaje=Calificación eliminada&tipo=success");
    exit();
} else {
    header("Location: admin_calificaciones.php?inscripcion_id=$inscripcion_id&accion=editar&mensaje=Error al eliminar&tipo=error");
    exit();
}
$stmt->close();
$conn->close();
?>