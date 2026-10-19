<?php
// mensajes.php - Bandeja de mensajes estilo WhatsApp
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }
$conn->set_charset("utf8mb4");

// Marcar como leído
if (isset($_GET['leer']) && is_numeric($_GET['leer'])) {
    $stmt = $conn->prepare("UPDATE mensajes SET leido = 1 WHERE id = ? AND destinatario_id = ?");
    $stmt->bind_param("ii", $_GET['leer'], $user_id);
    $stmt->execute();
    $stmt->close();
}

// Eliminar mensaje
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $stmt = $conn->prepare("DELETE FROM mensajes WHERE id = ? AND (remitente_id = ? OR destinatario_id = ?)");
    $stmt->bind_param("iii", $_GET['eliminar'], $user_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: mensajes.php");
    exit();
}

// ============================================================
// OBTENER CONVERSACIONES (AGRUPADAS POR USUARIO)
// ============================================================
$sql_conversaciones = "
    SELECT 
        CASE 
            WHEN m.remitente_id = ? THEN m.destinatario_id 
            ELSE m.remitente_id 
        END as otro_usuario_id,
        MAX(m.id) as ultimo_mensaje_id
    FROM mensajes m
    WHERE m.remitente_id = ? OR m.destinatario_id = ?
    GROUP BY otro_usuario_id
    ORDER BY ultimo_mensaje_id DESC
";

