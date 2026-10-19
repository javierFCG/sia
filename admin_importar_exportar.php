<?php
// admin_importar_exportar.php - Importación y Exportación (SIN PRÉSTAMOS)
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$mensaje = "";
$mensaje_tipo = "";
$importados = 0;
$fallidos = 0;

// ============================================================
// FUNCIÓN AUXILIAR: LIMPIAR BOM Y ESPACIOS
// ============================================================
function limpiarTexto($texto) {
    if ($texto === null) return '';
    $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
    return trim($texto);
}

// ============================================================
// PROCESAR IMPORTACIÓN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar'])) {
    $tipo = $_POST['tipo_importacion'];
    $usuario_id = $_SESSION['user_id'];
    
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['archivo'];
        $nombre_original = $archivo['name'];
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        
        if ($extension !== 'csv') {
            $mensaje = "❌ Solo se permiten archivos CSV.";
            $mensaje_tipo = "error";
        } else {
            $directorio = "uploads/importaciones/";
            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }
            $ruta_archivo = $directorio . time() . "_" . $nombre_original;
            move_uploaded_file($archivo['tmp_name'], $ruta_archivo);
            
            $handle = fopen($ruta_archivo, 'r');
            $headers_raw = fgetcsv($handle);
            $headers = array_map('limpiarTexto', $headers_raw);
            
            $conn->begin_transaction();
            
            try {
                while (($data = fgetcsv($handle)) !== false) {
                    $data = array_map('limpiarTexto', $data);
                    
                    if (count($headers) !== count($data)) {
                        $fallidos++;
                        continue;
                    }
                    
                    $row = array_combine($headers, $data);
                    
                    switch ($tipo) {
                        case 'usuarios':
                            $importados += importarUsuario($conn, $row);
                            break;
                        case 'estudiantes':
                            $importados += importarEstudiante($conn, $row);
                            break;
                        case 'docentes':
                            $importados += importarDocente($conn, $row);
                            break;
                        case 'carreras':
                            $importados += importarCarrera($conn, $row);
                            break;
                        case 'materias':
                            $importados += importarMateria($conn, $row);
                            break;
                        case 'grupos':
                            $importados += importarGrupo($conn, $row);
                            break;
                        case 'becas':
                            $importados += importarBeca($conn, $row);
                            break;
                        case 'calificaciones':
                            $importados += importarCalificacion($conn, $row);
                            break;
                    }
                }
                fclose($handle);
                
                $conn->commit();
                
                $stmt = $conn->prepare("INSERT INTO importaciones (tipo, archivo, registros_importados, registros_fallidos, usuario_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiii", $tipo, $ruta_archivo, $importados, $fallidos, $usuario_id);
                $stmt->execute();
                $stmt->close();
                
                if ($importados > 0) {
                    $mensaje = "✅ Importación completada. <strong>$importados</strong> registros importados, <strong>$fallidos</strong> fallidos.";
                    $mensaje_tipo = "success";
                } else {
                    $mensaje = "⚠️ No se importó ningún registro. Verifica que el CSV tenga los encabezados correctos.";
                    $mensaje_tipo = "error";
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                $mensaje = "❌ Error durante la importación: " . $e->getMessage();
                $mensaje_tipo = "error";
            }
        }
    } else {
        $mensaje = "❌ No se seleccionó ningún archivo.";
        $mensaje_tipo = "error";
    }
}

// ============================================================
// FUNCIONES DE IMPORTACIÓN
// ============================================================

function importarUsuario($conn, $row) {
    global $fallidos;
    $nombre = trim($row['Nombre'] ?? '');
    $correo = trim($row['Correo'] ?? '');
    $password = trim($row['Contraseña'] ?? '');
    $rol = trim($row['Rol'] ?? 'Estudiante');
    $telefono = trim($row['Telefono'] ?? '');
    $direccion = trim($row['Direccion'] ?? '');
    
    if (empty($nombre) || empty($correo) || empty($password)) {
        $fallidos++;
        return 0;
    }
    
    $roles_validos = ['Administrador', 'Docente', 'Estudiante', 'NuevoIngreso'];
    if (!in_array($rol, $roles_validos)) $rol = 'Estudiante';
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (Nombre, Correo, password, Rol, telefono, direccion) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $correo, $hash, $rol, $telefono, $direccion);
    
    if ($stmt->execute()) {
        $stmt->close();
        return 1;
    } else {
        $fallidos++;
        $stmt->close();
        return 0;
    }
}

