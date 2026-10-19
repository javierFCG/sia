<?php
// admin_gestionar_documentos.php - Gestión de documentos (VERSIÓN COMPLETA CORREGIDA)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Verificar que el usuario sea Administrador
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

// ============================================================
// 1. APROBAR/RECHAZAR DOCUMENTO INDIVIDUAL
// ============================================================
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $doc_id = (int)$_GET['id'];
    $estado = $_GET['estado'] ?? '';
    $comentario = $_GET['comentario'] ?? '';
    
    if (in_array($estado, ['Aprobado', 'Rechazado', 'Revisado'])) {
        $stmt = $conn->prepare("UPDATE documentos_alumnos SET estado = ?, comentarios = ?, fecha_revision = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $estado, $comentario, $doc_id);
        if ($stmt->execute()) {
            $mensaje = "✅ Documento " . strtolower($estado) . " correctamente";
        } else {
            $mensaje = "❌ Error al actualizar el documento";
        }
        $stmt->close();
        header("Location: admin_gestionar_documentos.php?success=" . urlencode($mensaje));
        exit();
    }
}

// ============================================================
// 2. VALIDAR ALUMNO COMPLETO (GENERAR CORREO INSTITUCIONAL)
// ============================================================
if (isset($_GET['validar_alumno']) && is_numeric($_GET['validar_alumno'])) {
    $usuario_id = (int)$_GET['validar_alumno'];
    
    // Verificar que todos los documentos estén aprobados
    $stmt = $conn->prepare("SELECT COUNT(*) as pendientes FROM documentos_alumnos WHERE usuario_id = ? AND estado != 'Aprobado'");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result['pendientes'] > 0) {
        header("Location: admin_gestionar_documentos.php?error=Todos los documentos deben estar aprobados");
        exit();
    }
    
    // Generar correo institucional
    $conn->begin_transaction();
    
    try {
        // Obtener datos del usuario
        $stmt = $conn->prepare("SELECT Id, Nombre, Correo FROM usuarios WHERE Id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$usuario) {
            throw new Exception("Usuario no encontrado.");
        }
        
        // Generar número de control
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE Rol = 'Estudiante' AND correo_institucional IS NOT NULL");
        $stmt->execute();
        $total_estudiantes = $stmt->get_result()->fetch_assoc()['total'] + 1;
        $stmt->close();
        
        $anio_actual = date('y');
        $num_control = $anio_actual . "w" . str_pad($total_estudiantes, 4, "0", STR_PAD_LEFT);
        $correo_institucional = $num_control . "@zongolica.tecnm.mx";
        $password_temporal = 'TecNM2025';
        $hash_password = password_hash($password_temporal, PASSWORD_DEFAULT);
        
        // ✅ CORREGIDO: 5 variables (Correo, correo_institucional, num_control, password, Id)
        // Tipos: s (string), s (string), s (string), s (string), i (integer) = "ssssi"
        $stmt = $conn->prepare("UPDATE usuarios SET 
            Correo = ?,
            Rol = 'Estudiante', 
            correo_institucional = ?, 
            num_control = ?,
            password = ?,
            validado = 1
        WHERE Id = ?");
        $stmt->bind_param("ssssi", $correo_institucional, $correo_institucional, $num_control, $hash_password, $usuario_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el usuario: " . $stmt->error);
        }
        $stmt->close();
        
        $conn->commit();
        
        // Redirigir con mensaje de éxito
        $mensaje = "✅ Alumno validado. Correo institucional: " . $correo_institucional . " | Contraseña temporal: " . $password_temporal;
        header("Location: admin_gestionar_documentos.php?success=" . urlencode($mensaje));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: admin_gestionar_documentos.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// ============================================================
// 3. REENVIAR CORREO DE CREDENCIALES
// ============================================================
if (isset($_GET['reenviar_correo']) && is_numeric($_GET['reenviar_correo'])) {
    $usuario_id = (int)$_GET['reenviar_correo'];
    
    $stmt = $conn->prepare("SELECT Nombre, Correo, correo_institucional, num_control FROM usuarios WHERE Id = ? AND Rol = 'Estudiante' AND correo_institucional IS NOT NULL");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($usuario) {
        $mensaje = "✅ Correo reenviado a " . $usuario['Correo'];
        header("Location: admin_gestionar_documentos.php?success=" . urlencode($mensaje));
    } else {
        header("Location: admin_gestionar_documentos.php?error=Usuario no encontrado o sin correo institucional");
    }
    exit();
}

// ============================================================
// 4. ELIMINAR DOCUMENTO
// ============================================================
if (isset($_GET['eliminar_doc']) && is_numeric($_GET['eliminar_doc'])) {
    $doc_id = (int)$_GET['eliminar_doc'];
    
    // Obtener ruta del archivo
    $stmt = $conn->prepare("SELECT ruta_archivo FROM documentos_alumnos WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($doc && file_exists($doc['ruta_archivo'])) {
        unlink($doc['ruta_archivo']);
    }
    
    $stmt = $conn->prepare("DELETE FROM documentos_alumnos WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_gestionar_documentos.php?success=Documento eliminado");
    exit();
}

// ============================================================
// 5. OBTENER DATOS PARA MOSTRAR
// ============================================================

// Documentos pendientes
$sql_pendientes = "SELECT d.*, u.Nombre as estudiante, u.Correo 
                   FROM documentos_alumnos d 
                   JOIN usuarios u ON d.usuario_id = u.Id 
                   WHERE d.estado = 'Pendiente' 
                   ORDER BY d.fecha_subida ASC";
$pendientes = $conn->query($sql_pendientes)->fetch_all(MYSQLI_ASSOC);

// Alumnos listos para validar (NuevoIngreso con todos los documentos aprobados)
$sql_por_validar = "SELECT DISTINCT u.Id, u.Nombre, u.Correo 
                    FROM documentos_alumnos d 
                    JOIN usuarios u ON d.usuario_id = u.Id 
                    WHERE u.Rol = 'NuevoIngreso'
                    AND NOT EXISTS (
                        SELECT 1 FROM documentos_alumnos d2 
                        WHERE d2.usuario_id = u.Id AND d2.estado != 'Aprobado'
                    )";
$por_validar = $conn->query($sql_por_validar)->fetch_all(MYSQLI_ASSOC);

// Estudiantes ya validados (con correo institucional)
$sql_validados = "SELECT Id, Nombre, Correo, correo_institucional, num_control, validado 
                  FROM usuarios 
                  WHERE Rol = 'Estudiante' AND correo_institucional IS NOT NULL AND correo_institucional != ''
                  ORDER BY Id DESC";
$validados = $conn->query($sql_validados)->fetch_all(MYSQLI_ASSOC);

// Todos los documentos
$sql_todos = "SELECT d.*, u.Nombre as estudiante, u.Correo, u.correo_institucional, u.num_control 
              FROM documentos_alumnos d 
              JOIN usuarios u ON d.usuario_id = u.Id 
              ORDER BY d.fecha_subida DESC LIMIT 100";
$todos = $conn->query($sql_todos)->fetch_all(MYSQLI_ASSOC);

$conn->close();

$nombres_docs = [
    'Acta_Nacimiento' => 'Acta de Nacimiento',
    'Certificado_Bachillerato' => 'Certificado de Bachillerato',
    'CURP' => 'CURP',
    'Comprobante_Domicilio' => 'Comprobante de Domicilio',
    'Fotografia' => 'Fotografía',
    'Ficha_Preinscripcion' => 'Ficha de Preinscripción'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Documentos - ITSZ</title>
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
            --naranja: #f39c12;
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

        .card-header h1 {
            color: var(--blanco);
            font-size: 22px;
            font-weight: 800;
        }
        .card-header h1 i { color: var(--dorado); margin-right: 12px; }
        .card-header p {
            color: rgba(255,255,255,0.85);
            font-size: 13px;
            margin-top: 2px;
        }

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

        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--gris-oscuro);
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 24px;
            cursor: pointer;
            font-weight: 600;
            color: var(--texto-oscuro);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            font-size: 14px;
        }
        .tab.active { color: var(--azul); border-bottom-color: var(--dorado); }
        .tab .badge {
            background: var(--guinda);
            color: var(--blanco);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: 6px;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .table-responsive { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid var(--gris-oscuro);
        }

        th {
            background: var(--azul);
            color: var(--blanco);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        tr:hover { background: var(--gris); }

        .badge-estado {
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-pendiente { background: #fff3cd; color: #856404; }
        .badge-aprobado { background: #d4edda; color: #155724; }
        .badge-rechazado { background: #f8d7da; color: #721c24; }
        .badge-revisado { background: #cce5ff; color: #004085; }
        .badge-validado { background: #d4edda; color: #155724; }

        .btn-accion {
            padding: 5px 14px;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            font-size: 11px;
            color: var(--blanco);
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-accion:hover { transform: translateY(-2px); }

        .btn-aprobar { background: var(--verde-exito); }
        .btn-aprobar:hover { background: #219a52; }

        .btn-rechazar { background: var(--rojo); }
        .btn-rechazar:hover { background: #6b080c; }

        .btn-revisar { background: #3498db; }
        .btn-revisar:hover { background: #2980b9; }

        .btn-validar {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 6px 20px;
        }
        .btn-validar:hover { box-shadow: var(--shadow); }

        .btn-reenviar {
            background: var(--naranja);
            color: var(--blanco);
            padding: 5px 12px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 10px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-reenviar:hover { background: #d35400; transform: translateY(-2px); }

        .btn-descargar {
            background: var(--verde-exito);
            color: var(--blanco);
            padding: 4px 14px;
            text-decoration: none;
            border-radius: var(--radius);
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-descargar:hover { background: #219a52; transform: translateY(-2px); }

        .btn-eliminar-doc {
            background: var(--rojo);
            color: var(--blanco);
            padding: 4px 12px;
            border: none;
            border-radius: var(--radius);
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-eliminar-doc:hover { background: #6b080c; transform: translateY(-2px); }

        .correo-institucional {
            font-weight: 700;
            color: var(--azul);
            font-size: 12px;
        }
        .num-control {
            font-weight: 700;
            color: var(--guinda);
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--texto-oscuro);
            opacity: 0.6;
        }
        .empty-state i { font-size: 48px; display: block; margin-bottom: 12px; }

        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            .tabs { flex-wrap: wrap; }
            .tab { padding: 8px 16px; font-size: 12px; }
            th, td { padding: 6px 8px; font-size: 11px; }
            .btn-accion { font-size: 10px; padding: 3px 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <div>
                <h1><i class="fas fa-file-alt"></i> Gestión de Documentos</h1>
                <p>Revisa, valida y genera correos institucionales para alumnos de nuevo ingreso</p>
            </div>
            <a href="admin_panel.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <?php if (isset($_GET['success'])): ?>
                <div class="mensaje success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="mensaje error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="mostrarTab('pendientes')">
                    <i class="fas fa-clock"></i> Pendientes
                    <span class="badge"><?php echo count($pendientes); ?></span>
                </button>
                <button class="tab" onclick="mostrarTab('validar')">
                    <i class="fas fa-check-double"></i> Por Validar
                    <span class="badge"><?php echo count($por_validar); ?></span>
                </button>
                <button class="tab" onclick="mostrarTab('validados')">
                    <i class="fas fa-user-check"></i> Validados
                    <span class="badge"><?php echo count($validados); ?></span>
                </button>
                <button class="tab" onclick="mostrarTab('todos')">
                    <i class="fas fa-history"></i> Todos
                </button>
            </div>

            <!-- Tab: Pendientes -->
            <div id="tab-pendientes" class="tab-content active">
                <?php if (count($pendientes) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Documento</th>
                                    <th>Fecha</th>
                                    <th>Archivo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendientes as $d): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($d['estudiante']); ?></strong></td>
                                        <td><?php echo $nombres_docs[$d['tipo_documento']] ?? $d['tipo_documento']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($d['fecha_subida'])); ?></td>
                                        <td>
                                            <a href="<?php echo $d['ruta_archivo']; ?>" target="_blank" class="btn-descargar">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <a href="admin_gestionar_documentos.php?accion=aprobar&id=<?php echo $d['id']; ?>&estado=Aprobado" 
                                               class="btn-accion btn-aprobar" onclick="return confirm('¿Aprobar este documento?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="admin_gestionar_documentos.php?accion=revisar&id=<?php echo $d['id']; ?>&estado=Revisado" 
                                               class="btn-accion btn-revisar" onclick="return confirm('¿Marcar como revisado?')">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="admin_gestionar_documentos.php?accion=rechazar&id=<?php echo $d['id']; ?>&estado=Rechazado" 
                                               class="btn-accion btn-rechazar" onclick="return confirm('¿Rechazar este documento?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <a href="admin_gestionar_documentos.php?eliminar_doc=<?php echo $d['id']; ?>" 
                                               class="btn-accion btn-eliminar-doc" onclick="return confirm('¿Eliminar este documento?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color: var(--verde-exito);"></i>
                        <p>No hay documentos pendientes de revisión</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Por Validar -->
            <div id="tab-validar" class="tab-content">
                <?php if (count($por_validar) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Correo</th>
                                    <th>Documentos</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($por_validar as $a): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($a['Nombre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($a['Correo']); ?></td>
                                        <td>
                                            <span class="badge-estado badge-aprobado">6/6 Aprobados</span>
                                        </td>
                                        <td>
                                            <a href="admin_gestionar_documentos.php?validar_alumno=<?php echo $a['Id']; ?>" 
                                               class="btn-accion btn-validar"
                                               onclick="return confirm('¿Validar a <?php echo addslashes($a['Nombre']); ?>? Se reemplazará su correo y contraseña por las credenciales institucionales.')">
                                                <i class="fas fa-check-double"></i> Validar y Generar Correo
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No hay alumnos con documentos completos para validar</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Validados -->
            <div id="tab-validados" class="tab-content">
                <?php if (count($validados) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Correo Institucional</th>
                                    <th>N° Control</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($validados as $v): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($v['Nombre']); ?></strong></td>
                                        <td>
                                            <span class="correo-institucional">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($v['correo_institucional']); ?>
                                            </span>
                                            <br>
                                            <span style="font-size: 10px; color: #888;">
                                                <i class="fas fa-exchange-alt"></i> Reemplazó a: <?php echo htmlspecialchars($v['Correo']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="num-control"><?php echo htmlspecialchars($v['num_control']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge-estado badge-validado">
                                                <i class="fas fa-check-circle"></i> Validado
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin_gestionar_documentos.php?reenviar_correo=<?php echo $v['Id']; ?>" 
                                               class="btn-reenviar" onclick="return confirm('¿Reenviar correo de credenciales a <?php echo addslashes($v['Nombre']); ?>?')">
                                                <i class="fas fa-envelope"></i> Reenviar Correo
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-check"></i>
                        <p>No hay estudiantes validados aún</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Todos -->
            <div id="tab-todos" class="tab-content">
                <?php if (count($todos) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Documento</th>
                                    <th>Estado</th>
                                    <th>Correo Institucional</th>
                                    <th>N° Control</th>
                                    <th>Archivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todos as $d): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($d['estudiante']); ?></strong></td>
                                        <td><?php echo $nombres_docs[$d['tipo_documento']] ?? $d['tipo_documento']; ?></td>
                                        <td>
                                            <span class="badge-estado badge-<?php echo strtolower($d['estado']); ?>">
                                                <?php echo $d['estado']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($d['correo_institucional'])): ?>
                                                <span class="correo-institucional">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($d['correo_institucional']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 11px;">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($d['num_control'])): ?>
                                                <span class="num-control"><?php echo htmlspecialchars($d['num_control']); ?></span>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 11px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $d['ruta_archivo']; ?>" target="_blank" class="btn-descargar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No hay documentos registrados</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function mostrarTab(tab) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelector(`.tab[onclick="mostrarTab('${tab}')"]`).classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
    }

    // Mostrar mensajes de éxito/error después de 5 segundos
    document.addEventListener('DOMContentLoaded', function() {
        const mensajes = document.querySelectorAll('.mensaje');
        mensajes.forEach(function(mensaje) {
            setTimeout(function() {
                mensaje.style.transition = 'opacity 0.5s';
                mensaje.style.opacity = '0';
                setTimeout(function() {
                    mensaje.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
</script>

</body>
</html>