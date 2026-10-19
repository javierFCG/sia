<?php
// mensajes_enviar.php - Formulario de nuevo mensaje (envía por AJAX a procesar_mensaje.php)
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit();
}

$remitente_id     = (int)$_SESSION['user_id'];
$rol_remitente    = $_SESSION['role'] ?? 'Estudiante';
$nombre_remitente = $_SESSION['username'];

// Conexión con charset UTF-8
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ============================================================
// OBTENER DESTINATARIOS SEGÚN EL ROL
// ============================================================
if ($rol_remitente === 'Estudiante') {
    $sql = "SELECT Id, Nombre, Rol, foto_perfil FROM usuarios 
            WHERE Rol IN ('Administrador', 'Docente') AND Id != ? 
            ORDER BY Rol, Nombre ASC";
} elseif ($rol_remitente === 'Docente') {
    $sql = "SELECT Id, Nombre, Rol, foto_perfil FROM usuarios 
            WHERE Rol IN ('Administrador', 'Docente') AND Id != ? 
            ORDER BY Rol, Nombre ASC";
} elseif ($rol_remitente === 'Administrador') {
    $sql = "SELECT Id, Nombre, Rol, foto_perfil FROM usuarios 
            WHERE Id != ? ORDER BY Rol, Nombre ASC";
} else {
    $sql = "SELECT Id, Nombre, Rol, foto_perfil FROM usuarios 
            WHERE Rol = 'Administrador' AND Id != ? ORDER BY Nombre ASC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $remitente_id);
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// ============================================================
// PRESELECCIONAR DESTINATARIO SI VIENE ?responder=ID
// ============================================================
$destinatario_preseleccionado = 0;
if (isset($_GET['responder']) && is_numeric($_GET['responder'])) {
    $destinatario_preseleccionado = (int)$_GET['responder'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Mensaje · ITSZ</title>
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
            padding: 12px 0;
            box-shadow: var(--shadow);
            border-bottom: 4px solid var(--dorado);
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

        .header-itsz .logo { display: flex; align-items: center; gap: 14px; }
        .header-itsz .logo img { height: 50px; width: auto; }
        .header-itsz .logo h1 { color: var(--blanco); font-weight: 800; font-size: 1.1rem; }
        .header-itsz .logo h1 small { display: block; font-weight: 400; font-size: 0.65rem; color: rgba(255,255,255,0.7); }

        .header-itsz nav { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .header-itsz nav a {
            color: var(--blanco);
            font-size: 0.85rem;
            font-weight: 500;
            padding: 6px 16px;
            border-radius: var(--radius);
            transition: all 0.3s;
            text-decoration: none;
        }
        .header-itsz nav a:hover { background: rgba(255, 255, 255, 0.12); }
        .header-itsz nav .btn-salir { background: var(--rojo); padding: 6px 18px; border-radius: var(--radius); font-weight: 600; }

        .main-content {
            flex: 1;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chat-container {
            max-width: 700px;
            width: 100%;
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 3px solid var(--dorado);
        }
        .chat-header .btn-back { color: var(--blanco); font-size: 20px; text-decoration: none; padding: 6px 10px; border-radius: 50%; transition: 0.3s; }
        .chat-header .btn-back:hover { background: rgba(255,255,255,0.15); }
        .chat-header .icon-chat {
            width: 45px; height: 45px; border-radius: 50%;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            color: var(--dorado); font-size: 20px; border: 2px solid var(--dorado);
        }
        .chat-header .info-contacto { flex: 1; }
        .chat-header .info-contacto .nombre { color: var(--blanco); font-size: 17px; font-weight: 700; }
        .chat-header .info-contacto .estado { color: rgba(255,255,255,0.75); font-size: 12px; }
        .chat-header .info-contacto .estado i { color: var(--dorado); font-size: 10px; margin-right: 4px; }

        .aviso-restriccion {
            background: #FFF8E1;
            padding: 12px 20px;
            border-left: 4px solid var(--dorado);
            font-size: 13px;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .aviso-restriccion i { color: var(--dorado); font-size: 16px; }

        .chat-body {
            padding: 24px;
            background: #ECE5DD;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.3) 1px, transparent 1px),
                radial-gradient(circle at 80% 70%, rgba(255,255,255,0.3) 1px, transparent 1px);
            background-size: 50px 50px;
            flex: 1;
        }

        .burbuja-remitente {
            background: var(--verde-chat);
            padding: 12px 16px;
            border-radius: 10px;
            border-top-right-radius: 2px;
            max-width: 85%;
            margin-left: auto;
            margin-bottom: 20px;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .burbuja-remitente::before {
            content: '';
            position: absolute;
            right: -8px; top: 0;
            width: 0; height: 0;
            border-top: 8px solid var(--verde-chat);
            border-right: 8px solid transparent;
        }
        .burbuja-remitente .nombre-burbuja {
            font-size: 11px; font-weight: 700; color: var(--azul);
            margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between; gap: 10px;
        }
        .burbuja-remitente .rol-badge { background: var(--azul); color: var(--blanco); padding: 2px 10px; border-radius: 12px; font-size: 9px; font-weight: 700; letter-spacing: 0.3px; }
        .burbuja-remitente .texto-burbuja { font-size: 14px; color: var(--texto-oscuro); }

        .form-mensaje { background: var(--blanco); border-radius: var(--radius); padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

        .input-group { margin-bottom: 16px; }
        .input-group label { display: block; font-weight: 600; color: var(--azul); margin-bottom: 6px; font-size: 13px; }
        .input-group label i { color: var(--dorado); margin-right: 6px; }
        .input-group label .required { color: var(--rojo); font-weight: 700; }

        .input-group select,
        .input-group input[type="text"],
        .input-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            background: var(--blanco);
            outline: none;
            transition: 0.3s;
        }
        .input-group select:focus,
        .input-group input[type="text"]:focus,
        .input-group textarea:focus {
            border-color: var(--azul);
            box-shadow: 0 0 0 3px rgba(27, 57, 106, 0.08);
        }
        .input-group textarea { resize: vertical; min-height: 100px; line-height: 1.5; }
        .input-group .help-text { font-size: 11px; color: var(--texto-oscuro); opacity: 0.6; margin-top: 4px; }
        .input-group .help-text i { color: var(--dorado); margin-right: 4px; }

        .acciones-form { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .btn-enviar {
            flex: 1;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            box-shadow: 0 4px 15px rgba(27, 57, 106, 0.3);
        }
        .btn-enviar:hover { box-shadow: 0 6px 20px rgba(27, 57, 106, 0.4); }
        .btn-enviar:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-cancelar { background: var(--gris); color: var(--texto-oscuro); }
        .btn-cancelar:hover { background: var(--gris-oscuro); }

        .mensaje-estado {
            padding: 12px 18px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .mensaje-estado.success { background: #d4edda; color: #155724; border-left: 4px solid var(--verde-exito); }
        .mensaje-estado.error { background: #f8d7da; color: #721c24; border-left: 4px solid var(--rojo); }

        @media (max-width: 768px) {
            body { padding: 0; }
            .main-content { padding: 10px; }
            .chat-container { max-width: 100%; border-radius: 15px; }
            .header-itsz { display: none; }
            .chat-header { padding: 12px 16px; }
            .chat-header .nombre { font-size: 15px !important; }
            .chat-body { padding: 16px; }
            .form-mensaje { padding: 16px; }
            .acciones-form { flex-direction: column; }
            .btn { width: 100%; }
            .burbuja-remitente { max-width: 95%; }
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
        <div class="chat-container">

            <div class="chat-header">
                <a href="mensajes.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="icon-chat">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <div class="info-contacto">
                    <div class="nombre">Nuevo Mensaje</div>
                    <div class="estado">
                        <i class="fas fa-circle"></i> Redactando
                    </div>
                </div>
            </div>

            <div class="aviso-restriccion">
                <i class="fas fa-info-circle"></i>
                <?php if ($rol_remitente === 'Estudiante'): ?>
                    Como <strong>estudiante</strong>, solo puedes enviar mensajes a <strong>Administradores</strong> y <strong>Docentes</strong>.
                <?php elseif ($rol_remitente === 'Docente'): ?>
                    Como <strong>docente</strong>, puedes enviar mensajes a <strong>Administradores</strong> y otros <strong>Docentes</strong>.
                <?php elseif ($rol_remitente === 'Administrador'): ?>
                    Como <strong>administrador</strong>, puedes enviar mensajes a <strong>cualquier usuario</strong> del sistema.
                <?php endif; ?>
            </div>

            <div class="chat-body">

                <!-- Contenedor para mensajes de éxito/error (dinámicos por AJAX) -->
                <div id="respuestaAjax"></div>

                <div class="burbuja-remitente">
                    <div class="nombre-burbuja">
                        <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($nombre_remitente); ?></span>
                        <span class="rol-badge"><?php echo htmlspecialchars($rol_remitente); ?></span>
                    </div>
                    <div class="texto-burbuja">
                        Redactando un nuevo mensaje...
                    </div>
                </div>

                <form class="form-mensaje" id="formMensaje">

                    <div class="input-group">
                        <label for="destinatario">
                            <i class="fas fa-user"></i> Para <span class="required">*</span>
                        </label>
                        <select name="destinatario" id="destinatario" required>
                            <option value="">Selecciona un destinatario...</option>
                            <?php 
                            $rol_actual = '';
                            foreach ($usuarios as $u): 
                                $nuevo_rol = $u['Rol'];
                                
                                if ($rol_actual !== $nuevo_rol) {
                                    if ($rol_actual !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($nuevo_rol) . 's">';
                                    $rol_actual = $nuevo_rol;
                                }
                                
                                $selected = ($destinatario_preseleccionado == $u['Id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $u['Id']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($u['Nombre']); ?>
                                </option>
                            <?php endforeach; 
                            if ($rol_actual !== '') echo '</optgroup>';
                            ?>
                        </select>
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i> 
                            <?php if ($rol_remitente === 'Estudiante'): ?>
                                Solo puedes enviar a Administradores y Docentes
                            <?php elseif ($rol_remitente === 'Docente'): ?>
                                Puedes enviar a Administradores y otros Docentes
                            <?php else: ?>
                                Puedes enviar a todos los usuarios
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="asunto">
                            <i class="fas fa-tag"></i> Asunto <span class="required">*</span>
                        </label>
                        <input type="text" 
                               name="asunto" 
                               id="asunto" 
                               placeholder="Escribe un asunto breve..." 
                               required
                               maxlength="100">
                    </div>

                    <div class="input-group">
                        <label for="mensaje">
                            <i class="fas fa-comment-alt"></i> Mensaje <span class="required">*</span>
                        </label>
                        <textarea name="mensaje" 
                                  id="mensaje" 
                                  placeholder="Escribe tu mensaje aquí..." 
                                  required
                                  maxlength="2000"></textarea>
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i> 
                            Máximo 2000 caracteres
                        </div>
                    </div>

                    <div class="acciones-form">
                        <a href="mensajes.php" class="btn btn-cancelar">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" id="btnEnviar" class="btn btn-enviar">
                            <i class="fas fa-paper-plane"></i> Enviar Mensaje
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Auto-enfocar el primer campo
        window.addEventListener('load', function() {
            document.getElementById('destinatario').focus();
        });

        // Enviar con Ctrl+Enter
        document.getElementById('mensaje').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                e.preventDefault();
                document.getElementById('formMensaje').dispatchEvent(new Event('submit'));
            }
        });

        // ============================================================
        // ENVÍO POR AJAX A procesar_mensaje.php
        // ============================================================
        document.getElementById('formMensaje').addEventListener('submit', function(e) {
            e.preventDefault();

            const destinatario = document.getElementById('destinatario').value;
            const asunto      = document.getElementById('asunto').value.trim();
            const mensaje     = document.getElementById('mensaje').value.trim();
            const btnEnviar   = document.getElementById('btnEnviar');
            const respuesta   = document.getElementById('respuestaAjax');

            // Validaciones del lado del cliente
            if (!destinatario) { alert('Por favor, selecciona un destinatario.'); return; }
            if (!asunto)       { alert('Por favor, escribe un asunto.'); return; }
            if (!mensaje)      { alert('Por favor, escribe un mensaje.'); return; }

            // Deshabilitar botón mientras se envía
            btnEnviar.disabled = true;
            btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            // Preparar datos
            const formData = new FormData();
            formData.append('destinatario', destinatario);
            formData.append('asunto', asunto);
            formData.append('mensaje', mensaje);

            // Enviar a procesar_mensaje.php
            fetch('procesar_mensaje.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    respuesta.innerHTML = `
                        <div class="mensaje-estado success">
                            <i class="fas fa-check-circle"></i>
                            ${data.mensaje} (ID: ${data.id} · ${data.hora} ${data.fecha})
                        </div>`;
                    
                    // Limpiar formulario
                    document.getElementById('formMensaje').reset();

                    // Redirigir al chat después de 1.5 segundos
                    setTimeout(() => {
                        window.location.href = 'mensajes.php?con=' + destinatario;
                    }, 1500);
                } else {
                    respuesta.innerHTML = `
                        <div class="mensaje-estado error">
                            <i class="fas fa-exclamation-circle"></i>
                            ${data.error}
                        </div>`;
                }
            })
            .catch(err => {
                respuesta.innerHTML = `
                    <div class="mensaje-estado error">
                        <i class="fas fa-exclamation-circle"></i>
                        Error de conexión: ${err.message}
                    </div>`;
            })
            .finally(() => {
                btnEnviar.disabled = false;
                btnEnviar.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Mensaje';
            });
        });

        // Contador de caracteres en textarea
        const mensajeTextarea = document.getElementById('mensaje');
        mensajeTextarea.addEventListener('input', function() {
            const length = this.value.length;
            const helpText = this.nextElementSibling;
            
            if (length > 1800) {
                helpText.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: var(--dorado);"></i> ' + 
                                     (2000 - length) + ' caracteres restantes';
            } else {
                helpText.innerHTML = '<i class="fas fa-info-circle"></i> Máximo 2000 caracteres';
            }
        });
    </script>

</body>
</html>