function importarEstudiante($conn, $row) {
    global $fallidos;
    $nombre = trim($row['Nombre'] ?? '');
    $correo = trim($row['Correo'] ?? '');
    $correo_institucional = trim($row['Correo_Institucional'] ?? '');
    $password = trim($row['Contraseña'] ?? '');
    $carrera = trim($row['Carrera'] ?? '');
    $semestre = (int)($row['Semestre'] ?? 1);
    $grupo = trim($row['Grupo'] ?? '');
    $telefono = trim($row['Telefono'] ?? '');
    $direccion = trim($row['Direccion'] ?? '');
    
    if (empty($nombre) || empty($correo) || empty($password)) {
        $fallidos++;
        return 0;
    }
    
    $carrera_id = 0;
    if (!empty($carrera)) {
        $stmt = $conn->prepare("SELECT id FROM carreras WHERE nombre = ?");
        $stmt->bind_param("s", $carrera);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row_c = $result->fetch_assoc()) {
            $carrera_id = $row_c['id'];
        }
        $stmt->close();
    }
    
    $num_control = '';
    if (!empty($correo_institucional)) {
        $num_control = explode('@', $correo_institucional)[0];
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (Nombre, Correo, correo_institucional, num_control, password, Rol, carrera_id, semestre, grupo, telefono, direccion, validado, activo) VALUES (?, ?, ?, ?, ?, 'Estudiante', ?, ?, ?, ?, ?, 1, 1)");
    $stmt->bind_param("sssssiisss", $nombre, $correo, $correo_institucional, $num_control, $hash, $carrera_id, $semestre, $grupo, $telefono, $direccion);
    
    if ($stmt->execute()) {
        $stmt->close();
        return 1;
    } else {
        $fallidos++;
        $stmt->close();
        return 0;
    }
}

function importarDocente($conn, $row) {
    global $fallidos;
    $nombre = trim($row['Nombre'] ?? '');
    $correo = trim($row['Correo'] ?? '');
    $correo_institucional = trim($row['Correo_Institucional'] ?? '');
    $password = trim($row['Contraseña'] ?? '');
    $especialidad = trim($row['Especialidad'] ?? '');
    $telefono = trim($row['Telefono'] ?? '');
    $direccion = trim($row['Direccion'] ?? '');
    
    if (empty($nombre) || empty($correo) || empty($password)) {
        $fallidos++;
        return 0;
    }
    
    $num_control = '';
    if (!empty($correo_institucional)) {
        $num_control = explode('@', $correo_institucional)[0];
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (Nombre, Correo, correo_institucional, num_control, password, Rol, especialidad, telefono, direccion, validado, activo) VALUES (?, ?, ?, ?, ?, 'Docente', ?, ?, ?, 1, 1)");
    $stmt->bind_param("ssssssss", $nombre, $correo, $correo_institucional, $num_control, $hash, $especialidad, $telefono, $direccion);
    
    if ($stmt->execute()) {
        $stmt->close();
        return 1;
    } else {
        $fallidos++;
        $stmt->close();
        return 0;
    }
}

function importarCarrera($conn, $row) {
    global $fallidos;
    $nombre = trim($row['Nombre'] ?? '');
    $clave = trim($row['Clave'] ?? '');
    $duracion = (int)($row['Duracion_Semestres'] ?? 8);
    
    if (empty($nombre) || empty($clave)) {
        $fallidos++;
        return 0;
    }
    
    $stmt = $conn->prepare("INSERT INTO carreras (nombre, clave, duracion_semestres) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nombre, $clave, $duracion);
    
    if ($stmt->execute()) {
        $stmt->close();
        return 1;
    } else {
        $fallidos++;
        $stmt->close();
        return 0;
    }
}

function importarMateria($conn, $row) {
    global $fallidos;
    $nombre = trim($row['Nombre'] ?? '');
    $clave = trim($row['Clave'] ?? '');
    $creditos = (int)($row['Creditos'] ?? 5);
    $semestre = (int)($row['Semestre'] ?? 1);
    $carrera_clave = trim($row['Carrera_Clave'] ?? '');
    $docente = trim($row['Docente'] ?? '');
    
    if (empty($nombre) || empty($clave) || empty($carrera_clave)) {
        $fallidos++;
        return 0;
    }
    
    $stmt = $conn->prepare("SELECT id FROM carreras WHERE clave = ?");
    $stmt->bind_param("s", $carrera_clave);
    $stmt->execute();
    $carrera = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$carrera) {
        $fallidos++;
        return 0;
    }
    
    $stmt = $conn->prepare("INSERT INTO materias (nombre, clave, creditos, semestre, carrera_id, docente_asignado) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiss", $nombre, $clave, $creditos, $semestre, $carrera['id'], $docente);
    
    if ($stmt->execute()) {
        $stmt->close();
        return 1;
    } else {
        $fallidos++;
        $stmt->close();
        return 0;
    }
}

function importarGrupo($conn, $row) {
    global $fallidos;
    $nombre = trim($row['Nombre'] ?? '');
    $carrera_nombre = trim($row['Carrera'] ?? '');
    $semestre = (int)($row['Semestre'] ?? 1);
    $turno = trim($row['Turno'] ?? 'Matutino');
    $capacidad = (int)($row['Capacidad'] ?? 30);
    
    if (empty($nombre) || empty($carrera_nombre)) {
        $fallidos++;
        return 0;
    }
    
    $stmt = $conn->prepare("SELECT id FROM carreras WHERE nombre = ?");
    $stmt->bind_param("s", $carrera_nombre);
    $stmt->execute();
    $carrera = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$carrera) {
        $fallidos++;
        return 0;
    }
    
    $stmt = $conn->prepare("INSERT INTO grupos (nombre, carrera_id, semestre, turno, capacidad) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siisi", $nombre, $carrera['id'], $semestre, $turno, $capacidad);
    
    if ($stmt->execute()) {
        $stmt->close();
        return 1;
    } else {
        $fallidos++;
        $stmt->close();
        return 0;
    }
}

