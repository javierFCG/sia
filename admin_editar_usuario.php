<?php
// admin_editar_usuario.php - Editar usuario con grupos filtrados por carrera
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

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// ============================================================
// OBTENER DATOS DEL USUARIO (CON CARRERA DESDE INSCRIPCIONES)
// ============================================================
$stmt = $conn->prepare("SELECT Id, Nombre, Correo, Rol, grupo, semestre, carrera_id FROM usuarios WHERE Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { die("Usuario no encontrado."); }

// ============================================================
// ✅ OBTENER LA CARRERA DEL ESTUDIANTE (priorizar inscripciones)
// ============================================================
$carrera_del_estudiante = 0;
$carrera_nombre_estudiante = '';

// Primero, buscar la carrera más reciente en inscripciones
$stmt = $conn->prepare("
    SELECT i.carrera_id, c.nombre as carrera_nombre
    FROM inscripciones i
    JOIN carreras c ON i.carrera_id = c.id
    WHERE i.usuario_id = ?
    ORDER BY i.fecha_inscripcion DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$inscripcion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($inscripcion) {
    $carrera_del_estudiante = $inscripcion['carrera_id'];
    $carrera_nombre_estudiante = $inscripcion['carrera_nombre'];
} elseif (!empty($user['carrera_id'])) {
    $carrera_del_estudiante = $user['carrera_id'];
    $stmt = $conn->prepare("SELECT nombre FROM carreras WHERE id = ?");
    $stmt->bind_param("i", $carrera_del_estudiante);
    $stmt->execute();
    $carrera = $stmt->get_result()->fetch_assoc();
    $carrera_nombre_estudiante = $carrera['nombre'] ?? '';
    $stmt->close();
}

// ============================================================
// ✅ OBTENER SOLO LOS GRUPOS DE LA CARRERA DEL ESTUDIANTE
// ============================================================
$grupos = [];
if ($carrera_del_estudiante > 0) {
    $stmt = $conn->prepare("
        SELECT g.*, c.nombre as carrera_nombre 
        FROM grupos g
        JOIN carreras c ON g.carrera_id = c.id
        WHERE g.carrera_id = ?
        ORDER BY g.semestre, g.nombre
    ");
    $stmt->bind_param("i", $carrera_del_estudiante);
    $stmt->execute();
    $grupos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Obtener todas las carreras (por si el admin quiere cambiar la carrera)
$carreras = $conn->query("SELECT id, nombre FROM carreras ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$roles = ['Estudiante', 'Docente', 'Administrador'];
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - ITSZ</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container { max-width: 600px; width: 100%; }

        .card {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 24px 30px;
            border-bottom: 4px solid var(--dorado);
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
            margin-top: 4px;
        }

        .card-body { padding: 30px; }

        .input-group {
            margin-bottom: 16px;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            color: var(--azul);
            margin-bottom: 4px;
            font-size: 0.85rem;
        }

        .input-group label i {
            color: var(--dorado);
            width: 20px;
        }

        .input-group .required {
            color: var(--rojo);
            font-weight: 700;
        }

        .input-group input, .input-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 0.95rem;
            transition: all 0.3s;
            box-sizing: border-box;
            background: var(--gris);
            font-family: 'Inter', sans-serif;
        }

        .input-group input:focus, .input-group select:focus {
            border-color: var(--azul);
            outline: none;
            background: var(--blanco);
            box-shadow: 0 0 0 3px rgba(27, 57, 106, 0.08);
        }

        .input-group .help-text {
            font-size: 0.7rem;
            color: var(--texto-oscuro);
            opacity: 0.6;
            margin-top: 4px;
        }

        .input-group .help-text i {
            color: var(--dorado);
            margin-right: 4px;
        }

        /* ===== SECCIÓN DE GRUPO ===== */
        .grupo-section {
            background: #e8f0fe;
            padding: 18px 20px;
            border-radius: var(--radius);
            margin: 20px 0;
            border-left: 4px solid var(--dorado);
        }

        .grupo-section h3 {
            color: var(--azul);
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .grupo-section h3 i { color: var(--dorado); }

        .grupo-section .sub {
            font-size: 0.8rem;
            color: var(--texto-oscuro);
            opacity: 0.7;
            margin-bottom: 14px;
        }

        .carrera-info {
            background: var(--blanco);
            padding: 10px 14px;
            border-radius: var(--radius);
            margin-bottom: 14px;
            border: 1px solid var(--gris-oscuro);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }

        .carrera-info i {
            color: var(--dorado);
            font-size: 1.1rem;
        }

        .carrera-info strong {
            color: var(--azul);
        }

        .carrera-info.vacia {
            background: #fff8e1;
            border-color: #ffeaa7;
        }

        .carrera-info.vacia i {
            color: var(--naranja);
        }

        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        /* ===== BOTONES ===== */
        .btn-guardar {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }

        .btn-guardar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-volver {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: var(--azul);
            text-decoration: none;
            font-weight: 600;
        }

        .btn-volver:hover {
            color: var(--guinda);
            text-decoration: underline;
        }

        /* ===== PASS SECTION ===== */
        .pass-section {
            border-top: 1px solid var(--gris-oscuro);
            padding-top: 20px;
            margin-top: 20px;
        }

        .pass-section h3 {
            color: var(--azul);
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .pass-section .hint {
            font-size: 0.75rem;
            color: var(--texto-oscuro);
            opacity: 0.6;
            font-weight: 400;
        }

        @media (max-width: 480px) {
            .card-body { padding: 20px; }
            .row-2 { grid-template-columns: 1fr; gap: 0; }
            .card-header { padding: 18px 20px; }
            .card-header h1 { font-size: 18px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-user-edit"></i> Editar Usuario</h1>
            <p>Actualiza la información del usuario y asigna su grupo</p>
        </div>

        <div class="card-body">
            <form action="admin_actualizar_usuario.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $user['Id']; ?>">

                <!-- Nombre -->
                <div class="input-group">
                    <label><i class="fas fa-user"></i> Nombre de Usuario <span class="required">*</span></label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['Nombre']); ?>" required>
                </div>

                <!-- Correo -->
                <div class="input-group">
                    <label><i class="fas fa-envelope"></i> Correo Electrónico <span class="required">*</span></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['Correo']); ?>" required>
                </div>

                <!-- Rol -->
                <div class="input-group">
                    <label><i class="fas fa-user-tag"></i> Rol del Usuario <span class="required">*</span></label>
                    <select name="role" required>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol; ?>" <?php echo ($user['Rol'] === $rol) ? 'selected' : ''; ?>>
                                <?php echo $rol; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ===== SECCIÓN: ASIGNACIÓN DE GRUPO (SOLO PARA ESTUDIANTES) ===== -->
                <?php if ($user['Rol'] === 'Estudiante' || $user['Rol'] === 'NuevoIngreso'): ?>
                <div class="grupo-section">
                    <h3><i class="fas fa-users"></i> Asignación de Grupo</h3>
                    <p class="sub">Asigna un grupo al estudiante para que pueda ver su horario</p>

                    <!-- Info de la carrera -->
                    <?php if ($carrera_del_estudiante > 0): ?>
                        <div class="carrera-info">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Carrera del estudiante: <strong><?php echo htmlspecialchars($carrera_nombre_estudiante); ?></strong></span>
                        </div>
                    <?php else: ?>
                        <div class="carrera-info vacia">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>El estudiante aún no tiene carrera asignada. Primero asígnala en la sección de abajo.</span>
                        </div>
                    <?php endif; ?>

                    <div class="row-2">
                        <!-- ✅ GRUPO (SOLO DE LA CARRERA DEL ESTUDIANTE) -->
                        <div class="input-group">
                            <label><i class="fas fa-layer-group"></i> Grupo</label>
                            <select name="grupo" <?php echo empty($grupos) ? 'disabled' : ''; ?>>
                                <option value="">Sin asignar</option>
                                <?php if (count($grupos) > 0): ?>
                                    <?php foreach ($grupos as $g): ?>
                                        <option value="<?php echo htmlspecialchars($g['nombre']); ?>" 
                                            <?php echo ($user['grupo'] == $g['nombre']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($g['nombre']); ?> 
                                            (Sem. <?php echo $g['semestre']; ?> - <?php echo htmlspecialchars($g['turno']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="help-text">
                                <?php if (count($grupos) > 0): ?>
                                    <i class="fas fa-info-circle"></i> 
                                    Solo grupos de la carrera <strong><?php echo htmlspecialchars($carrera_nombre_estudiante); ?></strong>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle" style="color: var(--naranja);"></i> 
                                    No hay grupos disponibles para esta carrera
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Semestre -->
                        <div class="input-group">
                            <label><i class="fas fa-graduation-cap"></i> Semestre</label>
                            <input type="number" name="semestre" value="<?php echo $user['semestre'] ?? 1; ?>" min="1" max="12">
                            <div class="help-text">
                                <i class="fas fa-info-circle"></i> Semestre actual del estudiante
                            </div>
                        </div>
                    </div>

                    <!-- Carrera -->
                    <div class="input-group">
                        <label><i class="fas fa-book"></i> Carrera</label>
                        <select name="carrera_id">
                            <option value="">Seleccionar carrera</option>
                            <?php foreach ($carreras as $c): ?>
                                <option value="<?php echo $c['id']; ?>" 
                                    <?php echo ($carrera_del_estudiante == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i> 
                            Al cambiar la carrera, se actualizará la lista de grupos disponibles
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ===== SECCIÓN: CAMBIAR CONTRASEÑA ===== -->
                <div class="pass-section">
                    <h3><i class="fas fa-key"></i> Cambiar Contraseña <span class="hint">(Opcional)</span></h3>
                    <div class="input-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="password" placeholder="Déjalo vacío para no cambiarla">
                    </div>
                    <div class="input-group">
                        <label>Confirmar Nueva Contraseña</label>
                        <input type="password" name="confirm_password" placeholder="Repite la contraseña">
                    </div>
                </div>

                <button type="submit" class="btn-guardar">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>

            <a href="admin_gestionar_usuarios.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Cancelar y Volver
            </a>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPT: ACTUALIZAR GRUPOS AL CAMBIAR CARRERA
     ============================================================ -->
<script>
    // Cuando cambia la carrera, actualizar los grupos automáticamente
    const carreraSelect = document.querySelector('select[name="carrera_id"]');
    const grupoSelect = document.querySelector('select[name="grupo"]');

    if (carreraSelect && grupoSelect) {
        carreraSelect.addEventListener('change', function() {
            const carreraId = this.value;
            
            if (!carreraId) {
                // Resetear el select de grupos
                grupoSelect.innerHTML = '<option value="">Sin asignar</option>';
                grupoSelect.disabled = true;
                return;
            }

            // Mostrar "Cargando..."
            grupoSelect.innerHTML = '<option value="">Cargando grupos...</option>';
            grupoSelect.disabled = true;

            // Hacer petición AJAX
            fetch('ajax_grupos_carrera.php?carrera_id=' + carreraId)
                .then(response => response.json())
                .then(data => {
                    grupoSelect.innerHTML = '<option value="">Sin asignar</option>';
                    
                    if (data.grupos && data.grupos.length > 0) {
                        data.grupos.forEach(g => {
                            const option = document.createElement('option');
                            option.value = g.nombre;
                            option.textContent = `${g.nombre} (Sem. ${g.semestre} - ${g.turno})`;
                            grupoSelect.appendChild(option);
                        });
                        grupoSelect.disabled = false;
                    } else {
                        grupoSelect.innerHTML = '<option value="">Sin grupos disponibles</option>';
                        grupoSelect.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    grupoSelect.innerHTML = '<option value="">Error al cargar</option>';
                });
        });
    }
</script>

</body>
</html>