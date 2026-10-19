<?php
// mensajes_leer.php - Chat estilo WhatsApp con envío de archivos/imágenes por AJAX
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: mensajes.php");
    exit();
}

$mensaje_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }
$conn->set_charset("utf8mb4");

// ============================================================
// PROCESAR ELIMINAR MENSAJE INDIVIDUAL
// ============================================================
if (isset($_GET['eliminar_mensaje']) && is_numeric($_GET['eliminar_mensaje'])) {
    $id_eliminar = (int)$_GET['eliminar_mensaje'];
    
    // Obtener ruta del archivo para borrarlo del servidor
    $stmt_arch = $conn->prepare("SELECT archivo_ruta FROM mensajes WHERE id = ? AND (remitente_id = ? OR destinatario_id = ?)");
    $stmt_arch->bind_param("iii", $id_eliminar, $user_id, $user_id);
    $stmt_arch->execute();
    $arch = $stmt_arch->get_result()->fetch_assoc();
    $stmt_arch->close();
    
    if ($arch && !empty($arch['archivo_ruta']) && file_exists($arch['archivo_ruta'])) {
        unlink($arch['archivo_ruta']);
    }
    
    $stmt = $conn->prepare("DELETE FROM mensajes WHERE id = ? AND (remitente_id = ? OR destinatario_id = ?)");
    $stmt->bind_param("iii", $id_eliminar, $user_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: mensajes.php?success=Mensaje eliminado");
    } else {
        header("Location: mensajes.php?error=Error al eliminar");
    }
    $stmt->close();
    $conn->close();
    exit();
}

