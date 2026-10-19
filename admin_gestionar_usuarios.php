<?php
// admin_gestionar_usuarios.php - Con numeración por rol
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$busqueda = isset($_GET['busqueda']) ? $conn->real_escape_string($_GET['busqueda']) : '';
$filtro_rol = isset($_GET['filtro_rol']) ? $_GET['filtro_rol'] : '';

// ============================================================
// OBTENER USUARIOS CON CARRERA DESDE INSCRIPCIONES
// ============================================================
$sql = "SELECT u.Id, u.Nombre, u.Correo, u.Rol, u.grupo, u.semestre, u.carrera_id, 
               u.correo_institucional, u.num_control, u.especialidad,
               c.nombre as carrera_nombre,
               i_insc.carrera_id as insc_carrera_id,
               c_insc.nombre as insc_carrera_nombre,
               i_insc.grupo as insc_grupo,
               i_insc.semestre as insc_semestre
        FROM usuarios u
        LEFT JOIN carreras c ON u.carrera_id = c.id
        LEFT JOIN (
            SELECT i1.usuario_id, i1.carrera_id, u1.grupo, u1.semestre
            FROM inscripciones i1
            JOIN usuarios u1 ON i1.usuario_id = u1.Id
            WHERE i1.id = (
                SELECT MAX(i2.id) FROM inscripciones i2 WHERE i2.usuario_id = i1.usuario_id
            )
        ) i_insc ON i_insc.usuario_id = u.Id
        LEFT JOIN carreras c_insc ON i_insc.carrera_id = c_insc.id
        WHERE 1=1";

if (!empty($busqueda)) {
    $sql .= " AND (u.Id LIKE '%$busqueda%' OR u.Correo LIKE '%$busqueda%' OR u.Rol LIKE '%$busqueda%' OR u.Nombre LIKE '%$busqueda%' OR u.num_control LIKE '%$busqueda%' OR u.especialidad LIKE '%$busqueda%')";
}

if (!empty($filtro_rol) && $filtro_rol !== 'todos') {
    $sql .= " AND u.Rol = '$filtro_rol'";
}

$sql .= " ORDER BY 
            CASE u.Rol 
                WHEN 'Administrador' THEN 1 
                WHEN 'Docente' THEN 2 
                WHEN 'Estudiante' THEN 3 
                WHEN 'NuevoIngreso' THEN 4 
                ELSE 5 
            END, 
            u.Nombre ASC";

$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);

// ============================================================
// CONTADORES POR ROL
// ============================================================
$total_administradores = 0;
$total_docentes = 0;
$total_estudiantes = 0;
$total_nuevoingreso = 0;

