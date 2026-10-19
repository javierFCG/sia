<?php
// exportar_excel.php - Exportación de datos a CSV (VERSIÓN COMPLETA)
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// ============================================================
// LISTA DE TIPOS DE EXPORTACIÓN PERMITIDOS
// ============================================================
$tipos_permitidos = [
    'estudiantes', 
    'docentes', 
    'carreras', 
    'materias', 
    'calificaciones', 
    'usuarios',
    'prestamos',
    'becas',
    'grupos',
    'periodos',
    'horarios'
];

if (!in_array($tipo, $tipos_permitidos)) {
    die("❌ Tipo de exportación no válido. Tipos permitidos: " . implode(', ', $tipos_permitidos));
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// ============================================================
// CONFIGURACIÓN DE DESCARGA
// ============================================================
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $tipo . '_' . date('Y-m-d') . '.csv');

// Agregar BOM para UTF-8 (para que Excel lo reconozca correctamente)
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// ============================================================
// EXPORTACIÓN POR TIPO
// ============================================================
switch ($tipo) {
    // ===== ESTUDIANTES =====
    case 'estudiantes':
        fputcsv($output, ['ID', 'Nombre', 'Correo', 'Correo Institucional', 'N° Control', 'Carrera', 'Semestre', 'Grupo']);
        $result = $conn->query("
            SELECT u.Id, u.Nombre, u.Correo, u.correo_institucional, u.num_control, 
                   c.nombre as carrera, u.semestre, u.grupo
            FROM usuarios u
            LEFT JOIN carreras c ON u.carrera_id = c.id
            WHERE u.Rol = 'Estudiante' 
            ORDER BY u.Nombre
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== DOCENTES =====
    case 'docentes':
        fputcsv($output, ['ID', 'Nombre', 'Correo', 'Especialidad']);
        $result = $conn->query("
            SELECT Id, Nombre, Correo, especialidad 
            FROM usuarios 
            WHERE Rol = 'Docente' 
            ORDER BY Nombre
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== TODOS LOS USUARIOS =====
    case 'usuarios':
        fputcsv($output, ['ID', 'Nombre', 'Correo', 'Rol', 'Correo Institucional', 'N° Control']);
        $result = $conn->query("
            SELECT Id, Nombre, Correo, Rol, correo_institucional, num_control 
            FROM usuarios 
            ORDER BY Rol, Nombre
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== CARRERAS =====
    case 'carreras':
        fputcsv($output, ['ID', 'Nombre', 'Clave', 'Semestres']);
        $result = $conn->query("SELECT id, nombre, clave, duracion_semestres FROM carreras ORDER BY nombre");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== MATERIAS =====
    case 'materias':
        fputcsv($output, ['ID', 'Nombre', 'Clave', 'Créditos', 'Semestre', 'Docente', 'Carrera']);
        $result = $conn->query("
            SELECT m.id, m.nombre, m.clave, m.creditos, m.semestre, 
                   m.docente_asignado as Docente, c.nombre as Carrera
            FROM materias m 
            JOIN carreras c ON m.carrera_id = c.id 
            ORDER BY c.nombre, m.semestre, m.nombre
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== CALIFICACIONES =====
    case 'calificaciones':
        fputcsv($output, ['Estudiante', 'Materia', 'Calificación', 'Estado']);
        $result = $conn->query("
            SELECT u.Nombre as Estudiante, m.nombre as Materia, idet.calificacion,
                CASE 
                    WHEN idet.calificacion >= 70 THEN 'Aprobado'
                    WHEN idet.calificacion IS NULL THEN 'Pendiente'
                    ELSE 'Reprobado'
                END as Estado
            FROM inscripciones_detalle idet
            JOIN inscripciones i ON idet.inscripcion_id = i.id
            JOIN usuarios u ON i.usuario_id = u.Id
            JOIN materias m ON idet.materia_id = m.id
            ORDER BY u.Nombre, m.nombre
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== PRÉSTAMOS =====
    case 'prestamos':
        fputcsv($output, ['ID', 'Libro', 'Estudiante', 'Fecha Préstamo', 'Fecha Devolución', 'Estado']);
        $result = $conn->query("
            SELECT id, libro, estudiante, fecha_prestamo, fecha_devolucion,
                CASE 
                    WHEN fecha_devolucion IS NULL THEN 'Activo'
                    ELSE 'Devuelto'
                END as Estado
            FROM prestamos 
            ORDER BY fecha_prestamo DESC
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== BECAS =====
    case 'becas':
        fputcsv($output, ['ID', 'Nombre', 'Descripción', 'Porcentaje', 'Cupo', 'Fecha Inicio', 'Fecha Fin', 'Activo']);
        $result = $conn->query("
            SELECT id, nombre, descripcion, porcentaje, cupo, 
                   fecha_inicio, fecha_fin, 
                   CASE WHEN activo = 1 THEN 'Activo' ELSE 'Inactivo' END as Activo
            FROM becas 
            ORDER BY nombre
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== GRUPOS =====
    case 'grupos':
        fputcsv($output, ['ID', 'Nombre', 'Carrera', 'Semestre', 'Turno', 'Capacidad']);
        $result = $conn->query("
            SELECT g.id, g.nombre, c.nombre as Carrera, g.semestre, g.turno, g.capacidad
            FROM grupos g
            JOIN carreras c ON g.carrera_id = c.id
            ORDER BY c.nombre, g.semestre, g.nombre
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== PERIODOS =====
    case 'periodos':
        fputcsv($output, ['ID', 'Nombre', 'Fecha Inicio', 'Fecha Fin', 'Estado']);
        $result = $conn->query("
            SELECT id, nombre, fecha_inicio, fecha_fin, estado 
            FROM periodos 
            ORDER BY fecha_inicio DESC
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ===== HORARIOS =====
    case 'horarios':
        fputcsv($output, ['ID', 'Materia', 'Grupo', 'Docente', 'Día', 'Hora Inicio', 'Hora Fin', 'Aula', 'Período']);
        $result = $conn->query("
            SELECT h.id, m.nombre as Materia, g.nombre as Grupo, 
                   u.Nombre as Docente, h.dia_semana, 
                   TIME_FORMAT(h.hora_inicio, '%H:%i') as 'Hora Inicio',
                   TIME_FORMAT(h.hora_fin, '%H:%i') as 'Hora Fin',
                   h.aula, p.nombre as Período
            FROM horarios h
            JOIN materias m ON h.materia_id = m.id
            JOIN grupos g ON h.grupo_id = g.id
            LEFT JOIN usuarios u ON h.docente_id = u.Id
            JOIN periodos p ON h.periodo_id = p.id
            ORDER BY h.dia_semana, h.hora_inicio
        ");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    default:
        fputcsv($output, ['Error', 'Tipo de exportación no válido']);
        break;
}

fclose($output);
$conn->close();
exit();
?>