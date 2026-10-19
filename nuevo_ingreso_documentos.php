<?php
// nuevo_ingreso_documentos.php - Subida de documentos para alumnos de nuevo ingreso (DISEÑO ITSZ)
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'NuevoIngreso') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$mensaje = "";
$mensaje_tipo = "";

// Crear directorios si no existen
if (!file_exists(DOCUMENTOS_DIR)) {
    mkdir(DOCUMENTOS_DIR, 0777, true);
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Procesar subida de documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_documento'])) {
    $tipo_documento = $_POST['tipo_documento'];
    
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['archivo'];
        $nombre_original = $archivo['name'];
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        $tipos_permitidos = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        
        if (!in_array($extension, $tipos_permitidos)) {
            $mensaje = "❌ Tipo de archivo no permitido. Solo PDF, JPG, PNG, DOC.";
            $mensaje_tipo = "error";
        } else {
            $nombre_nuevo = $user_id . "_" . $tipo_documento . "_" . time() . "." . $extension;
            $ruta_destino = DOCUMENTOS_DIR . $nombre_nuevo;
            
            if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                $stmt = $conn->prepare("INSERT INTO documentos_alumnos (usuario_id, tipo_documento, nombre_archivo, ruta_archivo) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user_id, $tipo_documento, $nombre_original, $ruta_destino);
                if ($stmt->execute()) {
                    $mensaje = "✅ Documento subido correctamente.";
                    $mensaje_tipo = "success";
                } else {
                    $mensaje = "❌ Error al guardar en base de datos.";
                    $mensaje_tipo = "error";
                }
                $stmt->close();
            } else {
                $mensaje = "❌ Error al mover el archivo.";
                $mensaje_tipo = "error";
            }
        }
    } else {
        $mensaje = "❌ No se seleccionó ningún archivo.";
        $mensaje_tipo = "error";
    }
}

// Obtener documentos del usuario
$sql = "SELECT * FROM documentos_alumnos WHERE usuario_id = ? ORDER BY fecha_subida DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$documentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Verificar si todos los documentos están completos
$documentos_requeridos = ['Acta_Nacimiento', 'Certificado_Bachillerato', 'CURP', 'Comprobante_Domicilio', 'Fotografia', 'Ficha_Preinscripcion'];
$documentos_subidos = array_column($documentos, 'tipo_documento');
$documentos_faltantes = array_diff($documentos_requeridos, $documentos_subidos);
$todos_completos = empty($documentos_faltantes);

$conn->close();

// Nombres para mostrar
$nombres_documentos = [
    'Acta_Nacimiento' => 'Acta de Nacimiento',
    'Certificado_Bachillerato' => 'Certificado de Bachillerato',
    'CURP' => 'CURP',
    'Comprobante_Domicilio' => 'Comprobante de Domicilio',
    'Fotografia' => 'Fotografía Infantil',
    'Ficha_Preinscripcion' => 'Ficha de Preinscripción TecNM'
];