$stmt = $conn->prepare($sql_conversaciones);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$conversaciones_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Para cada conversación, obtener los detalles
$conversaciones = [];
foreach ($conversaciones_raw as $conv) {
    $otro_id = (int)$conv['otro_usuario_id'];
    $ultimo_id = (int)$conv['ultimo_mensaje_id'];
    
    // Obtener el último mensaje y datos del otro usuario
    $sql_detalle = "
        SELECT 
            m.*,
            u.Nombre as otro_nombre,
            u.Rol as otro_rol,
            u.foto_perfil as otro_foto,
            m.remitente_id,
            m.destinatario_id,
            m.leido
        FROM mensajes m
        JOIN usuarios u ON u.Id = ?
        WHERE m.id = ?
    ";
    $stmt = $conn->prepare($sql_detalle);
    $stmt->bind_param("ii", $otro_id, $ultimo_id);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$detalle) continue; // Saltar si no hay datos
    
    // Contar mensajes no leídos de esta conversación
    $sql_no_leidos = "
        SELECT COUNT(*) as total 
        FROM mensajes 
        WHERE remitente_id = ? AND destinatario_id = ? AND leido = 0
    ";
    $stmt = $conn->prepare($sql_no_leidos);
    $stmt->bind_param("ii", $otro_id, $user_id);
    $stmt->execute();
    $no_leidos = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Formatear el último mensaje
    $ultimo_texto = $detalle['mensaje'] ?? '';
    $es_mio = ($detalle['remitente_id'] == $user_id);
    $prefijo = $es_mio ? 'Tú: ' : '';
    
    if (strlen($ultimo_texto) > 50) {
        $ultimo_texto = substr($ultimo_texto, 0, 50) . '...';
    }
    
    // Formatear fecha
    $fecha_msg = strtotime($detalle['fecha_envio'] ?? 'now');
    $hoy = strtotime(date('Y-m-d'));
    $ayer = strtotime('-1 day', $hoy);
    
    if ($fecha_msg >= $hoy) {
        $fecha_formateada = date('H:i', $fecha_msg);
    } elseif ($fecha_msg >= $ayer) {
        $fecha_formateada = 'Ayer';
    } else {
        $fecha_formateada = date('d/m/Y', $fecha_msg);
    }
    
    $conversaciones[] = [
        'otro_id'           => $otro_id,
        'otro_nombre'       => $detalle['otro_nombre'],
        'otro_rol'          => $detalle['otro_rol'],
        'otro_foto'         => $detalle['otro_foto'] ?? 'uploads/default_usuario.png',
        'ultimo_mensaje'    => $prefijo . $ultimo_texto,
        'ultima_fecha'      => $fecha_formateada,
        'ultimo_mensaje_id' => $ultimo_id,
        'no_leidos'         => $no_leidos,
        'es_mio'            => $es_mio
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajería · ITSZ</title>
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
        }

        .header-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 10px 0;
            box-shadow: var(--shadow);
            border-bottom: 3px solid var(--dorado);
            position: sticky;
            top: 0;
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

        .main-content { flex: 1; padding: 20px; display: flex; justify-content: center; }

        .chat-list-container {
            max-width: 800px;
            width: 100%;
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 140px);
            max-height: 700px;
        }

        .chat-list-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 3px solid var(--dorado);
            flex-shrink: 0;
        }

        .chat-list-header .titulo { display: flex; align-items: center; gap: 12px; }
        .chat-list-header .titulo i { color: var(--dorado); font-size: 22px; }
        .chat-list-header .titulo h1 { color: var(--blanco); font-size: 18px; font-weight: 800; }
        .chat-list-header .titulo .subtitulo { color: rgba(255,255,255,0.75); font-size: 11px; font-weight: 400; }

        .chat-list-header .btn-nuevo {
            background: var(--verde-exito);
            color: var(--blanco);
            padding: 8px 16px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
        }
        .chat-list-header .btn-nuevo:hover { background: #219a52; transform: translateY(-2px); }

        .chat-search {
            background: var(--gris);
            padding: 10px 20px;
            border-bottom: 1px solid var(--gris-oscuro);
            flex-shrink: 0;
        }

        .chat-search .input-buscar {
            width: 100%;
            padding: 10px 18px;
            border: 1px solid var(--gris-oscuro);
            border-radius: 25px;
            font-size: 14px;
            background: var(--blanco);
            outline: none;
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }
        .chat-search .input-buscar:focus {
            border-color: var(--azul);
            box-shadow: 0 0 0 2px rgba(27, 57, 106, 0.1);
        }

        .chat-list { flex: 1; overflow-y: auto; background: var(--blanco); }
        .chat-list::-webkit-scrollbar { width: 6px; }
        .chat-list::-webkit-scrollbar-track { background: transparent; }
        .chat-list::-webkit-scrollbar-thumb { background: rgba(27, 57, 106, 0.2); border-radius: 10px; }

        .chat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            text-decoration: none;
            color: var(--texto-oscuro);
            transition: 0.2s;
            border-bottom: 1px solid var(--gris);
            position: relative;
        }
        .chat-item:hover { background: var(--gris); }
        .chat-item.no-leido { background: #F0F8FF; }

        .chat-item .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--azul);
            color: var(--blanco);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        .chat-item .avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .chat-item .info { flex: 1; min-width: 0; }

        .chat-item .info .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 3px;
        }

        .chat-item .info .nombre {
            font-weight: 700;
            font-size: 15px;
            color: var(--azul);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-item .info .fecha { font-size: 11px; color: #888; flex-shrink: 0; }
        .chat-item.no-leido .info .fecha { color: var(--verde-exito); font-weight: 700; }

        .chat-item .info .mensaje-preview {
            font-size: 13px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .chat-item.no-leido .info .mensaje-preview { font-weight: 600; color: var(--texto-oscuro); }
        .chat-item .info .mensaje-preview i.tilde { color: #4FC3F7; font-size: 12px; }

        .chat-item .badge-no-leidos {
            background: var(--verde-exito);
            color: var(--blanco);
            min-width: 22px;
            height: 22px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            padding: 0 6px;
            flex-shrink: 0;
        }

        .chat-item .rol-badge {
            display: inline-block;
            background: var(--gris);
            color: var(--azul);
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            margin-left: 6px;
        }

        .estado-vacio { text-align: center; padding: 60px 20px; color: #999; }
        .estado-vacio i { font-size: 60px; color: var(--dorado); margin-bottom: 15px; display: block; opacity: 0.5; }
        .estado-vacio p { font-size: 14px; margin: 5px 0; }

        .estado-vacio .btn-nuevo-grande {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            margin-top: 15px;
            font-size: 14px;
            transition: 0.3s;
        }
        .estado-vacio .btn-nuevo-grande:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(27, 57, 106, 0.4); }

        .footer-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            padding: 16px 20px;
            text-align: center;
            border-top: 3px solid var(--dorado);
            flex-shrink: 0;
        }
        .footer-itsz p { font-size: 12px; opacity: 0.85; margin: 2px 0; }
        .footer-itsz .copy { font-size: 10px; opacity: 0.6; margin-top: 4px; }

        @media (max-width: 768px) {
            body { padding: 0; }
            .main-content { padding: 0; }
            .chat-list-container { height: 100vh; border-radius: 0; max-width: 100%; max-height: 100vh; }
            .header-itsz { padding: 8px 0; }
            .header-itsz .logo h1 { font-size: 0.85rem; }
            .header-itsz nav a { font-size: 0.7rem; padding: 4px 10px; }
            .chat-list-header { padding: 12px 16px; }
            .chat-list-header .titulo h1 { font-size: 16px; }
            .chat-item { padding: 10px 15px; }
            .chat-item .avatar { width: 45px; height: 45px; font-size: 18px; }
            .chat-item .info .nombre { font-size: 14px; }
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

    <main class="main-content">
        <div class="chat-list-container">
            
            <div class="chat-list-header">
                <div class="titulo">
                    <i class="fab fa-whatsapp"></i>
                    <div>
                        <h1>Mensajería</h1>
                        <div class="subtitulo"><?php echo count($conversaciones); ?> conversación(es)</div>
                    </div>
                </div>
                <a href="mensajes_enviar.php" class="btn-nuevo">
                    <i class="fas fa-plus"></i> Nuevo
                </a>
            </div>

            <div class="chat-search">
                <input type="text" 
                       class="input-buscar" 
                       placeholder="🔍 Buscar conversación..." 
                       id="buscarChat"
                       onkeyup="filtrarChats()">
            </div>

            <div class="chat-list" id="listaChats">
                <?php if (count($conversaciones) > 0): ?>
                    <?php foreach ($conversaciones as $conv): ?>
                        <a href="mensajes_leer.php?id=<?php echo $conv['ultimo_mensaje_id']; ?>" 
                           class="chat-item <?php echo $conv['no_leidos'] > 0 ? 'no-leido' : ''; ?>"
                           data-nombre="<?php echo strtolower(htmlspecialchars($conv['otro_nombre'])); ?>">
                            
                            <div class="avatar">
                                <?php if (!empty($conv['otro_foto']) && file_exists($conv['otro_foto'])): ?>
                                    <img src="<?php echo htmlspecialchars($conv['otro_foto']); ?>" 
                                         alt="Avatar"
                                         onerror="this.style.display='none'; this.parentNode.innerHTML='<?php echo strtoupper(substr($conv['otro_nombre'], 0, 1)); ?>';">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($conv['otro_nombre'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>

                            <div class="info">
                                <div class="header-row">
                                    <div class="nombre">
                                        <?php echo htmlspecialchars($conv['otro_nombre']); ?>
                                        <span class="rol-badge"><?php echo htmlspecialchars($conv['otro_rol']); ?></span>
                                    </div>
                                    <div class="fecha"><?php echo $conv['ultima_fecha']; ?></div>
                                </div>
                                <div class="mensaje-preview">
                                    <?php if ($conv['es_mio']): ?>
                                        <i class="fas fa-check-double tilde"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($conv['ultimo_mensaje']); ?>
                                </div>
                            </div>

                            <?php if ($conv['no_leidos'] > 0): ?>
                                <div class="badge-no-leidos"><?php echo $conv['no_leidos']; ?></div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="estado-vacio">
                        <i class="fas fa-comments"></i>
                        <p>No tienes conversaciones</p>
                        <p style="font-size: 12px;">Envía un mensaje para comenzar</p>
                        <a href="mensajes_enviar.php" class="btn-nuevo-grande">
                            <i class="fas fa-paper-plane"></i> Enviar mi primer mensaje
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer-itsz">
        <p><strong>Tecnológico Superior de Zongolica</strong> · Sistema Integral Académico</p>
        <p class="copy">&copy; <?php echo date('Y'); ?> Todos los derechos reservados</p>
    </footer>

    <script>
        function filtrarChats() {
            const input = document.getElementById('buscarChat');
            const filtro = input.value.toLowerCase();
            const items = document.querySelectorAll('.chat-item');
            
            items.forEach(item => {
                const nombre = item.getAttribute('data-nombre');
                if (nombre && nombre.includes(filtro)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        window.addEventListener('load', function() {
            const chats = document.querySelectorAll('.chat-item');
            if (chats.length > 5) {
                document.getElementById('buscarChat').focus();
            }
        });
    </script>

</body>
</html>