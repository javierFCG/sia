<?php
// admin_eliminar_usuario.php - Eliminar usuario y TODOS sus datos relacionados
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID no válido.");
}

$user_id = (int)$_GET['id'];

// No permitir eliminarse a sí mismo
if ($user_id == $_SESSION['user_id']) {
    header("Location: admin_gestionar_usuarios.php?error=No puedes eliminarte a ti mismo");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

// ============================================================
// OBTENER NOMBRE DEL USUARIO ANTES DE ELIMINAR
// ============================================================
$stmt = $conn->prepare("SELECT Nombre FROM usuarios WHERE Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario) {
    die("Usuario no encontrado.");
}

$nombre_usuario = $usuario['Nombre'];

// ============================================================
// ELIMINAR TODOS LOS DATOS RELACIONADOS
// ============================================================
$conn->begin_transaction();

try {
    // 1. Eliminar documentos del alumno (y sus archivos físicos)
    $stmt = $conn->prepare("SELECT ruta_archivo FROM documentos_alumnos WHERE usuario_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Eliminar archivos físicos
    foreach ($docs as $doc) {
        if (!empty($doc['ruta_archivo']) && file_exists($doc['ruta_archivo'])) {
            @unlink($doc['ruta_archivo']);
        }
    }
    
    // Eliminar registros de documentos
    $stmt = $conn->prepare("DELETE FROM documentos_alumnos WHERE usuario_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // 2. Eliminar detalles de inscripción
    $sql_detalle = "DELETE idet FROM inscripciones_detalle idet 
                    JOIN inscripciones i ON idet.inscripcion_id = i.id 
                    WHERE i.usuario_id = ?";
    $stmt = $conn->prepare($sql_detalle);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // 3. Eliminar inscripciones
    $stmt = $conn->prepare("DELETE FROM inscripciones WHERE usuario_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // 4. Eliminar mensajes enviados
    $stmt = $conn->prepare("DELETE FROM mensajes WHERE remitente_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // 5. Eliminar mensajes recibidos
    $stmt = $conn->prepare("DELETE FROM mensajes WHERE destinatario_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // 6. Actualizar horarios donde este usuario era docente (poner a 0)
    $stmt = $conn->prepare("UPDATE horarios SET docente_id = 0 WHERE docente_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // 7. Actualizar materias donde este usuario era docente
    $stmt = $conn->prepare("UPDATE materias SET docente_asignado = NULL WHERE docente_asignado = ?");
    $stmt->bind_param("s", $nombre_usuario);
    $stmt->execute();
    $stmt->close();
    
    // 8. Eliminar especialidades del docente (si tiene)
    $stmt = $conn->prepare("DELETE FROM docentes_especialidades WHERE docente_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // 9. Eliminar tokens de recuperación (ya se eliminará con el usuario)
    
    // 10. Finalmente, eliminar el usuario
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE Id = ?");
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al eliminar usuario: " . $stmt->error);
    }
    $stmt->close();
    
    $conn->commit();
    
    // Redirigir con mensaje de éxito
    header("Location: admin_gestionar_usuarios.php?success=Usuario eliminado correctamente: " . urlencode($nombre_usuario));
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    header("Location: admin_gestionar_usuarios.php?error=" . urlencode("Error al eliminar: " . $e->getMessage()));
    exit();
}

$conn->close();
?>