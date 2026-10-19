<?php
// nuevo_ingreso_eliminar_documento.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'NuevoIngreso') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: nuevo_ingreso_documentos.php");
    exit();
}

$doc_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Verificar que el documento pertenece al usuario
$stmt = $conn->prepare("SELECT ruta_archivo FROM documentos_alumnos WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $doc_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $doc = $result->fetch_assoc();
    // Eliminar archivo físico
    if (file_exists($doc['ruta_archivo'])) {
        unlink($doc['ruta_archivo']);
    }
    // Eliminar de base de datos
    $stmt = $conn->prepare("DELETE FROM documentos_alumnos WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: nuevo_ingreso_documentos.php");
exit();
?>