foreach ($users as $u) {
    if ($u['Rol'] === 'Administrador') $total_administradores++;
    elseif ($u['Rol'] === 'Docente') $total_docentes++;
    elseif ($u['Rol'] === 'NuevoIngreso') $total_nuevoingreso++;
    else $total_estudiantes++;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios · ITSZ</title>
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

        .container { max-width: 1400px; margin: 0 auto; }

        .card {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
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

        /* ===== ESTADÍSTICAS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--gris);
            padding: 14px 18px;
            border-radius: var(--radius);
            border-left: 4px solid var(--azul);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card .info .label {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--texto-oscuro);
            opacity: 0.6;
            font-weight: 600;
        }

        .stat-card .info .number {
            font-size: 22px;
            font-weight: 700;
            color: var(--azul);
        }

        .stat-card .icon {
            font-size: 24px;
            color: var(--dorado);
        }

        .stat-card.admin { border-left-color: var(--rojo); }
        .stat-card.docente { border-left-color: var(--azul); }
        .stat-card.estudiante { border-left-color: var(--verde-exito); }
        .stat-card.nuevoingreso { border-left-color: var(--naranja); }

        /* ===== FILTROS ===== */
        .filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            align-items: center;
        }

        .filtros .busqueda {
            flex: 1;
            min-width: 200px;
            display: flex;
            gap: 8px;
        }

        .filtros .busqueda input {
            flex: 1;
            padding: 10px 16px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 14px;
            background: var(--gris);
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .filtros .busqueda input:focus {
            border-color: var(--azul);
            outline: none;
            background: var(--blanco);
        }

        .filtros .busqueda button {
            padding: 10px 20px;
            background: var(--azul);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .filtros .busqueda button:hover { background: var(--guinda); transform: translateY(-2px); }

        .filtros .filtro-rol {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .filtros .filtro-rol .btn-rol {
            padding: 8px 16px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            background: var(--blanco);
            color: var(--texto-oscuro);
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
        }

        .filtros .filtro-rol .btn-rol:hover {
            border-color: var(--azul);
            transform: translateY(-2px);
        }

        .filtros .filtro-rol .btn-rol.active {
            background: var(--azul);
            color: var(--blanco);
            border-color: var(--azul);
        }

        .filtros .filtro-rol .btn-rol .count {
            display: inline-block;
            background: var(--gris);
            color: var(--texto-oscuro);
            padding: 0 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 4px;
        }

        .filtros .filtro-rol .btn-rol.active .count {
            background: rgba(255,255,255,0.2);
            color: var(--blanco);
        }

        .btn-limpiar {
            padding: 8px 20px;
            background: var(--rojo);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
            font-size: 12px;
        }
        .btn-limpiar:hover { background: #6b080c; transform: translateY(-2px); }

        /* ===== TABLA ===== */
        .table-responsive { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid var(--gris-oscuro);
            vertical-align: middle;
        }

        th {
            background: var(--azul);
            color: var(--blanco);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tr:hover { background: var(--gris); }

        /* ===== NÚMERO CONSECUTIVO ===== */
        .num-consecutivo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: var(--azul);
            color: var(--blanco);
            border-radius: 50%;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(27, 57, 106, 0.25);
        }

        /* Colores diferentes por rol */
        .row-admin .num-consecutivo { background: var(--rojo); box-shadow: 0 2px 8px rgba(134, 10, 15, 0.25); }
        .row-docente .num-consecutivo { background: var(--azul); box-shadow: 0 2px 8px rgba(27, 57, 106, 0.25); }
        .row-estudiante .num-consecutivo { background: var(--verde-exito); box-shadow: 0 2px 8px rgba(39, 174, 96, 0.25); }
        .row-nuevoingreso .num-consecutivo { background: var(--naranja); box-shadow: 0 2px 8px rgba(243, 156, 18, 0.25); }

        /* ===== ROW SEPARADORES POR ROL ===== */
        .row-admin td { background: #fde8e8; }
        .row-admin:hover td { background: #f8d7da; }

        .row-docente td { background: #e8f0fe; }
        .row-docente:hover td { background: #d6e4f8; }

        .row-estudiante td { background: #e8f5e9; }
        .row-estudiante:hover td { background: #d4edda; }

        .row-nuevoingreso td { background: #fff8e1; }
        .row-nuevoingreso:hover td { background: #fff3cd; }

        /* ===== BADGES ===== */
        .badge-rol {
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
        }

        .badge-admin { background: #fde8e8; color: #721c24; }
        .badge-docente { background: #cce5ff; color: #004085; }
        .badge-estudiante { background: #d4edda; color: #155724; }
        .badge-nuevoingreso { background: #fff3cd; color: #856404; }

        .badge-grupo {
            background: var(--gris);
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 9px;
            color: var(--texto-oscuro);
            display: inline-block;
            margin: 2px 0;
        }

        .badge-semestre {
            background: var(--azul);
            color: var(--blanco);
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            display: inline-block;
            margin: 2px 0;
        }

        .badge-carrera {
            background: var(--dorado);
            color: var(--azul);
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            display: inline-block;
            margin: 2px 0;
        }

        .badge-numcontrol {
            font-weight: 700;
            color: var(--guinda);
            font-size: 11px;
        }

        .badge-correo {
            font-size: 11px;
            color: var(--azul);
        }

        /* ===== ESPECIALIDADES DOCENTE ===== */
        .especialidades-docente {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .badge-especialidad {
            display: inline-block;
            background: #e8f0fe;
            color: var(--azul);
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
        }

        .sin-especialidad {
            color: #ccc;
            font-size: 10px;
            font-style: italic;
        }

        .btn-especialidad {
            display: inline-block;
            padding: 2px 10px;
            background: var(--naranja);
            color: var(--blanco);
            border-radius: var(--radius);
            text-decoration: none;
            font-size: 9px;
            font-weight: 600;
            transition: 0.3s;
            width: fit-content;
        }
        .btn-especialidad:hover { background: #d35400; transform: translateY(-2px); }

        /* ===== ACCIONES ===== */
        .action-btns {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 3px 10px;
            border: none;
            border-radius: var(--radius);
            color: var(--blanco);
            text-decoration: none;
            font-weight: 600;
            font-size: 10px;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-edit { background: var(--azul); }
        .btn-edit:hover { background: var(--guinda); }

        .btn-delete { background: var(--rojo); }
        .btn-delete:hover { background: #6b080c; }

        .btn-grupo {
            background: var(--naranja);
            color: var(--blanco);
            padding: 3px 10px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            font-size: 9px;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .btn-grupo:hover { background: #d35400; transform: translateY(-2px); }

        /* ===== SEPARADOR DE GRUPOS ===== */
        .separador-rol {
            background: var(--gris-oscuro);
            padding: 6px 14px;
            font-weight: 700;
            font-size: 12px;
            color: var(--azul);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px;
            margin-top: 8px;
        }
        .separador-rol i { margin-right: 8px; }

        /* ===== CELDA DE INFORMACIÓN ACADÉMICA ===== */
        .info-academica {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .info-academica .sin-datos {
            color: #ccc;
            font-size: 10px;
            font-style: italic;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--texto-oscuro);
            opacity: 0.6;
        }
        .empty-state i { font-size: 48px; display: block; margin-bottom: 12px; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
            .badge-rol { font-size: 8px; padding: 2px 8px; }
            .btn-action { font-size: 9px; padding: 3px 8px; }
            .badge-especialidad { font-size: 8px; }
            .num-consecutivo { width: 28px; height: 28px; font-size: 12px; }
        }

        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .filtros { flex-direction: column; }
            .filtros .busqueda { width: 100%; }
            .filtros .filtro-rol { justify-content: center; }
            table { font-size: 10px; }
            th, td { padding: 4px 6px; }
            .btn-action { font-size: 8px; padding: 2px 6px; }
            .btn-grupo { font-size: 8px; padding: 2px 6px; }
            .btn-especialidad { font-size: 8px; padding: 2px 8px; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .action-btns { flex-direction: column; }
            .btn-action { justify-content: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <div>
                <h1><i class="fas fa-users-cog"></i> Gestión de Usuarios</h1>
                <p style="color: rgba(255,255,255,0.85); font-size: 13px; margin-top: 2px;">
                    Administra estudiantes, docentes y administradores del sistema
                </p>
            </div>
            <a href="admin_panel.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <!-- ===== ESTADÍSTICAS ===== -->
            <div class="stats-grid">
                <div class="stat-card admin">
                    <div class="info">
                        <span class="label">Administradores</span>
                        <span class="number"><?php echo $total_administradores; ?></span>
                    </div>
                    <span class="icon"><i class="fas fa-user-shield"></i></span>
                </div>
                <div class="stat-card docente">
                    <div class="info">
                        <span class="label">Docentes</span>
                        <span class="number"><?php echo $total_docentes; ?></span>
                    </div>
                    <span class="icon"><i class="fas fa-chalkboard-teacher"></i></span>
                </div>
                <div class="stat-card estudiante">
                    <div class="info">
                        <span class="label">Estudiantes</span>
                        <span class="number"><?php echo $total_estudiantes; ?></span>
                    </div>
                    <span class="icon"><i class="fas fa-user-graduate"></i></span>
                </div>
                <div class="stat-card nuevoingreso">
                    <div class="info">
                        <span class="label">Nuevo Ingreso</span>
                        <span class="number"><?php echo $total_nuevoingreso; ?></span>
                    </div>
                    <span class="icon"><i class="fas fa-user-plus"></i></span>
                </div>
            </div>

            <!-- ===== FILTROS ===== -->
            <div class="filtros">
                <form method="GET" class="busqueda">
                    <input type="text" name="busqueda" placeholder="Buscar por nombre, correo, N° control o especialidad..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>

                <div class="filtro-rol">
                    <a href="admin_gestionar_usuarios.php?filtro_rol=todos<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-rol <?php echo empty($filtro_rol) || $filtro_rol === 'todos' ? 'active' : ''; ?>">
                        Todos <span class="count"><?php echo count($users); ?></span>
                    </a>
                    <a href="admin_gestionar_usuarios.php?filtro_rol=Administrador<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-rol <?php echo $filtro_rol === 'Administrador' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Admin <span class="count"><?php echo $total_administradores; ?></span>
                    </a>
                    <a href="admin_gestionar_usuarios.php?filtro_rol=Docente<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-rol <?php echo $filtro_rol === 'Docente' ? 'active' : ''; ?>">
                        <i class="fas fa-chalkboard-teacher"></i> Docentes <span class="count"><?php echo $total_docentes; ?></span>
                    </a>
                    <a href="admin_gestionar_usuarios.php?filtro_rol=Estudiante<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-rol <?php echo $filtro_rol === 'Estudiante' ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i> Estudiantes <span class="count"><?php echo $total_estudiantes; ?></span>
                    </a>
                    <a href="admin_gestionar_usuarios.php?filtro_rol=NuevoIngreso<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-rol <?php echo $filtro_rol === 'NuevoIngreso' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i> Nuevo Ingreso <span class="count"><?php echo $total_nuevoingreso; ?></span>
                    </a>

                    <?php if (!empty($busqueda) || !empty($filtro_rol)): ?>
                        <a href="admin_gestionar_usuarios.php" class="btn-limpiar">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== TABLA DE USUARIOS ===== -->
            <?php if (count($users) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align:center;">#</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Carrera / Semestre / Grupo</th>
                                <th>N° Control / Especialidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rol_actual = '';
                            
                            // ✅ CONTADORES INDEPENDIENTES POR ROL
                            $contador_admin = 0;
                            $contador_docente = 0;
                            $contador_estudiante = 0;
                            $contador_nuevoingreso = 0;
                            
                            foreach ($users as $user):
                                $row_class = '';
                                $badge_class = '';
                                $rol_icon = '';
                                
                                // ✅ ASIGNAR NÚMERO CONSECUTIVO SEGÚN EL ROL
                                if ($user['Rol'] === 'Administrador') {
                                    $row_class = 'row-admin';
                                    $badge_class = 'badge-admin';
                                    $rol_icon = '<i class="fas fa-user-shield"></i> ';
                                    $contador_admin++;
                                    $numero_mostrar = $contador_admin;
                                } elseif ($user['Rol'] === 'Docente') {
                                    $row_class = 'row-docente';
                                    $badge_class = 'badge-docente';
                                    $rol_icon = '<i class="fas fa-chalkboard-teacher"></i> ';
                                    $contador_docente++;
                                    $numero_mostrar = $contador_docente;
                                } elseif ($user['Rol'] === 'NuevoIngreso') {
                                    $row_class = 'row-nuevoingreso';
                                    $badge_class = 'badge-nuevoingreso';
                                    $rol_icon = '<i class="fas fa-user-plus"></i> ';
                                    $contador_nuevoingreso++;
                                    $numero_mostrar = $contador_nuevoingreso;
                                } else {
                                    $row_class = 'row-estudiante';
                                    $badge_class = 'badge-estudiante';
                                    $rol_icon = '<i class="fas fa-user-graduate"></i> ';
                                    $contador_estudiante++;
                                    $numero_mostrar = $contador_estudiante;
                                }

                                // Mostrar separador entre roles
                                if ($rol_actual !== $user['Rol']) {
                                    $rol_actual = $user['Rol'];
                                    $titulo_rol = $user['Rol'] === 'Administrador' ? 'Administradores' : 
                                                  ($user['Rol'] === 'Docente' ? 'Docentes' : 
                                                  ($user['Rol'] === 'NuevoIngreso' ? 'Nuevo Ingreso' : 'Estudiantes'));
                                    echo '<tr><td colspan="7" class="separador-rol">';
                                    echo '<i class="fas fa-users"></i> ' . $titulo_rol;
                                    echo '</td></tr>';
                                }

                                // Determinar carrera a mostrar
                                $carrera_mostrar = '';
                                if (!empty($user['insc_carrera_nombre'])) {
                                    $carrera_mostrar = $user['insc_carrera_nombre'];
                                } elseif (!empty($user['carrera_nombre'])) {
                                    $carrera_mostrar = $user['carrera_nombre'];
                                }

                                // Determinar grupo y semestre
                                $grupo_mostrar = !empty($user['insc_grupo']) ? $user['insc_grupo'] : $user['grupo'];
                                $semestre_mostrar = !empty($user['insc_semestre']) ? $user['insc_semestre'] : $user['semestre'];
                            ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <!-- ✅ NÚMERO CONSECUTIVO POR ROL -->
                                    <td style="text-align:center;">
                                        <span class="num-consecutivo">
                                            <?php echo $numero_mostrar; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['Nombre']); ?></td>
                                    <td>
                                        <span class="badge-correo">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['Correo']); ?>
                                        </span>
                                        <?php if (!empty($user['correo_institucional']) && $user['Correo'] !== $user['correo_institucional']): ?>
                                            <br>
                                            <span style="font-size: 9px; color: var(--guinda);">
                                                <i class="fas fa-university"></i> <?php echo htmlspecialchars($user['correo_institucional']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-rol <?php echo $badge_class; ?>">
                                            <?php echo $rol_icon . htmlspecialchars($user['Rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="info-academica">
                                            <?php if (!empty($carrera_mostrar)): ?>
                                                <span class="badge-carrera">
                                                    <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($carrera_mostrar); ?>
                                                </span>
                                            <?php elseif ($user['Rol'] === 'Estudiante' || $user['Rol'] === 'NuevoIngreso'): ?>
                                                <span class="sin-datos">Sin carrera asignada</span>
                                            <?php else: ?>
                                                <span style="color: #ccc; font-size: 10px;">—</span>
                                            <?php endif; ?>

                                            <?php if (!empty($semestre_mostrar) && ($user['Rol'] === 'Estudiante' || $user['Rol'] === 'NuevoIngreso')): ?>
                                                <span class="badge-semestre">
                                                    <i class="fas fa-layer-group"></i> <?php echo $semestre_mostrar; ?>° Semestre
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($grupo_mostrar) && ($user['Rol'] === 'Estudiante' || $user['Rol'] === 'NuevoIngreso')): ?>
                                                <span class="badge-grupo">
                                                    <i class="fas fa-users"></i> Grupo <?php echo htmlspecialchars($grupo_mostrar); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['num_control'])): ?>
                                            <div>
                                                <span class="badge-numcontrol">
                                                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($user['num_control']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($user['Rol'] === 'Docente'): ?>
                                            <div class="especialidades-docente" style="margin-top: 4px;">
                                                <?php if (!empty($user['especialidad'])): ?>
                                                    <span class="badge-especialidad">
                                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($user['especialidad']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="sin-especialidad">Sin especialidad</span>
                                                <?php endif; ?>
                                                <a href="admin_gestionar_docentes.php?id=<?php echo $user['Id']; ?>" class="btn-especialidad">
                                                    <i class="fas fa-edit"></i> Gestionar
                                                </a>
                                            </div>
                                        <?php elseif (empty($user['num_control'])): ?>
                                            <span style="color: #ccc; font-size: 10px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="admin_editar_usuario.php?id=<?php echo $user['Id']; ?>" 
                                               class="btn-action btn-edit" title="Editar usuario">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['Rol'] === 'Estudiante' || $user['Rol'] === 'NuevoIngreso'): ?>
                                                <a href="admin_editar_usuario.php?id=<?php echo $user['Id']; ?>#grupo" 
                                                   class="btn-grupo" title="Asignar grupo">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="admin_eliminar_usuario.php?id=<?php echo $user['Id']; ?>" 
                                               class="btn-action btn-delete" 
                                               onclick="return confirm('¿Eliminar a <?php echo addslashes($user['Nombre']); ?>?')" 
                                               title="Eliminar usuario">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <p>No se encontraron usuarios que coincidan con la búsqueda.</p>
                    <p style="font-size: 14px; margin-top: 4px;">
                        <a href="admin_gestionar_usuarios.php" style="color: var(--azul); font-weight: 600;">
                            <i class="fas fa-undo"></i> Mostrar todos
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>