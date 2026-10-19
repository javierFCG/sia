<?php
// admin_gestionar_grupos.php - Gestión de grupos
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// ============================================================
// CREAR GRUPO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_grupo'])) {
    $nombre = trim($_POST['nombre']);
    $carrera_id = (int)$_POST['carrera_id'];
    $semestre = (int)$_POST['semestre'];
    $turno = $_POST['turno'];
    $capacidad = (int)$_POST['capacidad'];
    
    $stmt = $conn->prepare("INSERT INTO grupos (nombre, carrera_id, semestre, turno, capacidad) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siisi", $nombre, $carrera_id, $semestre, $turno, $capacidad);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Grupo creado exitosamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al crear el grupo: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
}

// ============================================================
// ELIMINAR GRUPO
// ============================================================
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    
    // Verificar si el grupo tiene horarios asignados
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM horarios WHERE grupo_id = ?");
    $stmt->bind_param("i", $id_eliminar);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result['total'] > 0) {
        $mensaje = "❌ No se puede eliminar el grupo porque tiene horarios asignados.";
        $mensaje_tipo = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM grupos WHERE id = ?");
        $stmt->bind_param("i", $id_eliminar);
        if ($stmt->execute()) {
            $mensaje = "✅ Grupo eliminado correctamente";
            $mensaje_tipo = "success";
        } else {
            $mensaje = "❌ Error al eliminar: " . $stmt->error;
            $mensaje_tipo = "error";
        }
        $stmt->close();
    }
}

// ============================================================
// OBTENER DATOS
// ============================================================

// Obtener grupos con datos de carrera
$sql = "SELECT g.*, c.nombre as carrera_nombre 
        FROM grupos g 
        JOIN carreras c ON g.carrera_id = c.id 
        ORDER BY c.nombre, g.semestre, g.nombre";