$iconos_documentos = [
    'Acta_Nacimiento' => 'fa-file-pdf',
    'Certificado_Bachillerato' => 'fa-file-alt',
    'CURP' => 'fa-id-card',
    'Comprobante_Domicilio' => 'fa-house',
    'Fotografia' => 'fa-camera',
    'Ficha_Preinscripcion' => 'fa-clipboard-list'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Ingreso · Tecnológico Superior de Zongolica</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ===== VARIABLES INSTITUCIONALES ITSZ ===== */
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --rojo: #860A0F;
            --dorado: #D99A32;
            --dorado-intenso: #F0A51A;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --texto-oscuro: #333333;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
            
            /* Colores de estado */
            --verde-exito: #27ae60;
            --rojo-error: #e74c3c;
            --naranja: #f39c12;
            --azul-claro: #2a5a9a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        /* ===== HEADER INSTITUCIONAL ===== */
        .header-itsz {
            background: var(--blanco);
            padding: 15px 0;
            box-shadow: var(--shadow);
            border-bottom: 4px solid var(--dorado);
            margin-bottom: 30px;
        }

        .header-itsz .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .header-itsz .logo-container {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-itsz .logo-container img {
            height: 50px;
            width: auto;
        }

        .header-itsz .logo-container .titulo {
            color: var(--azul);
            font-size: 14px;
            line-height: 1.3;
        }

        .header-itsz .logo-container .titulo strong {
            font-size: 18px;
            display: block;
            color: var(--guinda);
        }

        .header-itsz nav {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-itsz nav a {
            color: var(--azul);
            font-size: 14px;
            font-weight: 500;
            padding: 8px 18px;
            border-radius: var(--radius);
            transition: all 0.3s;
            text-decoration: none;
        }

        .header-itsz nav a:hover {
            background: var(--gris);
            transform: translateY(-2px);
        }

        .header-itsz nav .btn-logout {
            background: var(--rojo);
            color: var(--blanco);
            padding: 8px 20px;
            border-radius: var(--radius);
            font-weight: 600;
        }

        .header-itsz nav .btn-logout:hover {
            background: #6b080c;
            color: var(--blanco);
        }

        /* ===== TARJETA PRINCIPAL ===== */
        .card-principal {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        /* ===== HEADER DE LA PÁGINA ===== */
        .page-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 28px 32px;
            border-bottom: 4px solid var(--dorado);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header .titulo-section h1 {
            color: var(--blanco);
            font-size: 26px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .page-header .titulo-section h1 i {
            color: var(--dorado);
        }

        .page-header .titulo-section p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 14px;
            margin-top: 4px;
            font-weight: 400;
        }

        .page-header .usuario-badge {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 6px 18px 6px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(5px);
        }

        .page-header .usuario-badge .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--dorado);
            color: var(--azul);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .page-header .usuario-badge .info {
            color: var(--blanco);
            font-size: 13px;
        }

        .page-header .usuario-badge .info .nombre {
            font-weight: 700;
        }

        .page-header .usuario-badge .info .rol {
            font-size: 10px;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== CONTENIDO ===== */
        .contenido {
            padding: 32px;
        }

        /* ===== MENSAJES ===== */
        .mensaje {
            padding: 14px 20px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 5px solid transparent;
            font-size: 14px;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--verde-exito);
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--rojo-error);
        }

        .mensaje i {
            font-size: 18px;
        }

        /* ===== PROGRESO ===== */
        .progreso-container {
            background: var(--gris);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 28px;
            border: 1px solid var(--gris-oscuro);
        }

        .progreso-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }

        .progreso-header .label {
            font-weight: 700;
            color: var(--azul);
            font-size: 14px;
        }

        .progreso-header .label i {
            color: var(--dorado);
            margin-right: 8px;
        }

        .progreso-header .contador {
            font-weight: 600;
            color: var(--texto-oscuro);
            font-size: 13px;
        }

        .progreso-header .contador strong {
            color: var(--azul);
        }

        .progreso-bar {
            background: var(--gris-oscuro);
            border-radius: 20px;
            height: 22px;
            overflow: hidden;
            position: relative;
        }

        .progreso-bar .fill {
            background: linear-gradient(90deg, var(--dorado), var(--azul));
            height: 100%;
            border-radius: 20px;
            transition: width 0.6s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--blanco);
            font-size: 11px;
            font-weight: 700;
            min-width: 40px;
        }

        .progreso-mensaje {
            margin-top: 10px;
            font-size: 13px;
            font-weight: 500;
        }

        .progreso-mensaje.completo {
            color: var(--verde-exito);
        }

        .progreso-mensaje.faltante {
            color: var(--naranja);
        }

        .progreso-mensaje i {
            margin-right: 6px;
        }

        /* ===== LISTA DE DOCUMENTOS ===== */
        .documentos-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 28px;
        }

        .documento-item {
            background: var(--blanco);
            border-radius: var(--radius);
            padding: 14px 18px;
            border: 2px solid var(--gris-oscuro);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.25s ease;
        }

        .documento-item:hover {
            border-color: var(--azul);
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .documento-item .doc-info {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
        }

        .documento-item .doc-info .doc-icono {
            font-size: 1.3rem;
            color: var(--azul);
            width: 32px;
            text-align: center;
        }

        .documento-item .doc-info .doc-nombre {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--texto-oscuro);
        }

        .documento-item .doc-info .doc-estado {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 3px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .doc-estado.pendiente {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .doc-estado.aprobado {
            background: #d4edda;
            color: #155724;
            border: 1px solid #b7ebc5;
        }
        .doc-estado.rechazado {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .doc-estado.revisado {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b8d4f0;
        }

        .documento-item .doc-acciones {
            display: flex;
            gap: 8px;
        }

        .btn-doc {
            padding: 6px 16px;
            border: none;
            border-radius: var(--radius);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-doc:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-doc.subir {
            background: var(--azul);
            color: var(--blanco);
        }

        .btn-doc.subir:hover {
            background: var(--guinda);
        }

        .btn-doc.ver {
            background: var(--verde-exito);
            color: var(--blanco);
        }

        .btn-doc.ver:hover {
            background: #219a52;
        }

        .btn-doc.eliminar {
            background: var(--rojo);
            color: var(--blanco);
        }

        .btn-doc.eliminar:hover {
            background: #6b080c;
        }

        /* ===== BOTÓN FINALIZAR ===== */
        .footer-acciones {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid var(--gris);
        }

        .btn-finalizar {
            padding: 14px 48px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(27, 57, 106, 0.3);
        }

        .btn-finalizar:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(27, 57, 106, 0.4);
        }

        .btn-finalizar:disabled {
            background: var(--gris-oscuro);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            color: var(--texto-oscuro);
        }

        .footer-acciones .texto-ayuda {
            margin-top: 10px;
            font-size: 0.85rem;
            color: var(--texto-oscuro);
        }

        .footer-acciones .texto-ayuda i {
            margin-right: 6px;
            color: var(--dorado);
        }

        /* ===== MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(27, 57, 106, 0.6);
            backdrop-filter: blur(4px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--blanco);
            padding: 32px;
            border-radius: var(--radius-lg);
            max-width: 460px;
            width: 90%;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
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
            font-size: 0.9rem;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .modal-content .input-group {
            margin-bottom: 18px;
        }

        .modal-content .input-group label {
            display: block;
            font-weight: 600;
            color: var(--azul);
            margin-bottom: 6px;
            font-size: 0.85rem;
        }

        .modal-content .input-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 0.9rem;
            background: var(--gris);
            transition: 0.2s;
            cursor: pointer;
        }

        .modal-content .input-group input[type="file"]:hover {
            border-color: var(--azul);
            background: #e8edf5;
        }

        .modal-acciones {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 6px;
        }

        .btn-cancelar-modal {
            background: var(--gris);
            color: var(--texto-oscuro);
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
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
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit-modal:hover {
            background: var(--guinda);
            transform: translateY(-2px);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .header-itsz .container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .header-itsz .logo-container {
                flex-direction: column;
            }

            .header-itsz nav {
                justify-content: center;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }

            .page-header .titulo-section h1 {
                font-size: 20px;
            }

            .page-header .usuario-badge {
                align-self: flex-start;
            }

            .contenido {
                padding: 20px;
            }

            .documento-item {
                flex-wrap: wrap;
                gap: 10px;
            }

            .documento-item .doc-acciones {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .page-header .titulo-section h1 {
                font-size: 17px;
            }

            .contenido {
                padding: 16px;
            }

            .documento-item {
                padding: 12px 14px;
            }

            .documento-item .doc-info .doc-nombre {
                font-size: 0.8rem;
            }

            .btn-finalizar {
                padding: 12px 28px;
                font-size: 0.9rem;
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                padding: 24px;
            }

            .modal-acciones {
                flex-direction: column;
            }

            .modal-acciones button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER INSTITUCIONAL ===== -->
    <header class="header-itsz">
        <div class="container">
            <div class="logo-container">
                <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
                <div class="titulo">
                    <strong>Tecnológico Superior de Zongolica</strong>
                    <small>Sistema Integral Académico</small>
                </div>
            </div>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="mensajes.php"><i class="fas fa-envelope"></i> Mensajes</a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </nav>
        </div>
    </header>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="card-principal">
        <!-- Header de la página -->
        <div class="page-header">
            <div class="titulo-section">
                <h1>
                    <i class="fas fa-file-upload"></i> Subida de Documentos
                </h1>
                <p>Completa tu expediente para formalizar tu inscripción</p>
            </div>
            <div class="usuario-badge">
                <div class="avatar"><?php echo substr($username, 0, 1); ?></div>
                <div class="info">
                    <div class="nombre"><?php echo htmlspecialchars($username); ?></div>
                    <div class="rol">Nuevo Ingreso</div>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div class="contenido">
            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $mensaje_tipo; ?>">
                    <i class="fas <?php echo $mensaje_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <!-- Barra de progreso -->
            <?php 
            $total_requeridos = count($documentos_requeridos);
            $subidos = $total_requeridos - count($documentos_faltantes);
            $porcentaje = round(($subidos / $total_requeridos) * 100);
            ?>
            <div class="progreso-container">
                <div class="progreso-header">
                    <span class="label"><i class="fas fa-chart-simple"></i> Progreso de documentos</span>
                    <span class="contador"><strong><?php echo $subidos; ?></strong> de <strong><?php echo $total_requeridos; ?></strong> documentos subidos</span>
                </div>
                <div class="progreso-bar">
                    <div class="fill" style="width: <?php echo $porcentaje; ?>%;">
                        <?php if ($porcentaje > 15): ?>
                            <?php echo $porcentaje; ?>%
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($todos_completos): ?>
                    <div class="progreso-mensaje completo">
                        <i class="fas fa-check-circle"></i> ¡Todos los documentos están completos!
                    </div>
                <?php else: ?>
                    <div class="progreso-mensaje faltante">
                        <i class="fas fa-exclamation-triangle"></i>
                        Faltan <?php echo count($documentos_faltantes); ?> documento(s) por subir
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lista de documentos -->
            <div class="documentos-list">
                <?php foreach ($documentos_requeridos as $tipo):
                    $doc = array_filter($documentos, function($d) use ($tipo) {
                        return $d['tipo_documento'] === $tipo;
                    });
                    $doc = array_shift($doc);
                    $existe = $doc ? true : false;
                    $estado = $existe ? $doc['estado'] : 'Pendiente';
                    $clase_estado = strtolower($estado);
                ?>
                    <div class="documento-item">
                        <div class="doc-info">
                            <span class="doc-icono"><i class="fas <?php echo $iconos_documentos[$tipo]; ?>"></i></span>
                            <span class="doc-nombre"><?php echo $nombres_documentos[$tipo]; ?></span>
                            <span class="doc-estado <?php echo $clase_estado; ?>">
                                <?php echo $estado; ?>
                            </span>
                        </div>
                        <div class="doc-acciones">
                            <?php if ($existe): ?>
                                <a href="<?php echo $doc['ruta_archivo']; ?>" target="_blank" class="btn-doc ver">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <button onclick="eliminarDocumento(<?php echo $doc['id']; ?>)" class="btn-doc eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php else: ?>
                                <button onclick="abrirModal('<?php echo $tipo; ?>')" class="btn-doc subir">
                                    <i class="fas fa-upload"></i> Subir
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Botón finalizar -->
            <div class="footer-acciones">
                <?php if ($todos_completos): ?>
                    <form action="nuevo_ingreso_finalizar.php" method="POST">
                        <button type="submit" class="btn-finalizar">
                            <i class="fas fa-check-circle"></i> Finalizar Registro
                        </button>
                    </form>
                    <p class="texto-ayuda">
                        <i class="fas fa-info-circle"></i>
                        Tus documentos serán revisados por el administrador. Una vez aprobados, podrás acceder al sistema.
                    </p>
                <?php else: ?>
                    <button class="btn-finalizar" disabled>
                        <i class="fas fa-hourglass-half"></i> Completa todos los documentos
                    </button>
                    <p class="texto-ayuda">
                        <i class="fas fa-exclamation-triangle" style="color: var(--naranja);"></i>
                        Sube todos los documentos requeridos para continuar con tu registro.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para subir documento -->
    <div class="modal-overlay" id="modalSubir">
        <div class="modal-content">
            <h3><i class="fas fa-upload" style="color: var(--azul); margin-right: 10px;"></i> Subir Documento</h3>
            <p class="modal-sub">Selecciona el archivo que deseas subir para completar tu expediente.</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tipo_documento" id="tipoDocumento">
                <div class="input-group">
                    <label for="archivo"><i class="fas fa-file"></i> Archivo:</label>
                    <input type="file" id="archivo" name="archivo" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <div style="margin-top: 6px; font-size: 0.75rem; color: var(--texto-oscuro); opacity: 0.7;">
                        <i class="fas fa-info-circle"></i> Formatos permitidos: PDF, JPG, PNG, DOC (máx. 10MB)
                    </div>
                </div>
                <div class="modal-acciones">
                    <button type="button" class="btn-cancelar-modal" onclick="cerrarModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="subir_documento" class="btn-submit-modal">
                        <i class="fas fa-upload"></i> Subir
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModal(tipo) {
            document.getElementById('tipoDocumento').value = tipo;
            document.getElementById('modalSubir').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function cerrarModal() {
            document.getElementById('modalSubir').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function eliminarDocumento(id) {
            if (confirm('¿Estás seguro de eliminar este documento? Esta acción no se puede deshacer.')) {
                window.location.href = 'nuevo_ingreso_eliminar_documento.php?id=' + id;
            }
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalSubir').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });
    </script>

</body>
</html>