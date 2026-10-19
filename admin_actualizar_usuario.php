<?php
// admin_actualizar_usuario.php - CON CAMPO DE GRUPO
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_POST['id'], $_POST['username'], $_POST['email'], $_POST['role'])) {
    die("Datos incompletos.");
}

$id = (int)$_POST['id'];
$nombre = trim($_POST['username']);
$correo = trim($_POST['email']);
$rol = $_POST['role'];
$grupo = trim($_POST['grupo'] ?? '');
$semestre = (int)($_POST['semestre'] ?? 1);
$carrera_id = (int)($_POST['carrera_id'] ?? 0);
$nueva_pass = trim($_POST['password'] ?? '');
$conf_pass = trim($_POST['confirm_password'] ?? '');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

if (!empty($nueva_pass)) {
    if ($nueva_pass !== $conf_pass) {
        die("Las contraseñas no coinciden.");
    }
    $pass_hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
    $sql = "UPDATE usuarios SET 
                Nombre = ?, 
                Correo = ?, 
                Rol = ?, 
                `password` = ?,
                grupo = ?,
                semestre = ?,
                carrera_id = ?
            WHERE Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssiii", $nombre, $correo, $rol, $pass_hash, $grupo, $semestre, $carrera_id, $id);
} else {
    $sql = "UPDATE usuarios SET 
                Nombre = ?, 
                Correo = ?, 
                Rol = ?,
                grupo = ?,
                semestre = ?,
                carrera_id = ?
            WHERE Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiii", $nombre, $correo, $rol, $grupo, $semestre, $carrera_id, $id);
}

if ($stmt->execute()) {
    header("Location: admin_gestionar_usuarios.php?success=actualizado");
    exit();
} else {
    echo "Error al actualizar: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>