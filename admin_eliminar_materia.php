<?php
// admin_eliminar_materia.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['carrera_id'])) {
    die("IDs no válidos.");
}

$materia_id = (int)$_GET['id'];
$carrera_id = (int)$_GET['carrera_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

try {
    $conn->begin_transaction();
    
    $stmt1 = $conn->prepare("DELETE FROM inscripciones_detalle WHERE materia_id = ?");
    $stmt1->bind_param("i", $materia_id);
    $stmt1->execute();
    $stmt1->close();
    
    $stmt2 = $conn->prepare("DELETE FROM materias WHERE id = ? AND carrera_id = ?");
    $stmt2->bind_param("ii", $materia_id, $carrera_id);
    
    if ($stmt2->execute()) {
        $conn->commit();
        header("Location: admin_gestionar_materias.php?carrera_id=$carrera_id&success=materia_eliminada");
        exit();
    } else {
        throw new Exception("No se pudo eliminar.");
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "Error al eliminar: " . $e->getMessage();
}
$conn->close();
?>