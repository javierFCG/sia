<?php
// procesar_mensaje.php - Procesa envío de mensajes con archivos adjuntos
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$remitente_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Permitir mensaje vacío SI hay archivo
$tiene_mensaje = isset($_POST['mensaje']) && trim($_POST['mensaje']) !== '';
$tiene_archivo = isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE;

if (!$tiene_mensaje && !$tiene_archivo) {
    echo json_encode(['success' => false, 'error' => 'Debes escribir un mensaje o adjuntar un archivo']);
    exit();
}

if (!isset($_POST['destinatario'])) {
    echo json_encode(['success' => false, 'error' => 'Destinatario no especificado']);
    exit();
}

$destinatario_id = (int)$_POST['destinatario'];
$contenido       = trim($_POST['mensaje'] ?? '');
$asunto          = isset($_POST['asunto']) ? trim($_POST['asunto']) : 'Sin asunto';

if ($destinatario_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Destinatario no válido']);
    exit();
}

// Conexión
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit();
}
$conn->set_charset("utf8mb4");

// ============================================================
// PROCESAR ARCHIVO ADJUNTO
// ============================================================
$archivo_ruta   = null;
$archivo_nombre = null;
$archivo_tipo   = null;
$archivo_tamano = null;

if ($tiene_archivo) {
    $archivo = $_FILES['archivo'];
    
    // Verificar errores de subida
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo es demasiado grande',
            UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo',
            UPLOAD_ERR_EXTENSION  => 'Subida detenida por una extensión PHP'
        ];
        $msg_error = $errores[$archivo['error']] ?? 'Error desconocido al subir el archivo';
        echo json_encode(['success' => false, 'error' => $msg_error]);
        exit();
    }
    
    // Límite de 10 MB
    $max_size = 10 * 1024 * 1024;
    if ($archivo['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'El archivo excede los 10 MB permitidos']);
        exit();
    }
    
    // Tipos permitidos
    $tipos_permitidos = [
        // Imágenes
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
        // Documentos
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Texto
        'text/plain', 'text/csv',
        // Comprimidos
        'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'
    ];
    
    // Detectar tipo real con finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo_real = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($tipo_real, $tipos_permitidos)) {
        echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido: ' . $tipo_real]);
        exit();
    }
    
    // Generar nombre único
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $nombre_unico = 'msg_' . $remitente_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    
    // Ruta de destino
    $carpeta_destino = __DIR__ . '/uploads/mensajes/';
    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0755, true);
    }
    
    $ruta_destino = $carpeta_destino . $nombre_unico;
    
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo en el servidor']);
        exit();
    }
    
    $archivo_ruta   = 'uploads/mensajes/' . $nombre_unico;
    $archivo_nombre = $archivo['name'];
    $archivo_tipo   = $tipo_real;
    $archivo_tamano = $archivo['size'];
}

// ============================================================
// INSERTAR EN BASE DE DATOS
// ============================================================
$sql = "INSERT INTO mensajes 
        (remitente_id, destinatario_id, asunto, mensaje, archivo_ruta, archivo_nombre, archivo_tipo, archivo_tamano, fecha_envio, leido) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error al preparar: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iisssssi", 
    $remitente_id, 
    $destinatario_id, 
    $asunto, 
    $contenido,
    $archivo_ruta,
    $archivo_nombre,
    $archivo_tipo,
    $archivo_tamano
);

if ($stmt->execute()) {
    echo json_encode([
        'success'         => true,
        'id'              => $conn->insert_id,
        'hora'            => date('H:i'),
        'fecha'           => date('d/m/Y'),
        'archivo_ruta'    => $archivo_ruta,
        'archivo_nombre'  => $archivo_nombre,
        'archivo_tipo'    => $archivo_tipo,
        'archivo_tamano'  => $archivo_tamano,
        'es_imagen'       => $archivo_tipo && strpos($archivo_tipo, 'image/') === 0,
        'mensaje'         => 'Mensaje enviado correctamente'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>