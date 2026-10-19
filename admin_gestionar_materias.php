<?php
// admin_gestionar_materias.php - Gestión de materias con asignación de docente
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['carrera_id']) || !is_numeric($_GET['carrera_id'])) {
    die("ID de Carrera no especificado.");
}
$carrera_id = (int)$_GET['carrera_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$mensaje = "";

// ============================================================
// ACTUALIZAR MATERIA (ASIGNAR DOCENTE)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_materia'])) {
    $id_mat = (int)$_POST['id_materia'];
    $semestre = (int)$_POST['semestre'];
    $docente_nombre = trim($_POST['docente_asignado']);

    $stmt_upd = $conn->prepare("UPDATE materias SET semestre=?, docente_asignado=? WHERE id=?");
    $stmt_upd->bind_param("isi", $semestre, $docente_nombre, $id_mat);
    if ($stmt_upd->execute()) {
        $mensaje = "✅ Materia actualizada correctamente.";
    } else {
        $mensaje = "❌ Error al actualizar: " . $stmt_upd->error;
    }
    $stmt_upd->close();
}

// ============================================================
// ELIMINAR MATERIA
// ============================================================
if (isset($_GET['eliminar_id'])) {
    $id_borrar = (int)$_GET['eliminar_id'];
    try {
        $conn->begin_transaction();
        
        $stmt_l = $conn->prepare("DELETE FROM inscripciones_detalle WHERE materia_id = ?");
        $stmt_l->bind_param("i", $id_borrar);
        $stmt_l->execute();
        $stmt_l->close();
        
        $stmt_del = $conn->prepare("DELETE FROM materias WHERE id = ?");
        $stmt_del->bind_param("i", $id_borrar);
        if ($stmt_del->execute()) {
            $conn->commit();
            $mensaje = "🗑️ Materia eliminada correctamente.";
        } else {
            throw new Exception("No se pudo eliminar.");
        }
        $stmt_del->close();
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "❌ Error al eliminar: " . $e->getMessage();
    }
}

// ============================================================
// OBTENER DATOS
// ============================================================

$stmt_c = $conn->prepare("SELECT nombre FROM carreras WHERE id = ?");
$stmt_c->bind_param("i", $carrera_id);
$stmt_c->execute();
$res_c = $stmt_c->get_result();
if ($res_c->num_rows === 0) { die("Carrera no encontrada."); }
$nombre_carrera = $res_c->fetch_assoc()['nombre'];
$stmt_c->close();