// ============================================================
// PROCESAR VACIAR CHAT COMPLETO
// ============================================================
if (isset($_GET['vaciar_chat']) && is_numeric($_GET['vaciar_chat'])) {
    $otro_id = (int)$_GET['vaciar_chat'];
    
    // Borrar archivos del servidor antes de borrar los mensajes
    $stmt_arch = $conn->prepare("SELECT archivo_ruta FROM mensajes 
                                WHERE (remitente_id = ? AND destinatario_id = ?)
                                   OR (remitente_id = ? AND destinatario_id = ?)");
    $stmt_arch->bind_param("iiii", $user_id, $otro_id, $otro_id, $user_id);
    $stmt_arch->execute();
    $archivos = $stmt_arch->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_arch->close();
    
    foreach ($archivos as $a) {
        if (!empty($a['archivo_ruta']) && file_exists($a['archivo_ruta'])) {
            unlink($a['archivo_ruta']);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM mensajes 
                            WHERE (remitente_id = ? AND destinatario_id = ?)
                               OR (remitente_id = ? AND destinatario_id = ?)");
    $stmt->bind_param("iiii", $user_id, $otro_id, $otro_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: mensajes.php?success=Chat vaciado correctamente");
    } else {
        header("Location: mensajes.php?error=Error al vaciar el chat");
    }
    $stmt->close();
    $conn->close();
    exit();
}

// Obtener el mensaje principal
$stmt = $conn->prepare("SELECT m.*, u1.Nombre as remitente, u2.Nombre as destinatario,
                        u1.Id as remitente_id, u2.Id as destinatario_id
                        FROM mensajes m 
                        JOIN usuarios u1 ON m.remitente_id = u1.Id 
                        JOIN usuarios u2 ON m.destinatario_id = u2.Id 
                        WHERE m.id = ? AND (m.remitente_id = ? OR m.destinatario_id = ?)");
$stmt->bind_param("iii", $mensaje_id, $user_id, $user_id);
$stmt->execute();
$mensaje = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mensaje) {
    header("Location: mensajes.php");
    exit();
}

// Determinar el otro usuario
$otro_usuario_id = ($mensaje['remitente_id'] == $user_id) ? $mensaje['destinatario_id'] : $mensaje['remitente_id'];
$otro_usuario_nombre = ($mensaje['remitente_id'] == $user_id) ? $mensaje['destinatario'] : $mensaje['remitente'];

// Marcar como leídos
$stmt = $conn->prepare("UPDATE mensajes SET leido = 1 WHERE destinatario_id = ? AND remitente_id = ?");
$stmt->bind_param("ii", $user_id, $otro_usuario_id);
$stmt->execute();
$stmt->close();

// Obtener toda la conversación
$sql_conv = "SELECT m.*, u1.Nombre as remitente_nombre, u2.Nombre as destinatario_nombre,
             u1.foto_perfil as remitente_foto, u2.foto_perfil as destinatario_foto
             FROM mensajes m 
             JOIN usuarios u1 ON m.remitente_id = u1.Id 
             JOIN usuarios u2 ON m.destinatario_id = u2.Id 
             WHERE (m.remitente_id = ? AND m.destinatario_id = ?)
                OR (m.remitente_id = ? AND m.destinatario_id = ?)
             ORDER BY m.fecha_envio ASC, m.id ASC";
$stmt_conv = $conn->prepare($sql_conv);
$stmt_conv->bind_param("iiii", $user_id, $otro_usuario_id, $otro_usuario_id, $user_id);
$stmt_conv->execute();
$conversacion = $stmt_conv->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_conv->close();

// Foto del otro usuario
$stmt_foto = $conn->prepare("SELECT foto_perfil, Rol FROM usuarios WHERE Id = ?");
$stmt_foto->bind_param("i", $otro_usuario_id);
$stmt_foto->execute();
$otro_usuario = $stmt_foto->get_result()->fetch_assoc();
$foto_otro = $otro_usuario['foto_perfil'] ?? 'uploads/default_usuario.png';
$rol_otro = $otro_usuario['Rol'];
$stmt_foto->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat con <?php echo htmlspecialchars($otro_usuario_nombre); ?> · ITSZ</title>
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
            --verde-chat: #DCF8C6;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--gris);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .header-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 10px 0;
            box-shadow: var(--shadow);
            border-bottom: 3px solid var(--dorado);
            flex-shrink: 0;
            z-index: 1000;
        }

        .header-itsz .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .header-itsz .logo { display: flex; align-items: center; gap: 12px; }
        .header-itsz .logo img { height: 42px; width: auto; }
        .header-itsz .logo h1 { color: var(--blanco); font-weight: 800; font-size: 1rem; line-height: 1.2; }
        .header-itsz .logo h1 small { display: block; font-weight: 400; font-size: 0.6rem; color: rgba(255,255,255,0.7); }

        .header-itsz nav { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .header-itsz nav a {
            color: var(--blanco);
            font-size: 0.8rem;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: var(--radius);
            transition: all 0.3s;
            text-decoration: none;
        }
        .header-itsz nav a:hover { background: rgba(255, 255, 255, 0.12); }
        .header-itsz nav .btn-salir { background: var(--rojo); padding: 6px 16px; border-radius: var(--radius); font-weight: 600; }

        .chat-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 10px;
            overflow: hidden;
        }

        .chat-container {
            max-width: 900px;
            width: 100%;
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 3px solid var(--dorado);
            flex-shrink: 0;
        }

        .chat-header .btn-back { color: var(--blanco); font-size: 18px; text-decoration: none; padding: 6px 10px; border-radius: 50%; transition: 0.3s; }
        .chat-header .btn-back:hover { background: rgba(255,255,255,0.15); }

        .chat-header .avatar-chat {
            width: 42px; height: 42px; border-radius: 50%;
            object-fit: cover; border: 2px solid var(--dorado); background: var(--blanco);
        }

        .chat-header .info-contacto { flex: 1; min-width: 0; }

        .chat-header .info-contacto .nombre {
            color: var(--blanco);
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-header .info-contacto .nombre .rol-badge {
            background: rgba(255,255,255,0.2);
            color: var(--blanco);
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .chat-header .info-contacto .estado { color: rgba(255,255,255,0.75); font-size: 11px; }
        .chat-header .info-contacto .estado i { color: var(--verde-exito); font-size: 7px; margin-right: 4px; }

        .menu-opciones { position: relative; }

        .btn-menu {
            color: var(--blanco);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 8px 12px;
            border-radius: 50%;
            transition: 0.3s;
        }
        .btn-menu:hover { background: rgba(255,255,255,0.15); }

        .menu-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--blanco);
            border-radius: var(--radius);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            min-width: 220px;
            z-index: 100;
            overflow: hidden;
            margin-top: 5px;
        }
        .menu-dropdown.active { display: block; }

        .menu-dropdown a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: var(--texto-oscuro);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
            border-bottom: 1px solid var(--gris);
        }
        .menu-dropdown a:last-child { border-bottom: none; }
        .menu-dropdown a:hover { background: var(--gris); }
        .menu-dropdown a.danger { color: var(--rojo); }
        .menu-dropdown a.danger:hover { background: #fde8e8; }
        .menu-dropdown a i { width: 20px; font-size: 16px; }

        .chat-mensajes {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #ECE5DD;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.4) 1px, transparent 1px),
                radial-gradient(circle at 80% 70%, rgba(255,255,255,0.4) 1px, transparent 1px);
            background-size: 40px 40px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .fecha-separador {
            text-align: center;
            margin: 12px 0 8px;
            display: flex;
            justify-content: center;
        }

        .fecha-separador span {
            background: rgba(27, 57, 106, 0.75);
            color: var(--blanco);
            padding: 4px 14px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            backdrop-filter: blur(5px);
        }

        .mensaje-burbuja {
            max-width: 70%;
            padding: 8px 12px;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 2px;
            animation: aparecer 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        @keyframes aparecer {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .mensaje-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 2px;
        }

        .mensaje-wrapper.recibido { justify-content: flex-start; }
        .mensaje-wrapper.enviado { justify-content: flex-end; }

        .mensaje-burbuja.recibido {
            background: var(--blanco);
            border-top-left-radius: 2px;
            color: var(--texto-oscuro);
            order: 2;
        }

        .mensaje-burbuja.enviado {
            background: var(--verde-chat);
            border-top-right-radius: 2px;
            color: var(--texto-oscuro);
            order: 1;
        }

        .mensaje-burbuja.recibido.primero::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 0;
            height: 0;
            border-top: 8px solid var(--blanco);
            border-left: 8px solid transparent;
        }

        .mensaje-burbuja.enviado.primero::before {
            content: '';
            position: absolute;
            right: -8px;
            top: 0;
            width: 0;
            height: 0;
            border-top: 8px solid var(--verde-chat);
            border-right: 8px solid transparent;
        }

        .btn-eliminar-msg {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--gris);
            color: var(--rojo);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            opacity: 0;
            transition: 0.2s;
            text-decoration: none;
            flex-shrink: 0;
        }

        .mensaje-wrapper:hover .btn-eliminar-msg { opacity: 1; }

        .btn-eliminar-msg:hover {
            background: var(--rojo);
            color: var(--blanco);
            transform: scale(1.1);
        }

        .mensaje-burbuja .asunto-burbuja {
            font-size: 11px;
            font-weight: 700;
            color: var(--azul);
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px dashed rgba(27, 57, 106, 0.2);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .mensaje-burbuja .texto-burbuja {
            font-size: 14px;
            color: var(--texto-oscuro);
            white-space: pre-wrap;
        }

        .mensaje-burbuja .hora-burbuja {
            font-size: 10px;
            color: rgba(0,0,0,0.4);
            text-align: right;
            margin-top: 3px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
        }

        .mensaje-burbuja .hora-burbuja i { color: #4FC3F7; font-size: 11px; }

        .estado-vacio {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            margin: auto;
        }
        .estado-vacio i { font-size: 60px; color: var(--dorado); margin-bottom: 15px; display: block; opacity: 0.4; }
        .estado-vacio p { font-size: 14px; margin: 5px 0; }

        .chat-envio {
            background: var(--gris);
            padding: 10px 16px;
            border-top: 1px solid var(--gris-oscuro);
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-shrink: 0;
        }

        .chat-envio .fila-input {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .chat-envio .input-mensaje {
            flex: 1;
            padding: 11px 16px;
            border: 1px solid var(--gris-oscuro);
            border-radius: 22px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            background: var(--blanco);
            outline: none;
            transition: 0.3s;
            resize: none;
            max-height: 100px;
            min-height: 42px;
            line-height: 1.4;
        }

        .chat-envio .input-mensaje:focus {
            border-color: var(--azul);
            box-shadow: 0 0 0 2px rgba(27, 57, 106, 0.1);
        }

        .chat-envio .btn-enviar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: 0.3s;
            box-shadow: 0 3px 10px rgba(27, 57, 106, 0.3);
            flex-shrink: 0;
        }

        .chat-envio .btn-enviar:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(27, 57, 106, 0.4); }
        .chat-envio .btn-enviar:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* ===== BOTÓN ADJUNTAR ===== */
        .chat-envio .btn-adjuntar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--gris);
            color: var(--azul);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: 0.3s;
            flex-shrink: 0;
        }

        .chat-envio .btn-adjuntar:hover {
            background: var(--azul);
            color: var(--blanco);
            transform: scale(1.05);
        }

        .chat-envio .btn-adjuntar.activo {
            background: var(--dorado);
            color: var(--blanco);
        }

        .chat-envio input[type="file"] {
            display: none;
        }

        /* ===== PREVIEW DE ARCHIVO ANTES DE ENVIAR ===== */
        .preview-archivo {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: #FFF8E1;
            border-left: 4px solid var(--dorado);
            border-radius: var(--radius);
            font-size: 13px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .preview-archivo.activo {
            display: flex;
        }

        .preview-archivo .icono-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--blanco);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--azul);
            flex-shrink: 0;
            overflow: hidden;
        }

        .preview-archivo .icono-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-archivo .info-preview {
            flex: 1;
            min-width: 0;
        }

        .preview-archivo .nombre-preview {
            font-weight: 700;
            color: var(--azul);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 13px;
        }

        .preview-archivo .tamano-preview {
            font-size: 11px;
            color: #888;
        }

        .preview-archivo .btn-quitar-preview {
            background: transparent;
            border: none;
            color: var(--rojo);
            cursor: pointer;
            font-size: 16px;
            padding: 6px;
            border-radius: 50%;
            transition: 0.2s;
        }

        .preview-archivo .btn-quitar-preview:hover {
            background: #fde8e8;
        }

        /* ===== IMÁGENES EN BURBUJAS ===== */
        .imagen-burbuja {
            max-width: 100%;
            border-radius: 8px;
            cursor: pointer;
            display: block;
            margin: 2px 0;
            transition: 0.2s;
            max-height: 300px;
            object-fit: cover;
        }

        .imagen-burbuja:hover {
            opacity: 0.9;
            transform: scale(1.01);
        }

        /* ===== DOCUMENTOS EN BURBUJAS ===== */
        .archivo-burbuja {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(255,255,255,0.6);
            border-radius: 8px;
            text-decoration: none;
            color: var(--texto-oscuro);
            margin: 2px 0;
            transition: 0.2s;
            border: 1px solid rgba(0,0,0,0.05);
            min-width: 220px;
        }

        .archivo-burbuja:hover {
            background: rgba(255,255,255,0.95);
        }

        .archivo-burbuja .icono-archivo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--azul);
            color: var(--blanco);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .archivo-burbuja .icono-archivo.pdf { background: #DC3545; }
        .archivo-burbuja .icono-archivo.doc { background: #2B579A; }
        .archivo-burbuja .icono-archivo.xls { background: #217346; }
        .archivo-burbuja .icono-archivo.zip { background: #F7B32B; }
        .archivo-burbuja .icono-archivo.txt { background: #6C757D; }

        .archivo-burbuja .info-archivo {
            flex: 1;
            min-width: 0;
        }

        .archivo-burbuja .nombre-archivo {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .archivo-burbuja .tamano-archivo {
            font-size: 11px;
            opacity: 0.6;
        }

        /* ===== LIGHTBOX PARA IMÁGENES ===== */
        .lightbox {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 5000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            cursor: zoom-out;
        }

        .lightbox.activo { display: flex; }

        .lightbox img {
            max-width: 95%;
            max-height: 95%;
            object-fit: contain;
            border-radius: 8px;
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        .lightbox .cerrar-lightbox {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--rojo);
            color: var(--blanco);
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }

        .lightbox .cerrar-lightbox:hover {
            transform: scale(1.1) rotate(90deg);
        }

        .chat-mensajes::-webkit-scrollbar { width: 6px; }
        .chat-mensajes::-webkit-scrollbar-track { background: transparent; }
        .chat-mensajes::-webkit-scrollbar-thumb { background: rgba(27, 57, 106, 0.25); border-radius: 10px; }

        .modal-confirm {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(27, 57, 106, 0.7);
            backdrop-filter: blur(5px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-confirm.active { display: flex; }

        .modal-confirm-content {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            padding: 30px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-confirm-content .icono-warning {
            width: 70px; height: 70px;
            border-radius: 50%;
            background: #FDE8E8;
            color: var(--rojo);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }

        .modal-confirm-content h3 { color: var(--azul); font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .modal-confirm-content p { color: var(--texto-oscuro); font-size: 14px; opacity: 0.8; margin-bottom: 24px; line-height: 1.5; }

        .modal-actions { display: flex; gap: 10px; }

        .modal-actions .btn-cancelar-modal {
            flex: 1; padding: 12px;
            background: var(--gris);
            color: var(--texto-oscuro);
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }
        .modal-actions .btn-cancelar-modal:hover { background: var(--gris-oscuro); }

        .modal-actions .btn-confirmar-modal {
            flex: 1; padding: 12px;
            background: var(--rojo);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: 0.3s;
        }
        .modal-actions .btn-confirmar-modal:hover { background: #6b080c; }

        .error-red {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 14px;
            border-radius: var(--radius);
            border-left: 4px solid var(--rojo);
            margin-bottom: 10px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            body { padding: 0; }
            .header-itsz { padding: 8px 0; }
            .header-itsz .logo h1 { font-size: 0.85rem; }
            .chat-wrapper { padding: 0; }
            .chat-container { border-radius: 0; max-width: 100%; }
            .chat-header { padding: 10px 12px; }
            .mensaje-burbuja { max-width: 85%; font-size: 13px; }
            .chat-mensajes { padding: 12px 10px; }
            .btn-eliminar-msg { opacity: 1; width: 24px; height: 24px; font-size: 10px; }
            .archivo-burbuja { min-width: 180px; }
        }
    </style>
</head>
<body>

    <header class="header-itsz">
        <div class="container">
            <div class="logo">
                <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
                <div>
                    <h1>
                        Tecnológico Superior de Zongolica
                        <small>Sistema Integral Académico</small>
                    </h1>
                </div>
            </div>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="mensajes.php"><i class="fas fa-envelope"></i> Mensajes</a>
                <a href="logout.php" class="btn-salir"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </nav>
        </div>
    </header>

    <div class="chat-wrapper">
        <div class="chat-container">

            <div class="chat-header">
                <a href="mensajes.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <img src="<?php echo htmlspecialchars($foto_otro); ?>" 
                     alt="Avatar" 
                     class="avatar-chat"
                     onerror="this.src='uploads/default_usuario.png'">
                <div class="info-contacto">
                    <div class="nombre">
                        <?php echo htmlspecialchars($otro_usuario_nombre); ?>
                        <span class="rol-badge"><?php echo htmlspecialchars($rol_otro); ?></span>
                    </div>
                    <div class="estado">
                        <i class="fas fa-circle"></i> En línea
                    </div>
                </div>

                <div class="menu-opciones">
                    <button class="btn-menu" onclick="toggleMenu()">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="menu-dropdown" id="menuDropdown">
                        <a href="mensajes.php">
                            <i class="fas fa-inbox"></i> Ir a Mensajes
                        </a>
                        <a href="#" onclick="confirmarVaciar(<?php echo $otro_usuario_id; ?>); return false;" class="danger">
                            <i class="fas fa-trash-alt"></i> Vaciar este chat
                        </a>
                    </div>
                </div>
            </div>

            <div class="chat-mensajes" id="chatMensajes">
                <?php if (count($conversacion) > 0): 
                    $fecha_actual = '';
                    $id_anterior = 0;
                    
                    foreach ($conversacion as $msg):
                        $es_mio = ($msg['remitente_id'] == $user_id);
                        $fecha_msg = date('d/m/Y', strtotime($msg['fecha_envio']));
                        
                        if ($fecha_actual != $fecha_msg) {
                            $fecha_actual = $fecha_msg;
                            $hoy = date('d/m/Y');
                            $ayer = date('d/m/Y', strtotime('-1 day'));
                            
                            if ($fecha_msg == $hoy) $etiqueta_fecha = 'Hoy';
                            elseif ($fecha_msg == $ayer) $etiqueta_fecha = 'Ayer';
                            else {
                                $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                $etiqueta_fecha = $dias[date('w', strtotime($fecha_msg))] . ' ' . 
                                                  date('d', strtotime($fecha_msg)) . ' de ' . 
                                                  $meses[(int)date('m', strtotime($fecha_msg))];
                            }
                            
                            echo '<div class="fecha-separador"><span>' . $etiqueta_fecha . '</span></div>';
                            $id_anterior = 0;
                        }
                        
                        $es_primero = ($msg['remitente_id'] != $id_anterior);
                        $id_anterior = $msg['remitente_id'];

                        // ===== INFO DEL ARCHIVO ADJUNTO =====
                        $tiene_archivo = !empty($msg['archivo_ruta']);
                        $es_imagen = $tiene_archivo && strpos($msg['archivo_tipo'] ?? '', 'image/') === 0;
                        
                        // Ícono según extensión
                        $extension = strtolower(pathinfo($msg['archivo_nombre'] ?? '', PATHINFO_EXTENSION));
                        $clase_icono = 'default';
                        $icono_fa = 'fa-file';
                        
                        if ($extension === 'pdf') { $clase_icono = 'pdf'; $icono_fa = 'fa-file-pdf'; }
                        elseif (in_array($extension, ['doc', 'docx'])) { $clase_icono = 'doc'; $icono_fa = 'fa-file-word'; }
                        elseif (in_array($extension, ['xls', 'xlsx'])) { $clase_icono = 'xls'; $icono_fa = 'fa-file-excel'; }
                        elseif (in_array($extension, ['ppt', 'pptx'])) { $clase_icono = 'doc'; $icono_fa = 'fa-file-powerpoint'; }
                        elseif (in_array($extension, ['zip', 'rar', '7z'])) { $clase_icono = 'zip'; $icono_fa = 'fa-file-archive'; }
                        elseif (in_array($extension, ['txt', 'csv'])) { $clase_icono = 'txt'; $icono_fa = 'fa-file-alt'; }
                ?>
                    <div class="mensaje-wrapper <?php echo $es_mio ? 'enviado' : 'recibido'; ?>">
                        
                        <?php if (!$es_mio): ?>
                            <a href="#" 
                               class="btn-eliminar-msg" 
                               title="Eliminar mensaje"
                               onclick="confirmarEliminar(<?php echo $msg['id']; ?>); return false;">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                        
                        <div class="mensaje-burbuja <?php echo $es_mio ? 'enviado' : 'recibido'; ?> <?php echo $es_primero ? 'primero' : ''; ?>">
                            <?php if (!$es_mio && $es_primero): ?>
                                <div class="asunto-burbuja">
                                    <i class="fas fa-user-circle"></i> 
                                    <?php echo htmlspecialchars($msg['remitente_nombre']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($msg['asunto']) && $msg['id'] == $mensaje_id): ?>
                                <div class="asunto-burbuja">
                                    <i class="fas fa-tag"></i> 
                                    <?php echo htmlspecialchars($msg['asunto']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($tiene_archivo && $es_imagen): ?>
                                <!-- IMAGEN ADJUNTA -->
                                <img src="<?php echo htmlspecialchars($msg['archivo_ruta']); ?>" 
                                     alt="<?php echo htmlspecialchars($msg['archivo_nombre']); ?>"
                                     class="imagen-burbuja"
                                     onclick="abrirLightbox('<?php echo htmlspecialchars($msg['archivo_ruta']); ?>')">
                            <?php elseif ($tiene_archivo): ?>
                                <!-- DOCUMENTO ADJUNTO -->
                                <a href="<?php echo htmlspecialchars($msg['archivo_ruta']); ?>" 
                                   class="archivo-burbuja" 
                                   download="<?php echo htmlspecialchars($msg['archivo_nombre']); ?>"
                                   target="_blank">
                                    <div class="icono-archivo <?php echo $clase_icono; ?>">
                                        <i class="fas <?php echo $icono_fa; ?>"></i>
                                    </div>
                                    <div class="info-archivo">
                                        <div class="nombre-archivo"><?php echo htmlspecialchars($msg['archivo_nombre']); ?></div>
                                        <div class="tamano-archivo">
                                            <?php 
                                            $tam = $msg['archivo_tamano'] ?? 0;
                                            if ($tam > 1048576) echo round($tam / 1048576, 2) . ' MB';
                                            elseif ($tam > 1024) echo round($tam / 1024, 1) . ' KB';
                                            else echo $tam . ' B';
                                            ?>
                                        </div>
                                    </div>
                                    <i class="fas fa-download" style="opacity: 0.5;"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($msg['mensaje'])): ?>
                                <div class="texto-burbuja"><?php echo nl2br(htmlspecialchars($msg['mensaje'])); ?></div>
                            <?php endif; ?>
                            
                            <div class="hora-burbuja">
                                <?php echo date('H:i', strtotime($msg['fecha_envio'])); ?>
                                <?php if ($es_mio): ?>
                                    <i class="fas fa-check-double"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($es_mio): ?>
                            <a href="#" 
                               class="btn-eliminar-msg" 
                               title="Eliminar mensaje"
                               onclick="confirmarEliminar(<?php echo $msg['id']; ?>); return false;">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                        
                    </div>
                <?php endforeach; ?>
                <?php else: ?>
                    <div class="estado-vacio">
                        <i class="fas fa-comments"></i>
                        <p>No hay mensajes en esta conversación</p>
                        <p style="font-size: 12px;">¡Sé el primero en escribir!</p>
                    </div>
                <?php endif; ?>
            </div>

            <form class="chat-envio" method="POST" action="procesar_mensaje.php" id="formEnvio" enctype="multipart/form-data">
                <input type="hidden" name="destinatario" value="<?php echo $otro_usuario_id; ?>">
                <input type="hidden" name="chat_id" value="<?php echo $mensaje_id; ?>">
                <input type="hidden" name="asunto" value="Re: <?php echo htmlspecialchars($mensaje['asunto']); ?>">
                
                <!-- Preview del archivo adjunto -->
                <div class="preview-archivo" id="previewArchivo"></div>
                
                <div class="fila-input">
                    <!-- Botón adjuntar -->
                    <button type="button" class="btn-adjuntar" id="btnAdjuntar" title="Adjuntar archivo">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <input type="file" id="inputArchivo" name="archivo" 
                           accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z">
                    
                    <textarea 
                        name="mensaje" 
                        class="input-mensaje" 
                        placeholder="Escribe un mensaje..." 
                        id="inputMensaje"
                        rows="1"></textarea>
                    
                    <button type="submit" name="enviar_mensaje" class="btn-enviar">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ===== LIGHTBOX PARA IMÁGENES ===== -->
    <div class="lightbox" id="lightbox" onclick="cerrarLightbox()">
        <button class="cerrar-lightbox" onclick="cerrarLightbox()">
            <i class="fas fa-times"></i>
        </button>
        <img src="" alt="Vista previa" id="lightboxImg" onclick="event.stopPropagation()">
    </div>

    <div class="modal-confirm" id="modalEliminar">
        <div class="modal-confirm-content">
            <div class="icono-warning">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3>¿Eliminar mensaje?</h3>
            <p>Esta acción eliminará este mensaje permanentemente. No se puede deshacer.</p>
            <div class="modal-actions">
                <button class="btn-cancelar-modal" onclick="cerrarModal('modalEliminar')">Cancelar</button>
                <a href="#" class="btn-confirmar-modal" id="btnConfirmarEliminar">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </a>
            </div>
        </div>
    </div>

    <div class="modal-confirm" id="modalVaciar">
        <div class="modal-confirm-content">
            <div class="icono-warning">
                <i class="fas fa-broom"></i>
            </div>
            <h3>¿Vaciar este chat?</h3>
            <p>Se eliminarán <strong>TODOS los mensajes</strong> de esta conversación. Esta acción no se puede deshacer.</p>
            <div class="modal-actions">
                <button class="btn-cancelar-modal" onclick="cerrarModal('modalVaciar')">Cancelar</button>
                <a href="#" class="btn-confirmar-modal" id="btnConfirmarVaciar">
                    <i class="fas fa-broom"></i> Vaciar chat
                </a>
            </div>
        </div>
    </div>

    <script>
        // ============================================================
        // CONFIGURACIÓN INICIAL
        // ============================================================
        const chatMensajes = document.getElementById('chatMensajes');
        const inputMensaje = document.getElementById('inputMensaje');
        const formEnvio = document.getElementById('formEnvio');
        const btnEnviar = formEnvio.querySelector('.btn-enviar');
        const btnAdjuntar = document.getElementById('btnAdjuntar');
        const inputArchivo = document.getElementById('inputArchivo');
        const previewArchivo = document.getElementById('previewArchivo');
        
        const MI_ID = <?php echo $user_id; ?>;
        const OTRO_ID = <?php echo $otro_usuario_id; ?>;
        const MENSAJE_ID = <?php echo $mensaje_id; ?>;
        const NOMBRE_OTRO = '<?php echo addslashes($otro_usuario_nombre); ?>';
        
        function scrollAlFinal() {
            chatMensajes.scrollTop = chatMensajes.scrollHeight;
        }
        
        scrollAlFinal();

        inputMensaje.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // ============================================================
        // BOTÓN ADJUNTAR ARCHIVO
        // ============================================================
        btnAdjuntar.addEventListener('click', function() {
            inputArchivo.click();
        });

        inputArchivo.addEventListener('change', function() {
            const archivo = this.files[0];
            if (!archivo) return;
            
            const maxSize = 10 * 1024 * 1024; // 10 MB
            if (archivo.size > maxSize) {
                alert('El archivo excede los 10 MB permitidos.');
                this.value = '';
                return;
            }
            
            mostrarPreviewArchivo(archivo);
            btnAdjuntar.classList.add('activo');
        });

        function mostrarPreviewArchivo(archivo) {
            const esImagen = archivo.type.startsWith('image/');
            const tamano = formatearTamano(archivo.size);
            
            let iconoHTML = '';
            if (esImagen) {
                const url = URL.createObjectURL(archivo);
                iconoHTML = `<img src="${url}" alt="preview">`;
            } else {
                iconoHTML = '<i class="fas fa-file"></i>';
            }
            
            previewArchivo.innerHTML = `
                <div class="icono-preview">${iconoHTML}</div>
                <div class="info-preview">
                    <div class="nombre-preview">${escapeHtml(archivo.name)}</div>
                    <div class="tamano-preview">${tamano}</div>
                </div>
                <button type="button" class="btn-quitar-preview" onclick="quitarPreview()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            previewArchivo.classList.add('activo');
        }

        function quitarPreview() {
            inputArchivo.value = '';
            previewArchivo.classList.remove('activo');
            previewArchivo.innerHTML = '';
            btnAdjuntar.classList.remove('activo');
        }

        function formatearTamano(bytes) {
            if (bytes > 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes > 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return bytes + ' B';
        }

        // ============================================================
        // ENVIAR MENSAJE CON AJAX (con o sin archivo)
        // ============================================================
        formEnvio.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const mensajeTexto = inputMensaje.value.trim();
            const tieneArchivo = inputArchivo.files.length > 0;
            
            if (!mensajeTexto && !tieneArchivo) return;
            
            btnEnviar.disabled = true;
            btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            const formData = new FormData();
            formData.append('destinatario', OTRO_ID);
            formData.append('mensaje', mensajeTexto);
            formData.append('asunto', 'Re: Mensaje');
            
            if (tieneArchivo) {
                formData.append('archivo', inputArchivo.files[0]);
            }
            
            fetch('procesar_mensaje.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    agregarMensajeVisual(
                        mensajeTexto, 
                        data.hora, 
                        data.archivo_ruta, 
                        data.archivo_nombre, 
                        data.archivo_tipo, 
                        data.archivo_tamano
                    );
                    inputMensaje.value = '';
                    inputMensaje.style.height = 'auto';
                    quitarPreview();
                    inputMensaje.focus();
                    scrollAlFinal();
                } else {
                    mostrarErrorRed(data.error || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarErrorRed('No se pudo conectar con el servidor. Intenta de nuevo.');
            })
            .finally(() => {
                btnEnviar.disabled = false;
                btnEnviar.innerHTML = '<i class="fas fa-paper-plane"></i>';
            });
        });

        // ============================================================
        // MOSTRAR ERROR DE RED
        // ============================================================
        function mostrarErrorRed(mensaje) {
            const errorHTML = `
                <div class="error-red">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${mensaje}
                </div>`;
            const existente = chatMensajes.querySelector('.error-red');
            if (existente) existente.remove();
            chatMensajes.insertAdjacentHTML('beforeend', errorHTML);
            scrollAlFinal();
            setTimeout(() => {
                const err = chatMensajes.querySelector('.error-red');
                if (err) {
                    err.style.transition = 'opacity 0.5s';
                    err.style.opacity = '0';
                    setTimeout(() => err.remove(), 500);
                }
            }, 5000);
        }

        // ============================================================
        // AGREGAR MENSAJE VISUAL (con archivo)
        // ============================================================
        function agregarMensajeVisual(texto, hora, archivoRuta, archivoNombre, archivoTipo, archivoTamano) {
            const ultimoHijo = chatMensajes.lastElementChild;
            const tieneSeparadorHoy = ultimoHijo && 
                                     ultimoHijo.classList.contains('fecha-separador') &&
                                     ultimoHijo.textContent.includes('Hoy');
            
            if (!tieneSeparadorHoy) {
                const mensajesHoy = chatMensajes.querySelectorAll('.fecha-separador');
                const ultimoSep = mensajesHoy[mensajesHoy.length - 1];
                
                if (!ultimoSep || !ultimoSep.textContent.includes('Hoy')) {
                    const sep = document.createElement('div');
                    sep.className = 'fecha-separador';
                    sep.innerHTML = '<span>Hoy</span>';
                    chatMensajes.appendChild(sep);
                }
            }
            
            let contenidoHTML = '';
            
            // Imagen
            if (archivoRuta && archivoTipo && archivoTipo.startsWith('image/')) {
                contenidoHTML += `
                    <img src="${archivoRuta}" 
                         alt="${escapeHtml(archivoNombre)}"
                         class="imagen-burbuja"
                         onclick="abrirLightbox('${archivoRuta}')">`;
            } 
            // Documento
            else if (archivoRuta) {
                const info = obtenerIconoArchivo(archivoNombre);
                const tamanoFmt = formatearTamano(archivoTamano);
                contenidoHTML += `
                    <a href="${archivoRuta}" 
                       class="archivo-burbuja" 
                       download="${escapeHtml(archivoNombre)}"
                       target="_blank">
                        <div class="icono-archivo ${info.clase}">
                            <i class="fas ${info.icono}"></i>
                        </div>
                        <div class="info-archivo">
                            <div class="nombre-archivo">${escapeHtml(archivoNombre)}</div>
                            <div class="tamano-archivo">${tamanoFmt}</div>
                        </div>
                        <i class="fas fa-download" style="opacity: 0.5;"></i>
                    </a>`;
            }
            
            // Texto
            if (texto) {
                contenidoHTML += `<div class="texto-burbuja">${escapeHtml(texto)}</div>`;
            }
            
            const mensajeHTML = `
                <div class="mensaje-wrapper enviado">
                    <div class="mensaje-burbuja enviado primero">
                        ${contenidoHTML}
                        <div class="hora-burbuja">
                            ${hora}
                            <i class="fas fa-check-double"></i>
                        </div>
                    </div>
                    <a href="#" 
                       class="btn-eliminar-msg" 
                       title="Eliminar mensaje"
                       onclick="confirmarEliminarReciente(this); return false;">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            `;
            
            chatMensajes.insertAdjacentHTML('beforeend', mensajeHTML);
        }

        function obtenerIconoArchivo(nombre) {
            const ext = nombre.split('.').pop().toLowerCase();
            if (ext === 'pdf') return { clase: 'pdf', icono: 'fa-file-pdf' };
            if (['doc', 'docx'].includes(ext)) return { clase: 'doc', icono: 'fa-file-word' };
            if (['xls', 'xlsx'].includes(ext)) return { clase: 'xls', icono: 'fa-file-excel' };
            if (['ppt', 'pptx'].includes(ext)) return { clase: 'doc', icono: 'fa-file-powerpoint' };
            if (['zip', 'rar', '7z'].includes(ext)) return { clase: 'zip', icono: 'fa-file-archive' };
            if (['txt', 'csv'].includes(ext)) return { clase: 'txt', icono: 'fa-file-alt' };
            return { clase: 'default', icono: 'fa-file' };
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/\n/g, '<br>');
        }

        function confirmarEliminarReciente(elemento) {
            const wrapper = elemento.closest('.mensaje-wrapper');
            wrapper.style.transition = 'opacity 0.3s';
            wrapper.style.opacity = '0';
            setTimeout(() => wrapper.remove(), 300);
        }

        // ============================================================
        // LIGHTBOX PARA IMÁGENES
        // ============================================================
        function abrirLightbox(url) {
            document.getElementById('lightboxImg').src = url;
            document.getElementById('lightbox').classList.add('activo');
        }

        function cerrarLightbox() {
            document.getElementById('lightbox').classList.remove('activo');
        }

        // ============================================================
        // ENTER PARA ENVIAR, SHIFT+ENTER NUEVA LÍNEA
        // ============================================================
        inputMensaje.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                formEnvio.dispatchEvent(new Event('submit'));
            }
        });

        // ============================================================
        // MENÚ Y MODALES
        // ============================================================
        function toggleMenu() {
            document.getElementById('menuDropdown').classList.toggle('active');
        }

        document.addEventListener('click', function(e) {
            const menu = document.getElementById('menuDropdown');
            const btn = document.querySelector('.btn-menu');
            if (menu && !menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.remove('active');
            }
        });

        function confirmarEliminar(idMensaje) {
            const btn = document.getElementById('btnConfirmarEliminar');
            btn.href = 'mensajes_leer.php?id=' + MENSAJE_ID + '&eliminar_mensaje=' + idMensaje;
            document.getElementById('modalEliminar').classList.add('active');
        }

        function confirmarVaciar(idOtro) {
            const btn = document.getElementById('btnConfirmarVaciar');
            btn.href = 'mensajes_leer.php?id=' + MENSAJE_ID + '&vaciar_chat=' + idOtro;
            document.getElementById('modalVaciar').classList.add('active');
        }

        function cerrarModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarLightbox();
                document.querySelectorAll('.modal-confirm').forEach(m => m.classList.remove('active'));
            }
        });

        document.querySelectorAll('.modal-confirm').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        window.addEventListener('load', function() {
            inputMensaje.focus();
            scrollAlFinal();
        });
    </script>

</body>
</html>