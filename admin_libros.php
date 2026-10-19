<?php
// admin_libros.php - Gestión de libros
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

$mensaje = "";
$mensaje_tipo = "";

// Agregar libro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_libro'])) {
    $titulo = trim($_POST['titulo']);
    $autor = trim($_POST['autor']);
    $editorial = trim($_POST['editorial']);
    $anio = (int)$_POST['anio'];
    $isbn = trim($_POST['isbn']);
    $cantidad = (int)$_POST['cantidad'];
    
    $stmt = $conn->prepare("INSERT INTO libros (titulo, autor, editorial, anio, isbn, cantidad, disponibles) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssii", $titulo, $autor, $editorial, $anio, $isbn, $cantidad, $cantidad);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Libro agregado exitosamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al agregar el libro: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
}

// Eliminar libro
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM libros WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensaje = "🗑️ Libro eliminado correctamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al eliminar el libro";
        $mensaje_tipo = "error";
    }
    $stmt->close();
    header("Location: admin_libros.php");
    exit();
}

// Obtener libros
$libros = $conn->query("SELECT * FROM libros ORDER BY titulo ASC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca - ITSZ</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --rojo: #860A0F;
            --dorado: #D99A32;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-hover: 0 8px 25px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', 'Poppins', sans-serif; }
        body { background: var(--gris); color: #333; }

        .header-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 8px 0; box-shadow: var(--shadow); position: sticky; top: 0; z-index: 1000;
        }
        .header-itsz .container {
            max-width: 1200px; margin: 0 auto; padding: 0 20px;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;
        }
        .header-itsz .logo-container { display: flex; align-items: center; gap: 12px; }
        .header-itsz .logo-container img { height: 45px; width: auto; }
        .header-itsz .logo-container .titulo { color: var(--blanco); font-size: 13px; line-height: 1.2; }
        .header-itsz .logo-container .titulo strong { font-size: 16px; display: block; color: var(--dorado); }
        .header-itsz nav { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .header-itsz nav a { color: var(--blanco); font-size: 13px; font-weight: 500; padding: 6px 14px; border-radius: var(--radius); transition: all 0.3s; }
        .header-itsz nav a:hover { background: rgba(255,255,255,0.15); transform: translateY(-2px); }
        .header-itsz nav .btn-logout { background: var(--rojo); color: var(--blanco); padding: 6px 16px; border-radius: var(--radius); font-weight: 600; }
        .header-itsz nav .btn-logout:hover { background: #6b080c; }

        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }

        .page-header {
            background: white; padding: 20px 25px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { color: var(--azul); font-size: 24px; }
        .page-header h1 i { color: var(--guinda); }
        .page-header p { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }
        .btn-agregar { background: var(--dorado); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-agregar:hover { background: #c78a2a; transform: translateY(-2px); }

        .alert { padding: 15px; border-radius: var(--radius); margin-bottom: 20px; font-weight: 600; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--gris); font-size: 14px; vertical-align: middle; }
        th { background: var(--azul); color: white; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background: #f8f5f0; }

        .disponible { color: #27ae60; font-weight: 700; }
        .agotado { color: var(--rojo); font-weight: 700; }

        .btn-eliminar-libro {
            background: var(--rojo); color: var(--blanco); padding: 6px 14px; border: none; border-radius: var(--radius);
            cursor: pointer; font-weight: 600; font-size: 12px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;
        }
        .btn-eliminar-libro:hover { background: #6b080c; transform: translateY(-2px); }

        .empty-state {
            text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);
        }
        .empty-state i { font-size: 60px; color: var(--gris-oscuro); margin-bottom: 15px; display: block; }
        .empty-state h3 { color: var(--azul); font-size: 20px; margin-bottom: 10px; }
        .empty-state p { color: var(--gris-oscuro); font-size: 15px; }

        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center;
            animation: fadeIn 0.3s ease;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white; padding: 30px; border-radius: var(--radius-lg); max-width: 550px; width: 90%;
            box-shadow: var(--shadow-hover); max-height: 90vh; overflow-y: auto;
        }
        .modal-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
            border-bottom: 2px solid var(--gris); padding-bottom: 15px;
        }
        .modal-header h2 { color: var(--azul); font-size: 22px; }
        .modal-header h2 i { color: var(--guinda); }
        .modal-header .close { font-size: 28px; cursor: pointer; color: var(--gris-oscuro); transition: all 0.3s; }
        .modal-header .close:hover { color: var(--rojo); transform: rotate(90deg); }

        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-weight: 600; color: var(--azul); margin-bottom: 5px; font-size: 14px; }
        .input-group input {
            width: 100%; padding: 10px 14px; border: 2px solid var(--gris-oscuro); border-radius: var(--radius);
            font-size: 14px; transition: all 0.3s; box-sizing: border-box; background: var(--gris); color: #333;
        }
        .input-group input:focus {
            border-color: var(--azul); outline: none; background: var(--blanco);
            box-shadow: 0 0 0 3px rgba(27,57,106,0.1);
        }

        .modal-footer {
            display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;
            padding-top: 15px; border-top: 1px solid var(--gris);
        }
        .btn-cancelar { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 25px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-cancelar:hover { background: #b0b0b0; }
        .btn-guardar { background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%); color: var(--blanco); padding: 10px 25px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-guardar:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .footer-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco); padding: 20px; text-align: center; margin-top: 40px;
        }
        .footer-itsz .container { max-width: 1200px; margin: 0 auto; }
        .footer-itsz .social-links { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin: 10px 0; }
        .footer-itsz .social-links a { color: var(--blanco); font-size: 18px; transition: all 0.3s; }
        .footer-itsz .social-links a:hover { color: var(--dorado); transform: translateY(-3px); }
        .footer-itsz hr { border-color: rgba(255,255,255,0.15); margin: 10px 0; }
        .footer-itsz .copy { font-size: 11px; opacity: 0.8; }

        @media (max-width: 768px) {
            .header-itsz .container { flex-direction: column; align-items: center; text-align: center; }
            .header-itsz .logo-container { flex-direction: column; }
            .header-itsz nav { justify-content: center; }
            .header-itsz nav a { font-size: 11px; padding: 4px 8px; }
            .page-header { flex-direction: column; text-align: center; }
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
            .modal-content { padding: 20px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
            .btn-eliminar-libro { display: block; text-align: center; }
        }
    </style>
</head>
<body>
<header class="header-itsz">
    <div class="container">
        <div class="logo-container">
            <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
            <div class="titulo"><strong>Tecnológico Superior de Zongolica</strong><small>Sistema Integral Académico</small></div>
        </div>
        <nav>
            <a href="index.php"><i class='bx bx-home'></i> Inicio</a>
            <a href="admin_panel.php"><i class='bx bxs-dashboard'></i> Panel</a>
            <a href="mensajes.php"><i class='bx bxs-envelope'></i> Mensajes</a>
            <a href="logout.php" class="btn-logout"><i class='bx bx-log-out'></i> Salir</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <div><h1><i class='bx bxs-book'></i> Biblioteca</h1><p>Gestión de libros y préstamos</p></div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="abrirModal()" class="btn-agregar"><i class='bx bxs-plus-circle'></i> Agregar Libro</button>
            <a href="admin_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert <?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <?php if (count($libros) > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Editorial</th>
                        <th style="text-align:center;">Disponibles</th>
                        <th style="text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($libros as $l): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($l['titulo']); ?></strong></td>
                        <td><?php echo htmlspecialchars($l['autor']); ?></td>
                        <td><?php echo htmlspecialchars($l['editorial']); ?></td>
                        <td style="text-align:center;">
                            <span class="<?php echo $l['disponibles'] > 0 ? 'disponible' : 'agotado'; ?>">
                                <?php echo $l['disponibles']; ?> / <?php echo $l['cantidad']; ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="admin_libros.php?eliminar=<?php echo $l['id']; ?>" class="btn-eliminar-libro" onclick="return confirm('¿Eliminar este libro?')">
                                <i class='bx bxs-trash'></i> Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top: 15px; text-align: right; color: var(--gris-oscuro); font-size: 13px;">
                <i class='bx bxs-book'></i> Total: <strong><?php echo count($libros); ?></strong> libros en biblioteca
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-book-x'></i>
            <h3>No hay libros registrados</h3>
            <p>Haz clic en "Agregar Libro" para comenzar a gestionar la biblioteca.</p>
            <button onclick="abrirModal()" class="btn-agregar" style="margin-top: 15px; padding: 12px 30px; font-size: 16px;">
                <i class='bx bxs-plus-circle'></i> Agregar Libro
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para agregar libro -->
<div class="modal" id="modalLibro">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class='bx bxs-book-add'></i> Agregar Libro</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <form method="POST">
            <div class="input-group">
                <label>Título *</label>
                <input type="text" name="titulo" required placeholder="Título del libro">
            </div>
            <div class="input-group">
                <label>Autor *</label>
                <input type="text" name="autor" required placeholder="Nombre del autor">
            </div>
            <div class="input-group">
                <label>Editorial</label>
                <input type="text" name="editorial" placeholder="Editorial">
            </div>
            <div class="input-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Año</label>
                    <input type="number" name="anio" placeholder="2024">
                </div>
                <div>
                    <label>ISBN</label>
                    <input type="text" name="isbn" placeholder="978-3-16-148410-0">
                </div>
            </div>
            <div class="input-group">
                <label>Cantidad *</label>
                <input type="number" name="cantidad" min="1" value="1" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" name="agregar_libro" class="btn-guardar"><i class='bx bxs-save'></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<footer class="footer-itsz">
    <div class="container">
        <p><strong>Tecnológico Superior de Zongolica</strong><br>Carretera a la Compañía S/N, Zongolica, Veracruz</p>
        <div class="social-links">
            <a href="#"><i class='bx bxl-facebook'></i></a>
            <a href="#"><i class='bx bxl-twitter'></i></a>
            <a href="#"><i class='bx bxl-instagram'></i></a>
            <a href="#"><i class='bx bxl-youtube'></i></a>
        </div>
        <hr><p class="copy">&copy; <?php echo date('Y'); ?> Tecnológico Superior de Zongolica - Todos los derechos reservados</p>
    </div>
</footer>

<script>
    function abrirModal() {
        document.getElementById('modalLibro').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        document.getElementById('modalLibro').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('modalLibro').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModal();
    });
</script>
</body>
</html>