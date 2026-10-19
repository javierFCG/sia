<?php
// docente_eliminar_tarea.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Docente') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['materia_id'])) {
    die("Datos inválidos.");
}

$tarea_id = (int)$_GET['id'];
$materia_id = (int)$_GET['materia_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Verificar que la tarea pertenece al docente
$docente = $_SESSION['username'];
$stmt = $conn->prepare("SELECT t.id FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE t.id = ? AND m.docente_asignado = ?");
$stmt->bind_param("is", $tarea_id, $docente);
$stmt->execute();
if ($stmt->get_result()->num_rows == 0) {
    die("No tienes permiso para eliminar esta tarea.");
}
$stmt->close();

// Eliminar entregas asociadas
$stmt = $conn->prepare("DELETE FROM entregas WHERE tarea_id = ?");
$stmt->bind_param("i", $tarea_id);
$stmt->execute();
$stmt->close();

// Eliminar tarea
$stmt = $conn->prepare("DELETE FROM tareas WHERE id = ?");
$stmt->bind_param("i", $tarea_id);
$stmt->execute();
$stmt->close();

$conn->close();

header("Location: docente_tareas.php?materia_id=" . $materia_id);
exit();
?>