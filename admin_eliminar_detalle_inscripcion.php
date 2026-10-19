<?php
// admin_eliminar_detalle_inscripcion.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$tipo = $_GET['tipo'] ?? '';
$inscripcion_id = (int)($_GET['inscripcion_id'] ?? 0);
$detalle_id = (int)($_GET['detalle_id'] ?? 0);

try {
    $conn->begin_transaction();

    if ($tipo === 'materia' && $detalle_id > 0) {
        $stmt = $conn->prepare("DELETE FROM inscripciones_detalle WHERE id = ?");
        $stmt->bind_param("i", $detalle_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_reporte_detalle_inscripcion.php?inscripcion_id=" . $inscripcion_id);
    } elseif ($tipo === 'inscripcion_completa' && $inscripcion_id > 0) {
        $stmt1 = $conn->prepare("DELETE FROM inscripciones_detalle WHERE inscripcion_id = ?");
        $stmt1->bind_param("i", $inscripcion_id);
        $stmt1->execute();
        $stmt1->close();
        
        $stmt2 = $conn->prepare("DELETE FROM inscripciones WHERE id = ?");
        $stmt2->bind_param("i", $inscripcion_id);
        $stmt2->execute();
        $stmt2->close();
        
        header("Location: admin_reporte_inscritos.php");
    } else {
        die("Operación no válida.");
    }
    
    $conn->commit();
    exit();
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
$conn->close();
?>