$grupos = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Obtener carreras para el select
$carreras = $conn->query("SELECT id, nombre FROM carreras ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Grupos - ITSZ</title>
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

        .container { max-width: 1100px; margin: 0 auto; }

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

        .card-header h1 i {
            color: var(--dorado);
            margin-right: 12px;
        }

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

        .card-header .btn-volver:hover {
            background: rgba(255,255,255,0.25);
        }

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

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--verde-exito);
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--rojo);
        }

        .mensaje i { font-size: 18px; }

        .btn-crear {
            padding: 10px 24px;
            background: var(--verde-exito);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-crear:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .grid-grupos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .grupo-card {
            background: var(--blanco);
            border-radius: var(--radius);
            padding: 18px 20px;
            border: 1px solid var(--gris-oscuro);
            border-left: 4px solid var(--azul);
            transition: all 0.3s;
        }

        .grupo-card:hover {
            border-color: var(--azul);
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .grupo-card h3 {
            color: var(--azul);
            font-size: 18px;
            margin-bottom: 4px;
        }

        .grupo-card .info {
            color: var(--texto-oscuro);
            font-size: 13px;
            margin: 4px 0;
        }

        .grupo-card .info strong {
            color: var(--azul);
        }

        .grupo-card .acciones {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-accion {
            padding: 6px 14px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            color: var(--blanco);
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-accion:hover {
            transform: translateY(-2px);
        }

        .btn-horarios {
            background: var(--azul);
        }

        .btn-horarios:hover {
            background: var(--guinda);
        }

        .btn-editar {
            background: var(--dorado);
            color: var(--azul);
        }

        .btn-editar:hover {
            background: #c8a23d;
        }

        .btn-eliminar-grupo {
            background: var(--rojo);
        }

        .btn-eliminar-grupo:hover {
            background: #6b080c;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--texto-oscuro);
            opacity: 0.6;
        }

        .empty-state i {
            font-size: 48px;
            display: block;
            margin-bottom: 12px;
        }

        /* ===== MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(27, 57, 106, 0.5);
            backdrop-filter: blur(3px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active { display: flex; }

        .modal-content {
            background: var(--blanco);
            padding: 32px;
            border-radius: var(--radius-lg);
            max-width: 480px;
            width: 90%;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            animation: modalFadeIn 0.25s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(15px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-content h3 {
            color: var(--azul);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .modal-content .modal-sub {
            color: var(--texto-oscuro);
            font-size: 0.85rem;
            margin-bottom: 18px;
            opacity: 0.7;
        }

        .modal-content .input-group {
            margin-bottom: 14px;
        }

        .modal-content .input-group label {
            display: block;
            font-weight: 600;
            color: var(--azul);
            margin-bottom: 4px;
            font-size: 0.8rem;
        }

        .modal-content .input-group input,
        .modal-content .input-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 0.9rem;
            background: var(--gris);
            font-family: 'Inter', sans-serif;
        }

        .modal-content .input-group input:focus,
        .modal-content .input-group select:focus {
            border-color: var(--azul);
            outline: none;
            background: var(--blanco);
        }

        .modal-acciones {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 6px;
        }

        .btn-cancelar-modal {
            background: var(--gris);
            color: var(--texto-oscuro);
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-cancelar-modal:hover {
            background: var(--gris-oscuro);
        }

        .btn-submit-modal {
            background: var(--azul);
            color: var(--blanco);
            padding: 10px 28px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit-modal:hover {
            background: var(--guinda);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            .grid-grupos { grid-template-columns: 1fr; }
        }

        @media (max-width: 480px) {
            .modal-content { padding: 24px; }
            .modal-acciones { flex-direction: column; }
            .modal-acciones button { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <div>
                <h1><i class="fas fa-users"></i> Gestión de Grupos</h1>
                <p>Administra los grupos y semestres para la distribución de horarios</p>
            </div>
            <a href="admin_panel.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <?php if (isset($mensaje)): ?>
                <div class="mensaje <?php echo $mensaje_tipo; ?>">
                    <i class="fas <?php echo $mensaje_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <!-- Botón Crear -->
            <div style="margin-bottom: 16px;">
                <button onclick="abrirModal()" class="btn-crear">
                    <i class="fas fa-plus-circle"></i> Nuevo Grupo
                </button>
            </div>

            <!-- Lista de grupos -->
            <?php if (count($grupos) > 0): ?>
                <div class="grid-grupos">
                    <?php foreach ($grupos as $g): ?>
                        <div class="grupo-card">
                            <h3><?php echo htmlspecialchars($g['nombre']); ?></h3>
                            <div class="info">
                                <strong>Carrera:</strong> <?php echo htmlspecialchars($g['carrera_nombre']); ?>
                            </div>
                            <div class="info">
                                <strong>Semestre:</strong> <?php echo $g['semestre']; ?>°
                            </div>
                            <div class="info">
                                <strong>Turno:</strong> <?php echo $g['turno']; ?>
                            </div>
                            <div class="info">
                                <strong>Capacidad:</strong> <?php echo $g['capacidad']; ?> alumnos
                            </div>
                            <div class="acciones">
                                <a href="admin_gestionar_horarios.php?grupo_id=<?php echo $g['id']; ?>" class="btn-accion btn-horarios">
                                    <i class="fas fa-calendar"></i> Horarios
                                </a>
                                <a href="admin_editar_grupo.php?id=<?php echo $g['id']; ?>" class="btn-accion btn-editar">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="admin_gestionar_grupos.php?eliminar=<?php echo $g['id']; ?>" 
                                   class="btn-accion btn-eliminar-grupo" 
                                   onclick="return confirm('¿Eliminar el grupo <?php echo addslashes($g['nombre']); ?>? Esta acción no se puede deshacer.')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <p>No hay grupos registrados</p>
                    <p style="font-size: 14px;">Crea un grupo para comenzar a organizar horarios</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== MODAL ===== -->
<div class="modal-overlay" id="modalGrupo">
    <div class="modal-content">
        <h3><i class="fas fa-plus-circle" style="color: var(--azul); margin-right: 10px;"></i> Nuevo Grupo</h3>
        <p class="modal-sub">Crea un nuevo grupo para organizar horarios y asignar estudiantes</p>

        <form method="POST">
            <div class="input-group">
                <label>Nombre del Grupo <span style="color: var(--rojo);">*</span></label>
                <input type="text" name="nombre" placeholder="Ej: 1A, 2B, 3C..." required>
            </div>

            <div class="input-group">
                <label>Carrera <span style="color: var(--rojo);">*</span></label>
                <select name="carrera_id" required>
                    <option value="">Selecciona una carrera...</option>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <label>Semestre <span style="color: var(--rojo);">*</span></label>
                <input type="number" name="semestre" value="1" min="1" max="12" required>
            </div>

            <div class="input-group">
                <label>Turno <span style="color: var(--rojo);">*</span></label>
                <select name="turno" required>
                    <option value="Matutino">Matutino</option>
                    <option value="Vespertino">Vespertino</option>
                    <option value="Mixto">Mixto</option>
                </select>
            </div>

            <div class="input-group">
                <label>Capacidad</label>
                <input type="number" name="capacidad" value="30" min="1" max="50">
            </div>

            <div class="modal-acciones">
                <button type="button" class="btn-cancelar-modal" onclick="cerrarModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" name="crear_grupo" class="btn-submit-modal">
                    <i class="fas fa-save"></i> Guardar Grupo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModal() {
        document.getElementById('modalGrupo').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        document.getElementById('modalGrupo').classList.remove('active');
        document.body.style.overflow = '';
    }

    document.getElementById('modalGrupo').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModal();
    });
</script>

</body>
</html>