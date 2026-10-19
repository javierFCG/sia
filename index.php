<?php
// index.php - Página principal del SIA
session_start();

// Si el usuario ya está logueado, redirigir a su panel
if (isset($_SESSION['username'])) {
    $rol = $_SESSION['role'] ?? '';
    
    if ($rol === 'Administrador') {
        header("Location: admin_panel.php");
        exit();
    } elseif ($rol === 'Docente') {
        header("Location: docente_panel.php");
        exit();
    } elseif ($rol === 'NuevoIngreso') {
        header("Location: nuevo_ingreso_documentos.php");
        exit();
    } else {
        header("Location: estudiante_panel.php");
        exit();
    }
}

// Si no está logueado, ir al login
header("Location: Login.html");
exit();
?>