function importarBeca($conn, $row) {
    global $fallidos;
    $nombre = trim($row['Nombre'] ?? '');
    $descripcion = trim($row['Descripcion'] ?? '');
    $porcentaje = (float)($row['Porcentaje'] ?? 0);
    $cupo = (int)($row['Cupo'] ?? 0);
    $fecha_inicio = trim($row['Fecha_Inicio'] ?? '');
    $fecha_fin = trim($row['Fecha_Fin'] ?? '');
    
    if (empty($nombre) || empty($descripcion)) {
        $fallidos++;
        return 0;
    }
    
    $stmt = $conn->prepare("INSERT INTO becas (nombre, descripcion, porcentaje, cupo, fecha_inicio, fecha_fin, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("ssdiss", $nombre, $descripcion, $porcentaje, $cupo, $fecha_inicio, $fecha_fin);
    
    if ($stmt->execute()) {
        $stmt->close();
        return 1;
    } else {
        $fallidos++;
        $stmt->close();
        return 0;
    }
}

// ============================================================
// FUNCIÓN CORREGIDA: IMPORTAR CALIFICACIONES
// Crea la inscripción automáticamente si no existe
// ============================================================
function importarCalificacion($conn, $row) {
    global $fallidos;
    $estudiante = trim($row['Estudiante'] ?? '');
    $materia = trim($row['Materia'] ?? '');
    $calificacion = (float)($row['Calificacion'] ?? 0);
    
    if (empty($estudiante) || empty($materia)) {
        $fallidos++;
        return 0;
    }
    
    // 1. Buscar estudiante
    $stmt = $conn->prepare("SELECT Id, carrera_id FROM usuarios WHERE Nombre = ? AND Rol = 'Estudiante'");
    $stmt->bind_param("s", $estudiante);
    $stmt->execute();
    $est = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$est) { $fallidos++; return 0; }
    
    // 2. Buscar materia
    $stmt = $conn->prepare("SELECT id, carrera_id FROM materias WHERE nombre = ?");
    $stmt->bind_param("s", $materia);
    $stmt->execute();
    $mat = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$mat) { $fallidos++; return 0; }
    
    // 3. Buscar inscripción del estudiante
    $stmt = $conn->prepare("SELECT id FROM inscripciones WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $est['Id']);
    $stmt->execute();
    $insc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // 4. ✅ SI NO EXISTE INSCRIPCIÓN, CREARLA
    if (!$insc) {
        $periodo = $conn->query("SELECT id FROM periodos WHERE estado = 'Activo' LIMIT 1")->fetch_assoc();
        if (!$periodo) {
            $periodo = $conn->query("SELECT id FROM periodos ORDER BY id DESC LIMIT 1")->fetch_assoc();
        }
        if (!$periodo) { $fallidos++; return 0; }
        
        $carrera_id = $est['carrera_id'] ?: $mat['carrera_id'];
        if (!$carrera_id) { $fallidos++; return 0; }
        
        $stmt = $conn->prepare("INSERT INTO inscripciones (usuario_id, carrera_id, periodo_id, fecha_inscripcion) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iii", $est['Id'], $carrera_id, $periodo['id']);
        if (!$stmt->execute()) {
            $fallidos++;
            $stmt->close();
            return 0;
        }
        $inscripcion_id = $conn->insert_id;
        $stmt->close();
    } else {
        $inscripcion_id = $insc['id'];
    }
    
    // 5. Verificar si ya existe la calificación
    $stmt = $conn->prepare("SELECT id FROM inscripciones_detalle WHERE inscripcion_id = ? AND materia_id = ?");
    $stmt->bind_param("ii", $inscripcion_id, $mat['id']);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($detalle) {
        // Actualizar calificación
        $stmt = $conn->prepare("UPDATE inscripciones_detalle SET calificacion = ? WHERE id = ?");
        $stmt->bind_param("di", $calificacion, $detalle['id']);
    } else {
        // Insertar nueva calificación
        $stmt = $conn->prepare("INSERT INTO inscripciones_detalle (inscripcion_id, materia_id, calificacion) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $inscripcion_id, $mat['id'], $calificacion);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        return 1;
    } else {
        $fallidos++;
        $stmt->close();
        return 0;
    }
}

// ============================================================
// OBTENER IMPORTACIONES RECIENTES
// ============================================================
$importaciones = $conn->query("SELECT i.*, u.Nombre as usuario 
                               FROM importaciones i 
                               JOIN usuarios u ON i.usuario_id = u.Id 
                               ORDER BY i.fecha_importacion DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

$conn->close();

// ============================================================
// TIPOS DE IMPORTACIÓN (SIN PRÉSTAMOS)
// ============================================================
$tipos_importacion = [
    'usuarios' => ['label' => 'Todos los Usuarios', 'icon' => 'fa-users'],
    'estudiantes' => ['label' => 'Estudiantes', 'icon' => 'fa-user-graduate'],
    'docentes' => ['label' => 'Docentes', 'icon' => 'fa-chalkboard-teacher'],
    'carreras' => ['label' => 'Carreras', 'icon' => 'fa-graduation-cap'],
    'materias' => ['label' => 'Materias', 'icon' => 'fa-book'],
    'grupos' => ['label' => 'Grupos', 'icon' => 'fa-layer-group'],
    'becas' => ['label' => 'Becas', 'icon' => 'fa-hand-holding-heart'],
    'calificaciones' => ['label' => 'Calificaciones', 'icon' => 'fa-edit']
];

// ============================================================
// TIPOS DE EXPORTACIÓN (SIN PRÉSTAMOS)
// ============================================================
$tipos_exportacion = [
    'estudiantes' => ['label' => 'Estudiantes', 'icon' => 'fa-user-graduate', 'desc' => 'Exportar lista de estudiantes'],
    'docentes' => ['label' => 'Docentes', 'icon' => 'fa-chalkboard-teacher', 'desc' => 'Exportar lista de docentes'],
    'carreras' => ['label' => 'Carreras', 'icon' => 'fa-graduation-cap', 'desc' => 'Exportar lista de carreras'],
    'materias' => ['label' => 'Materias', 'icon' => 'fa-book', 'desc' => 'Exportar lista de materias'],
    'calificaciones' => ['label' => 'Calificaciones', 'icon' => 'fa-edit', 'desc' => 'Exportar todas las calificaciones'],
    'usuarios' => ['label' => 'Todos los Usuarios', 'icon' => 'fa-users', 'desc' => 'Exportar todos los usuarios del sistema'],
    'becas' => ['label' => 'Becas', 'icon' => 'fa-hand-holding-heart', 'desc' => 'Exportar lista de becas']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar / Exportar Datos · ITSZ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --rojo: #860A0F;
            --dorado: #D99A32;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --texto-oscuro: #333333;
            --verde-exito: #27ae60;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .card {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .card-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 20px 30px;
            border-bottom: 4px solid var(--dorado);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .card-header h1 { color: var(--blanco); font-size: 22px; font-weight: 800; }
        .card-header h1 i { color: var(--dorado); margin-right: 12px; }
        .card-header p { color: rgba(255,255,255,0.85); font-size: 13px; margin-top: 2px; }
        .card-header .btn-volver {
            padding: 8px 20px;
            background: rgba(255,255,255,0.15);
            color: var(--blanco);
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.3s;
        }
        .card-header .btn-volver:hover { background: rgba(255,255,255,0.25); }
        .card-body { padding: 24px 30px; }
        .mensaje {
            padding: 12px 18px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid transparent;
        }
        .mensaje.success { background: #d4edda; color: #155724; border-left-color: var(--verde-exito); }
        .mensaje.error { background: #f8d7da; color: #721c24; border-left-color: var(--rojo); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 992px) { .grid-2 { grid-template-columns: 1fr; } }
        .import-section, .export-section {
            background: var(--gris);
            border-radius: var(--radius);
            padding: 20px;
            border: 2px dashed var(--gris-oscuro);
            transition: 0.3s;
        }
        .import-section:hover, .export-section:hover { border-color: var(--azul); }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--azul);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: var(--dorado); }
        .import-form select, .import-form input[type="file"] {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 14px;
            background: var(--blanco);
            font-family: 'Inter', sans-serif;
            margin-bottom: 12px;
        }
        .import-form select:focus, .import-form input[type="file"]:focus {
            border-color: var(--azul);
            outline: none;
        }
        .btn-importar {
            width: 100%;
            padding: 12px;
            background: var(--azul);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            font-size: 15px;
        }
        .btn-importar:hover { background: var(--guinda); transform: translateY(-2px); }
        .formato-info {
            font-size: 11px;
            color: var(--texto-oscuro);
            opacity: 0.6;
            margin-top: 8px;
            padding: 10px;
            background: var(--blanco);
            border-radius: var(--radius);
            border: 1px solid var(--gris-oscuro);
        }
        .formato-info code {
            background: var(--gris);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
        }
        .export-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 8px;
        }
        .export-item {
            background: var(--blanco);
            border-radius: var(--radius);
            padding: 12px 16px;
            border: 1px solid var(--gris-oscuro);
            transition: 0.3s;
            text-decoration: none;
            color: var(--texto-oscuro);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .export-item:hover {
            border-color: var(--azul);
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }
        .export-item .info .label { font-weight: 600; font-size: 13px; color: var(--azul); }
        .export-item .info .desc { font-size: 10px; color: var(--texto-oscuro); opacity: 0.6; }
        .export-item .badge-csv {
            background: var(--verde-exito);
            color: var(--blanco);
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
        }
        .export-item .icon { color: var(--dorado); font-size: 18px; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--gris-oscuro); }
        th {
            background: var(--azul);
            color: var(--blanco);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        tr:hover { background: var(--gris); }
        .badge-exito { color: var(--verde-exito); font-weight: 700; }
        .badge-error { color: var(--rojo); font-weight: 700; }
        .empty-state { text-align: center; padding: 30px; color: var(--texto-oscuro); opacity: 0.6; }
        .empty-state i { font-size: 40px; display: block; margin-bottom: 10px; }
        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            .export-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <div>
                <h1><i class="fas fa-exchange-alt"></i> Importar / Exportar Datos</h1>
                <p>Gestiona la importación y exportación de datos del sistema en formato CSV</p>
            </div>
            <a href="admin_panel.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $mensaje_tipo; ?>">
                    <i class="fas <?php echo $mensaje_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <div class="grid-2">
                <!-- ===== IMPORTACIÓN ===== -->
                <div class="import-section">
                    <div class="section-title">
                        <i class="fas fa-upload"></i> Importar Datos
                    </div>
                    <div class="import-form">
                        <form method="POST" enctype="multipart/form-data">
                            <label style="font-weight:600; color:var(--azul); font-size:13px;">Tipo de importación</label>
                            <select name="tipo_importacion" required>
                                <option value="">Selecciona un tipo...</option>
                                <?php foreach ($tipos_importacion as $key => $t): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $t['label']; ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label style="font-weight:600; color:var(--azul); font-size:13px;">Archivo CSV</label>
                            <input type="file" name="archivo" accept=".csv" required>

                            <button type="submit" name="importar" class="btn-importar">
                                <i class="fas fa-file-upload"></i> Importar Datos
                            </button>

                            <div class="formato-info" id="formatoInfo">
                                <i class="fas fa-info-circle"></i> 
                                Selecciona un tipo para ver el formato esperado.
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ===== EXPORTACIÓN ===== -->
                <div class="export-section">
                    <div class="section-title">
                        <i class="fas fa-download"></i> Exportar Datos
                    </div>
                    <p style="font-size:13px; color:var(--texto-oscuro); opacity:0.7; margin-bottom:12px;">
                        Haz clic en cualquier opción para exportar a CSV
                    </p>
                    <div class="export-grid">
                        <?php foreach ($tipos_exportacion as $key => $t): ?>
                            <a href="exportar_excel.php?tipo=<?php echo $key; ?>" class="export-item">
                                <div class="info">
                                    <div class="label">
                                        <i class="fas <?php echo $t['icon']; ?>" style="color:var(--dorado); margin-right:6px;"></i>
                                        <?php echo $t['label']; ?>
                                    </div>
                                    <div class="desc"><?php echo $t['desc']; ?></div>
                                </div>
                                <span class="badge-csv">CSV</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ===== HISTORIAL DE IMPORTACIONES ===== -->
            <div style="margin-top: 30px;">
                <h3 style="color: var(--azul); margin-bottom: 12px; font-size: 16px;">
                    <i class="fas fa-history"></i> Historial de Importaciones
                </h3>
                <?php if (count($importaciones) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Archivo</th>
                                    <th>Importados</th>
                                    <th>Fallidos</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($importaciones as $imp): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($imp['fecha_importacion'])); ?></td>
                                        <td>
                                            <span style="background:var(--gris); padding:2px 12px; border-radius:12px; font-size:11px;">
                                                <?php echo $tipos_importacion[$imp['tipo']]['label'] ?? $imp['tipo']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo basename($imp['archivo']); ?></td>
                                        <td class="badge-exito"><?php echo $imp['registros_importados']; ?></td>
                                        <td class="badge-error"><?php echo $imp['registros_fallidos']; ?></td>
                                        <td><?php echo htmlspecialchars($imp['usuario']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No hay importaciones registradas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelector('select[name="tipo_importacion"]').addEventListener('change', function() {
        const formatos = {
            'usuarios': 'Columnas: <code>Nombre</code>, <code>Correo</code>, <code>Contraseña</code>, <code>Rol</code>, <code>Telefono</code>, <code>Direccion</code>',
            'estudiantes': 'Columnas: <code>Nombre</code>, <code>Correo</code>, <code>Correo_Institucional</code>, <code>Contraseña</code>, <code>Carrera</code>, <code>Semestre</code>, <code>Grupo</code>',
            'docentes': 'Columnas: <code>Nombre</code>, <code>Correo</code>, <code>Correo_Institucional</code>, <code>Contraseña</code>, <code>Especialidad</code>',
            'carreras': 'Columnas: <code>Nombre</code>, <code>Clave</code>, <code>Duracion_Semestres</code>',
            'materias': 'Columnas: <code>Nombre</code>, <code>Clave</code>, <code>Creditos</code>, <code>Semestre</code>, <code>Carrera_Clave</code>, <code>Docente</code>',
            'grupos': 'Columnas: <code>Nombre</code>, <code>Carrera</code>, <code>Semestre</code>, <code>Turno</code>, <code>Capacidad</code>',
            'becas': 'Columnas: <code>Nombre</code>, <code>Descripcion</code>, <code>Porcentaje</code>, <code>Cupo</code>, <code>Fecha_Inicio</code>, <code>Fecha_Fin</code>',
            'calificaciones': 'Columnas: <code>Estudiante</code>, <code>Materia</code>, <code>Calificacion</code>'
        };
        const formato = formatos[this.value] || 'Selecciona un tipo para ver el formato esperado.';
        document.getElementById('formatoInfo').innerHTML = '<i class="fas fa-info-circle"></i> ' + formato;
    });
</script>

</body>
</html>