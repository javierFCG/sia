<?php
// estudiante_reset_carrera.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;

if (!isset($_GET['inscripcion_id']) || !is_numeric($_GET['inscripcion_id'])) {
    die("Error: Datos incompletos.");
}
$inscripcion_id = (int)$_GET['inscripcion_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$check = $conn->prepare("SELECT periodo_id FROM inscripciones WHERE id = ? AND usuario_id = ?");
$check->bind_param("ii", $inscripcion_id, $user_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $fila = $res->fetch_assoc();
    $periodo_id = $fila['periodo_id'];

    $stmt1 = $conn->prepare("DELETE FROM inscripciones_detalle WHERE inscripcion_id = ?");
    $stmt1->bind_param("i", $inscripcion_id);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("DELETE FROM inscripciones WHERE id = ?");
    $stmt2->bind_param("i", $inscripcion_id);
    $stmt2->execute();
    $stmt2->close();

    header("Location: estudiante_carrera.php?periodo_id=" . $periodo_id . "&cambiar=1");
    exit();
} else {
    die("Error: No tienes permiso para eliminar esta inscripción.");
}
$conn->close();
?>