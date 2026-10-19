<?php
// admin_guardar_calificacion.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_POST['inscripcion_id']) || !isset($_POST['calificacion'])) {
    header("Location: admin_calificaciones.php?error=Datos incompletos");
    exit();
}

$inscripcion_id = (int)$_POST['inscripcion_id'];
$calificaciones = $_POST['calificacion'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$conn->begin_transaction();
$success = true;

try {
    foreach ($calificaciones as $detalle_id => $calificacion) {
        $detalle_id = (int)$detalle_id;
        
        if ($calificacion !== '' && $calificacion !== null) {
            $calificacion = (float)$calificacion;
            if ($calificacion < 0 || $calificacion > 100) {
                throw new Exception("Calificación inválida para el detalle ID: $detalle_id");
            }
        } else {
            $calificacion = null;
        }
        
        $stmt = $conn->prepare("UPDATE inscripciones_detalle SET calificacion = ? WHERE id = ?");
        $stmt->bind_param("di", $calificacion, $detalle_id);
        if (!$stmt->execute()) {
            throw new Exception("Error al guardar calificación: " . $stmt->error);
        }
        $stmt->close();
    }
    
    $conn->commit();
    $mensaje = "✅ Calificaciones guardadas exitosamente";
    $tipo = "success";
} catch (Exception $e) {
    $conn->rollback();
    $mensaje = "❌ Error: " . $e->getMessage();
    $tipo = "error";
}

$conn->close();

header("Location: admin_calificaciones.php?inscripcion_id=$inscripcion_id&accion=editar&mensaje=" . urlencode($mensaje) . "&tipo=$tipo");
exit();
?>