// Obtener docentes con sus especialidades
$docentes = $conn->query("
    SELECT u.Id, u.Nombre, u.especialidad,
           GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as carreras_especialidad,
           GROUP_CONCAT(DISTINCT de.tipo SEPARATOR ', ') as tipos_especialidad
    FROM usuarios u
    LEFT JOIN docentes_especialidades de ON u.Id = de.docente_id
    LEFT JOIN carreras c ON de.carrera_id = c.id
    WHERE u.Rol = 'Docente'
    GROUP BY u.Id
    ORDER BY u.Nombre
")->fetch_all(MYSQLI_ASSOC);

$sql_m = "SELECT id, nombre, clave, creditos, semestre, docente_asignado 
          FROM materias 
          WHERE carrera_id = ? 
          ORDER BY semestre ASC, nombre ASC";
$stmt_m = $conn->prepare($sql_m);
$stmt_m->bind_param("i", $carrera_id);
$stmt_m->execute();
$materias = $stmt_m->get_result();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materias de <?php echo htmlspecialchars($nombre_carrera); ?></title>
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
        .card-header .subtitle {
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

        .btn-crear {
            padding: 10px 24px;
            background: var(--verde-exito);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-crear:hover { background: #219a52; transform: translateY(-2px); }

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
            vertical-align: middle;
        }

        th {
            background: var(--azul);
            color: var(--blanco);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tr:hover { background: var(--gris); }

        .btn-icon {
            border: none;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: var(--radius);
            font-size: 13px;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-icon:hover { transform: translateY(-2px); }

        .btn-save { background: #e8f6f3; color: #27ae60; }
        .btn-save:hover { background: #d4edda; }

        .btn-delete { background: #fdedec; color: #c0392b; }
        .btn-delete:hover { background: #fadbd8; }

        select, input[type="number"] {
            padding: 6px 10px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 13px;
            background: var(--blanco);
            font-family: 'Inter', sans-serif;
            width: 100%;
            max-width: 200px;
        }
        select:focus, input[type="number"]:focus {
            border-color: var(--azul);
            outline: none;
        }

        .badge-docente {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            background: #e8f0fe;
            color: #1B396A;
        }

        .badge-especialidad {
            font-size: 9px;
            color: #666;
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--texto-oscuro);
            opacity: 0.6;
        }
        .empty-state i { font-size: 48px; display: block; margin-bottom: 12px; }

        .asignado-horario {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            background: #d4edda;
            color: #155724;
        }

        .no-asignado-horario {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            background: #fff3cd;
            color: #856404;
        }

        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            th, td { padding: 6px 8px; font-size: 11px; }
            select, input[type="number"] { font-size: 11px; padding: 4px 6px; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <div>
                <h1><i class="fas fa-book-open"></i> Gestión de Materias</h1>
                <div class="subtitle">Carrera: <strong><?php echo htmlspecialchars($nombre_carrera); ?></strong></div>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="admin_crear_materia.php?carrera_id=<?php echo htmlspecialchars($carrera_id); ?>" class="btn-crear">
                    <i class="fas fa-plus-circle"></i> Agregar Materia
                </a>
                <a href="admin_gestionar_carreras.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <?php if($mensaje): ?>
                <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                    <i class="fas <?php echo strpos($mensaje, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <?php if ($materias->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align:center;">Sem.</th>
                                <th>Clave</th>
                                <th>Materia</th>
                                <th style="text-align:center;">Créd.</th>
                                <th>Docente Asignado</th>
                                <th style="text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($materia = $materias->fetch_assoc()): ?>
                                <tr>
                                    <form method="POST">
                                        <input type="hidden" name="id_materia" value="<?php echo $materia['id']; ?>">
                                        <td style="text-align:center;">
                                            <input type="number" name="semestre" value="<?php echo $materia['semestre']; ?>" min="1" max="12" style="width:50px; text-align:center;">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($materia['clave']); ?></strong></td>
                                        <td><strong><?php echo htmlspecialchars($materia['nombre']); ?></strong></td>
                                        <td style="text-align:center;"><?php echo $materia['creditos']; ?></td>
                                        <td>
                                            <select name="docente_asignado" style="max-width: 250px;">
                                                <option value="">Sin asignar</option>
                                                <?php foreach ($docentes as $d): 
                                                    $selected = ($materia['docente_asignado'] == $d['Nombre']) ? 'selected' : '';
                                                    $especialidad_texto = !empty($d['carreras_especialidad']) ? ' (' . $d['carreras_especialidad'] . ')' : '';
                                                ?>
                                                    <option value="<?php echo htmlspecialchars($d['Nombre']); ?>" <?php echo $selected; ?>>
                                                        <?php echo htmlspecialchars($d['Nombre']); ?>
                                                        <?php echo $especialidad_texto; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (!empty($materia['docente_asignado'])): ?>
                                                <span class="asignado-horario">
                                                    <i class="fas fa-check-circle"></i> Asignado
                                                </span>
                                            <?php else: ?>
                                                <span class="no-asignado-horario">
                                                    <i class="fas fa-clock"></i> Sin asignar
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center; white-space: nowrap;">
                                            <button type="submit" class="btn-icon btn-save" title="Guardar cambios">
                                                <i class="fas fa-save"></i>
                                            </button>
                                            <a href="admin_gestionar_materias.php?carrera_id=<?php echo $carrera_id; ?>&eliminar_id=<?php echo $materia['id']; ?>" 
                                               class="btn-icon btn-delete" title="Eliminar materia"
                                               onclick="return confirm('¿Eliminar esta materia? Se borrará de la carga de TODOS los alumnos.')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </form>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <p>No hay materias registradas en esta carrera.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>