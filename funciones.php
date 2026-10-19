<?php
// funciones.php - Funciones auxiliares
require_once 'config.php';

function obtenerNombreCarrera($carrera_id) {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) { return "Carrera no encontrada"; }
    
    $stmt = $conn->prepare("SELECT nombre FROM carreras WHERE id = ?");
    $stmt->bind_param("i", $carrera_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $nombre = $result->num_rows > 0 ? $result->fetch_assoc()['nombre'] : "Carrera no encontrada";
    $stmt->close();
    $conn->close();
    return $nombre;
}

function obtenerNombreUsuario($usuario_id) {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) { return "Usuario no encontrado"; }
    
    $stmt = $conn->prepare("SELECT Nombre FROM usuarios WHERE Id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $nombre = $result->num_rows > 0 ? $result->fetch_assoc()['Nombre'] : "Usuario no encontrado";
    $stmt->close();
    $conn->close();
    return $nombre;
}

function obtenerNombrePeriodo($periodo_id) {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) { return "Período no encontrado"; }
    
    $stmt = $conn->prepare("SELECT nombre FROM periodos WHERE id = ?");
    $stmt->bind_param("i", $periodo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $nombre = $result->num_rows > 0 ? $result->fetch_assoc()['nombre'] : "Período no encontrado";
    $stmt->close();
    $conn->close();
    return $nombre;
}

function obtenerCalificacionLetra($calificacion) {
    if ($calificacion === null || $calificacion === '') { return 'N/A'; }
    $num = (float)$calificacion;
    if ($num >= 90) return 'A';
    if ($num >= 80) return 'B';
    if ($num >= 70) return 'C';
    if ($num >= 60) return 'D';
    return 'F';
}

function obtenerEstadoCalificacion($calificacion) {
    if ($calificacion === null || $calificacion === '') { return 'Pendiente'; }
    $num = (float)$calificacion;
    if ($num >= 70) return 'Aprobado';
    return 'Reprobado';
}
?>