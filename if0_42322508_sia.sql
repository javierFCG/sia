-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: sql303.infinityfree.com
-- Tiempo de generación: 19-10-2026 a las 11:41:47
-- Versión del servidor: 11.4.13-MariaDB
-- Versión de PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `if0_42322508_sia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `anuncios`
--

CREATE TABLE `anuncios` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `autor_id` int(11) DEFAULT NULL,
  `fecha_publicacion` timestamp NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aulas`
--

CREATE TABLE `aulas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `aulas`
--

INSERT INTO `aulas` (`id`, `nombre`, `capacidad`, `ubicacion`) VALUES
(1, 'Aula 101', 30, 'Edificio A - Planta Baja'),
(2, 'Aula 102', 35, 'Edificio A - Planta Baja'),
(3, 'Aula 201', 40, 'Edificio B - Planta Alta'),
(4, 'Laboratorio 1', 25, 'Edificio C - Planta Baja');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `becas`
--

CREATE TABLE `becas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `porcentaje` decimal(5,2) DEFAULT NULL,
  `cupo` int(11) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `becas`
--

INSERT INTO `becas` (`id`, `nombre`, `descripcion`, `porcentaje`, `cupo`, `fecha_inicio`, `fecha_fin`, `activo`) VALUES
(4, 'Beca Excelencia AcadÃ©mica', 'Para alumnos con promedio mayor a 9.5', '100.00', 5, '2026-01-01', '2026-12-31', 1),
(2, 'Beca de Apoyo', 'Para estudiantes con necesidades económicas', '30.00', 20, '2026-08-05', '2027-02-05', 1),
(3, 'Beca Alimenticia', 'Para mejorar la dieta de los alumnos', '25.00', 20, '2026-08-10', '2026-12-12', 1),
(5, 'Beca Deportiva', 'Para deportistas destacados', '50.00', 10, '2026-01-01', '2026-12-31', 1),
(6, 'Beca SocioeconÃ³mica', 'Para alumnos de bajos recursos', '75.00', 20, '2026-01-01', '2026-12-31', 1),
(7, 'Beca de Transporte', 'Apoyo para gastos de transporte', '30.00', 50, '2026-01-01', '2026-12-31', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `becas_solicitudes`
--

CREATE TABLE `becas_solicitudes` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `beca_id` int(11) DEFAULT NULL,
  `estado` enum('Pendiente','Aprobada','Rechazada') DEFAULT 'Pendiente',
  `fecha_solicitud` timestamp NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `becas_solicitudes`
--

INSERT INTO `becas_solicitudes` (`id`, `estudiante_id`, `beca_id`, `estado`, `fecha_solicitud`, `observaciones`) VALUES
(1, 6, 3, 'Pendiente', '2026-09-05 01:38:06', ''),
(2, 10, 3, 'Pendiente', '2026-09-05 03:11:08', ''),
(3, 10, 2, 'Pendiente', '2026-09-05 03:11:11', ''),
(4, 10, 1, 'Pendiente', '2026-09-05 03:11:14', ''),
(5, 3, 3, 'Pendiente', '2026-10-08 01:35:36', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

CREATE TABLE `carreras` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `clave` varchar(10) NOT NULL,
  `duracion_semestres` int(2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `carreras`
--

INSERT INTO `carreras` (`id`, `nombre`, `clave`, `duracion_semestres`) VALUES
(7, 'Ingeniería en Sistemas', 'IELE', 9),
(8, 'Ingeniería Civil', 'ICIV', 9),
(9, 'Ingeniería Industrial', 'IIND', 9),
(10, 'Ingeniería Mecatrónica', 'IMEC', 9),
(11, 'Licenciatura en Administración', 'LADM', 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `clave`, `valor`) VALUES
(1, 'logo_universidad', 'assets/img/logo_itsz.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes_especialidades`
--

CREATE TABLE `docentes_especialidades` (
  `id` int(11) NOT NULL,
  `docente_id` int(11) NOT NULL,
  `carrera_id` int(11) NOT NULL,
  `tipo` enum('Especialidad','Tronco Común','Ambas') DEFAULT 'Tronco Común',
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `docentes_especialidades`
--

INSERT INTO `docentes_especialidades` (`id`, `docente_id`, `carrera_id`, `tipo`, `fecha_asignacion`) VALUES
(2, 2, 1, 'Especialidad', '2026-09-09 22:41:22'),
(3, 2, 3, '', '2026-09-09 22:41:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_alumnos`
--

CREATE TABLE `documentos_alumnos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_documento` enum('Acta_Nacimiento','Certificado_Bachillerato','CURP','Comprobante_Domicilio','Fotografia','Ficha_Preinscripcion','Otro') NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `estado` enum('Pendiente','Revisado','Aprobado','Rechazado') DEFAULT 'Pendiente',
  `comentarios` text DEFAULT NULL,
  `fecha_subida` timestamp NULL DEFAULT current_timestamp(),
  `fecha_revision` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `documentos_alumnos`
--

INSERT INTO `documentos_alumnos` (`id`, `usuario_id`, `tipo_documento`, `nombre_archivo`, `ruta_archivo`, `estado`, `comentarios`, `fecha_subida`, `fecha_revision`) VALUES
(7, 11, 'Acta_Nacimiento', 'logo_universidad.png', 'uploads/documentos/11_Acta_Nacimiento_1788902121.png', 'Aprobado', '', '2026-09-08 21:15:21', '2026-09-08 14:18:22'),
(8, 11, 'Certificado_Bachillerato', '1000101920.jpg', 'uploads/documentos/11_Certificado_Bachillerato_1788902139.jpg', 'Aprobado', '', '2026-09-08 21:15:39', '2026-09-08 14:18:25'),
(9, 11, 'CURP', '1000101920.jpg', 'uploads/documentos/11_CURP_1788902155.jpg', 'Aprobado', '', '2026-09-08 21:15:56', '2026-09-08 14:18:27'),
(10, 11, 'Comprobante_Domicilio', 'logo_universidad.png', 'uploads/documentos/11_Comprobante_Domicilio_1788902228.png', 'Aprobado', '', '2026-09-08 21:17:08', '2026-09-08 14:18:30'),
(11, 11, 'Fotografia', '1000101920.jpg', 'uploads/documentos/11_Fotografia_1788902244.jpg', 'Aprobado', '', '2026-09-08 21:17:24', '2026-09-08 14:18:33'),
(12, 11, 'Ficha_Preinscripcion', 'logo_itsz.png', 'uploads/documentos/11_Ficha_Preinscripcion_1788902252.png', 'Aprobado', '', '2026-09-08 21:17:32', '2026-09-08 14:18:35'),
(13, 12, 'Acta_Nacimiento', 'logo_itsz.png', 'uploads/documentos/12_Acta_Nacimiento_1789055815.png', 'Aprobado', '', '2026-09-10 15:56:56', '2026-09-10 09:01:56'),
(14, 12, 'Certificado_Bachillerato', 'Reporte_Residencia_SIA.docx', 'uploads/documentos/12_Certificado_Bachillerato_1789055825.docx', 'Aprobado', '', '2026-09-10 15:57:05', '2026-09-10 09:01:58'),
(15, 12, 'CURP', 'logo_itsz.png', 'uploads/documentos/12_CURP_1789055833.png', 'Aprobado', '', '2026-09-10 15:57:14', '2026-09-10 09:02:01'),
(16, 12, 'Comprobante_Domicilio', 'logo_itsz.png', 'uploads/documentos/12_Comprobante_Domicilio_1789055841.png', 'Aprobado', '', '2026-09-10 15:57:22', '2026-09-10 09:02:05'),
(17, 12, 'Fotografia', 'logo_itsz.png', 'uploads/documentos/12_Fotografia_1789055849.png', 'Aprobado', '', '2026-09-10 15:57:30', '2026-09-10 09:02:08'),
(18, 12, 'Ficha_Preinscripcion', 'logo_itsz.png', 'uploads/documentos/12_Ficha_Preinscripcion_1789055857.png', 'Aprobado', '', '2026-09-10 15:57:37', '2026-09-10 09:02:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas`
--

CREATE TABLE `entregas` (
  `id` int(11) NOT NULL,
  `tarea_id` int(11) DEFAULT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `calificacion` decimal(4,2) DEFAULT NULL,
  `fecha_entrega` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas_tareas`
--

CREATE TABLE `entregas_tareas` (
  `id` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `fecha_entrega` timestamp NULL DEFAULT current_timestamp(),
  `calificacion` decimal(5,2) DEFAULT NULL,
  `comentario_docente` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `entregas_tareas`
--

INSERT INTO `entregas_tareas` (`id`, `id_tarea`, `id_estudiante`, `nombre_archivo`, `ruta_archivo`, `fecha_entrega`, `calificacion`, `comentario_docente`) VALUES
(1, 1, 3, 'logo_universidad.png', 'uploads/tarea_1_estudiante_3.png', '2026-09-05 02:16:03', '8.00', NULL),
(2, 1, 10, '20260904_154937.jpg', 'uploads/tarea_1_estudiante_10.jpg', '2026-09-05 03:12:01', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `tipo` enum('Académico','Examen','Vacaciones','Entrega','Otro') DEFAULT 'Otro',
  `color` varchar(7) DEFAULT '#5279e2'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `carrera_id` int(11) NOT NULL,
  `semestre` int(2) NOT NULL,
  `turno` enum('Matutino','Vespertino','Mixto') DEFAULT 'Matutino',
  `capacidad` int(3) DEFAULT 30,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id`, `nombre`, `carrera_id`, `semestre`, `turno`, `capacidad`, `activo`) VALUES
(4, '103A', 1, 1, 'Matutino', 30, 1),
(5, '101', 3, 1, 'Matutino', 30, 1),
(6, '101', 6, 1, 'Matutino', 30, 1),
(7, '102', 6, 2, 'Matutino', 30, 1),
(8, '103A', 6, 1, 'Vespertino', 25, 1),
(9, '201', 7, 1, 'Matutino', 30, 1),
(10, '202', 7, 2, 'Matutino', 30, 1),
(11, '203A', 7, 1, 'Vespertino', 25, 1),
(12, '301', 8, 1, 'Matutino', 30, 1),
(13, '302', 8, 2, 'Matutino', 25, 1),
(14, '401', 9, 1, 'Matutino', 30, 1),
(15, '501', 10, 1, 'Matutino', 25, 1),
(16, '103A', 7, 1, 'Matutino', 30, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `docente_id` int(11) DEFAULT NULL,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `aula` varchar(20) DEFAULT NULL,
  `periodo_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `horarios`
--

INSERT INTO `horarios` (`id`, `materia_id`, `grupo_id`, `docente_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `aula`, `periodo_id`) VALUES
(20, 21, 16, 0, 'Lunes', '07:51:00', '09:52:00', '13', 1),
(19, 20, 16, 2, 'Lunes', '07:37:00', '08:37:00', 'salon 13', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_detalle`
--

CREATE TABLE `horarios_detalle` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) DEFAULT NULL,
  `aula_id` int(11) DEFAULT NULL,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `importaciones`
--

CREATE TABLE `importaciones` (
  `id` int(11) NOT NULL,
  `tipo` enum('Usuarios','Materias','Carreras','Grupos','Horarios') NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `registros_importados` int(11) DEFAULT 0,
  `registros_fallidos` int(11) DEFAULT 0,
  `usuario_id` int(11) NOT NULL,
  `fecha_importacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `importaciones`
--

INSERT INTO `importaciones` (`id`, `tipo`, `archivo`, `registros_importados`, `registros_fallidos`, `usuario_id`, `fecha_importacion`) VALUES
(1, '', 'uploads/importaciones/1789398996_estudiantes.csv', 0, 6, 10, '2026-09-14 15:16:36'),
(2, '', 'uploads/importaciones/1789399070_estudiantes.csv', 0, 6, 10, '2026-09-14 15:17:51'),
(3, '', 'uploads/importaciones/1789399791_estudiantes.csv', 6, 0, 10, '2026-09-14 15:29:52'),
(4, 'Carreras', 'uploads/importaciones/1789400771_carreras.csv', 6, 0, 10, '2026-09-14 15:46:12'),
(5, 'Grupos', 'uploads/importaciones/1789400991_grupos.csv', 10, 0, 10, '2026-09-14 15:49:51'),
(6, '', 'uploads/importaciones/1789401033_docentes.csv', 5, 0, 10, '2026-09-14 15:50:34'),
(7, '', 'uploads/importaciones/1789401046_becas.csv', 4, 0, 10, '2026-09-14 15:50:46'),
(8, '', 'uploads/importaciones/1789401071_calificaciones.csv', 0, 20, 10, '2026-09-14 15:51:12'),
(9, '', 'uploads/importaciones/1789401339_estudiantes.csv', 12, 0, 10, '2026-09-14 15:55:40'),
(10, '', 'uploads/importaciones/1789401522_calificaciones.csv', 0, 20, 10, '2026-09-14 15:58:42'),
(11, '', 'uploads/importaciones/1789401549_calificaciones.csv', 0, 20, 10, '2026-09-14 15:59:09'),
(12, '', 'uploads/importaciones/1789401682_calificaciones.csv', 0, 20, 10, '2026-09-14 16:01:23'),
(13, '', 'uploads/importaciones/1789401907_calificaciones.csv', 0, 20, 10, '2026-09-14 16:05:07'),
(14, 'Materias', 'uploads/importaciones/1789402060_materias.csv', 20, 0, 10, '2026-09-14 16:07:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `carrera_id` int(11) NOT NULL,
  `periodo_id` int(11) NOT NULL,
  `fecha_inscripcion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id`, `usuario_id`, `carrera_id`, `periodo_id`, `fecha_inscripcion`) VALUES
(1, 5, 1, 1, '2026-09-04 23:43:15'),
(3, 6, 1, 1, '2026-09-05 01:28:44'),
(4, 9, 1, 1, '2026-09-05 02:00:40'),
(10, 3, 7, 1, '2026-10-02 16:08:29'),
(7, 10, 1, 1, '2026-09-07 17:41:17'),
(8, 11, 3, 1, '2026-09-08 23:27:09'),
(9, 12, 3, 1, '2026-09-10 16:41:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones_detalle`
--

CREATE TABLE `inscripciones_detalle` (
  `id` int(11) NOT NULL,
  `inscripcion_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `calificacion` decimal(4,2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inscripciones_detalle`
--

INSERT INTO `inscripciones_detalle` (`id`, `inscripcion_id`, `materia_id`, `calificacion`) VALUES
(1, 1, 2, '90.00'),
(2, 1, 1, NULL),
(8, 3, 1, NULL),
(7, 3, 2, '78.00'),
(10, 4, 2, '99.00'),
(11, 4, 1, NULL),
(21, 7, 1, NULL),
(20, 7, 2, '90.00'),
(22, 8, 5, NULL),
(23, 8, 6, NULL),
(24, 8, 10, NULL),
(25, 9, 5, NULL),
(26, 9, 6, NULL),
(27, 9, 10, NULL),
(37, 10, 23, NULL),
(36, 10, 22, NULL),
(35, 10, 21, NULL),
(34, 10, 20, '99.99');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `autor` varchar(100) DEFAULT NULL,
  `editorial` varchar(100) DEFAULT NULL,
  `anio` int(11) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1,
  `disponibles` int(11) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`id`, `titulo`, `autor`, `editorial`, `anio`, `isbn`, `cantidad`, `disponibles`) VALUES
(1, 'Introducción a la Programación', 'Juan Pérez', 'Editorial Tech', 2020, '978-3-16-148410-0', 5, 5),
(2, 'Matemáticas Avanzadas', 'María García', 'Editorial Math', 2019, '978-3-16-148411-7', 3, 3),
(3, 'Base de Datos', 'Carlos López', 'Editorial Data', 2021, '978-3-16-148412-4', 4, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `usuario_nombre` varchar(100) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `clave` varchar(20) NOT NULL,
  `creditos` int(2) NOT NULL,
  `semestre` int(2) NOT NULL DEFAULT 1,
  `horario` varchar(100) DEFAULT NULL,
  `descripcion` mediumtext DEFAULT NULL,
  `docente_asignado` varchar(100) DEFAULT NULL,
  `carrera_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id`, `nombre`, `clave`, `creditos`, `semestre`, `horario`, `descripcion`, `docente_asignado`, `carrera_id`) VALUES
(20, 'Cálculo Diferencial', 'CAL-101', 5, 1, NULL, NULL, 'docente', 7),
(19, 'Redes de Computadoras', 'RED-101', 5, 5, NULL, NULL, 'Mtro. Fernando Vargas DÃ­az', 6),
(18, 'Bases de Datos', 'BDA-101', 5, 4, NULL, NULL, 'Mtro. Fernando Vargas DÃ­az', 6),
(17, 'Estructuras de Datos', 'EST-101', 5, 3, NULL, NULL, 'Mtro. Fernando Vargas DÃ­az', 6),
(16, 'Programación II', 'PROG-102', 5, 2, NULL, NULL, 'Dr. Roberto MÃ©ndez LÃ³pez', 6),
(15, 'Álgebra Lineal', 'ALG-101', 5, 2, NULL, NULL, 'docente', 6),
(14, 'Física I', 'FIS-101', 5, 1, NULL, NULL, 'docente', 6),
(12, 'Matemáticas I', 'MAT-101', 5, 1, NULL, NULL, 'Dr. Roberto MÃ©ndez LÃ³pez', 6),
(13, 'Programación I', 'PROG-101', 5, 1, NULL, NULL, 'Dr. Roberto MÃ©ndez LÃ³pez', 6),
(21, 'Circuitos Eléctricos', 'CIR-101', 5, 1, NULL, NULL, '', 7),
(22, 'Electrónica Digital', 'ELE-101', 5, 2, NULL, NULL, '', 7),
(23, 'Sistemas Digitales', 'SIS-101', 5, 3, NULL, NULL, '', 7),
(24, 'Cálculo Integral', 'CAL-102', 5, 2, NULL, NULL, 'Mtro. Fernando Vargas DÃ­az', 8),
(25, 'Mecánica de Materiales', 'MEC-101', 5, 3, NULL, NULL, 'Mtro. Fernando Vargas DÃ­az', 8),
(26, 'Dibujo Técnico', 'DIB-101', 5, 1, NULL, NULL, 'Mtro. Fernando Vargas DÃ­az', 8),
(27, 'Álgebra Superior', 'ALG-102', 5, 1, NULL, NULL, 'Mtra. Claudia Romero Silva', 9),
(28, 'Probabilidad y Estadí­stica', 'PRO-101', 5, 2, NULL, NULL, 'Mtra. Claudia Romero Silva', 9),
(29, 'Investigación de Operaciones', 'INV-101', 5, 3, NULL, NULL, 'Mtra. Claudia Romero Silva', 9),
(30, 'Robótica', 'ROB-101', 5, 1, NULL, NULL, 'Ing. Alejandro Castro NÃºÃ±ez', 10),
(31, 'Sistemas Embebidos', 'SEM-101', 5, 2, NULL, NULL, 'Ing. Alejandro Castro NÃºÃ±ez', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL,
  `remitente_id` int(11) DEFAULT NULL,
  `destinatario_id` int(11) DEFAULT NULL,
  `asunto` varchar(200) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `archivo_ruta` varchar(255) DEFAULT NULL,
  `archivo_nombre` varchar(255) DEFAULT NULL,
  `archivo_tipo` varchar(100) DEFAULT NULL,
  `archivo_tamano` int(11) DEFAULT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_envio` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metas`
--

CREATE TABLE `metas` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('Pendiente','En progreso','Completada','Cancelada') DEFAULT 'Pendiente'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `metas`
--

INSERT INTO `metas` (`id`, `estudiante_id`, `titulo`, `descripcion`, `fecha_inicio`, `fecha_fin`, `estado`) VALUES
(1, 3, 'Terminar con promedio de 9.0', 'Todas las materias del semestre', '2026-08-29', '2026-12-11', 'Pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos`
--

CREATE TABLE `periodos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('Activo','Inactivo','Cerrado') NOT NULL DEFAULT 'Inactivo',
  `calificaciones_abiertas` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `periodos`
--

INSERT INTO `periodos` (`id`, `nombre`, `fecha_inicio`, `fecha_fin`, `estado`, `calificaciones_abiertas`) VALUES
(1, 'Agosto-Diciembre 2026', '2026-08-29', '2026-12-11', 'Activo', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos`
--

CREATE TABLE `prestamos` (
  `id` int(11) NOT NULL,
  `libro_id` int(11) DEFAULT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `fecha_prestamo` date DEFAULT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `fecha_devuelto` date DEFAULT NULL,
  `estado` enum('Activo','Devuelto','Atrasado') DEFAULT 'Activo'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_becas`
--

CREATE TABLE `solicitudes_becas` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_beca` int(11) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `matricula` varchar(50) NOT NULL,
  `promedio` decimal(4,2) NOT NULL,
  `motivo` text NOT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_solicitud` timestamp NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','Aprobada','Rechazada') DEFAULT 'Pendiente',
  `comentario_admin` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) DEFAULT NULL,
  `docente_id` int(11) DEFAULT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_publicacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_limite` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tareas`
--

INSERT INTO `tareas` (`id`, `materia_id`, `docente_id`, `titulo`, `descripcion`, `archivo`, `fecha_publicacion`, `fecha_limite`) VALUES
(1, 2, 2, 'resumen', '', NULL, '2026-09-05 02:11:49', '2026-09-11 12:59:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `Id` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `Rol` enum('Administrador','Docente','Estudiante','NuevoIngreso') NOT NULL DEFAULT 'Estudiante',
  `especialidad` varchar(100) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `token_recuperacion` varchar(255) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `grupo` varchar(10) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `semestre` int(2) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `correo_institucional` varchar(100) DEFAULT NULL,
  `num_control` varchar(20) DEFAULT NULL,
  `validado` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`Id`, `Nombre`, `Correo`, `password`, `Rol`, `especialidad`, `foto_perfil`, `token_recuperacion`, `token_expiracion`, `grupo`, `telefono`, `direccion`, `fecha_nacimiento`, `semestre`, `carrera_id`, `fecha_registro`, `activo`, `correo_institucional`, `num_control`, `validado`) VALUES
(1, 'admin', 'admin@itsz.edu.mx', '$2y$10$bVHGE/I8RfGTMkhXHwE2S.z69HWHJoMaoi9VZUrMOMtliKesjeVSe', 'Administrador', NULL, NULL, '95a49584f738d1f440479c9480872ca04cddc7632244d0948ff023599de4cab3', '2026-09-08 14:27:35', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-04 22:41:49', 1, NULL, NULL, 0),
(2, 'docente', 'docente@itsz.edu.mx', '$2y$12$qOjzNsKNJArkLONKNX2PT.LyS802j6tbSpvo3hWLZoLN2zLYz93ya', 'Docente', 'Ingenieria en Sistemas, Ingenieria Electronica', NULL, NULL, NULL, '103A', NULL, NULL, NULL, 1, 1, '2026-09-04 22:41:49', 1, NULL, NULL, 0),
(3, 'estudiante', 'estudiante@itsz.edu.mx', '$2y$12$WLWsoy/XO7rzD7ZubgLU5eqaNXDHmIqVcxWhbdTRRxkl.naeb/xIG', 'Estudiante', NULL, NULL, NULL, NULL, '103A', NULL, NULL, NULL, 1, 1, '2026-09-04 22:41:49', 1, NULL, NULL, 0),
(6, 'javier fcg', '216w0563@zongolica.tecnm.mx', '$2y$12$OCrr03o7zuCgl6x8QsKDF.SQxvXKOFTIhXzoBxXKvU2HtlejVcMyu', 'Estudiante', NULL, 'uploads/foto_6_1788901902.png', NULL, NULL, '103A', NULL, NULL, NULL, 1, 1, '2026-09-05 01:16:50', 1, NULL, NULL, 0),
(29, 'Laura Gómez Flores', 'laura.gomez@gmail.com', '$2y$10$8xiV.nWL8dVf3g504sQoHek/XF.AzcKMqeZ4Z.bTtRP/ApMns2hsW', 'Estudiante', NULL, NULL, NULL, NULL, '201', '2721234572', 'Calle Zaragoza 987 Zongolica', NULL, 1, 7, '2026-09-14 15:55:39', 1, '216w0006@zongolica.tecnm.mx', '216w0006', 1),
(5, 'JAVIER FRANCISCO COLOHUA GALVEZ', 'jcolohuagalvez@gmail.com', '$2y$10$g9iipjL883LIF/oEwMVeRO8KWhoJLfV.kIgnA1Hjn7ETKapC/40PC', 'Administrador', NULL, 'uploads/foto_5_1788565569.png', NULL, NULL, '', NULL, NULL, NULL, 1, 0, '2026-09-04 23:41:23', 1, NULL, NULL, 0),
(9, 'Maria Jose', 'mariajoseduranrueda82@gmail.com', '$2y$10$St60PFOR4Dfe0XsJgXN6yuHTwd2AiFA0eviN5diI2DDUb/RybA0P6', 'Estudiante', NULL, NULL, NULL, NULL, '103A', NULL, NULL, NULL, 1, 1, '2026-09-05 01:59:51', 1, NULL, NULL, 0),
(10, 'Javi', 'jcolohuagalvezf@gmail.com', '$2y$12$cJut8m2nk38sZdXhXD9W/eOb0xQeoFSwidaaDT2VJvloJwkie7C.i', 'Administrador', NULL, 'uploads/foto_10_1788577788.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-05 03:08:28', 1, NULL, NULL, 0),
(11, 'cr7', '26w0006@zongolica.tecnm.mx', '$2y$10$eCXnXcfEsMzHNfC0OJXH9eLRLWnq53J9LMY6cT2qAM7PqDy2PX8Ni', 'Estudiante', NULL, 'uploads/foto_11_1788910120.jpg', NULL, NULL, '101', NULL, NULL, NULL, 1, 3, '2026-09-08 19:02:10', 1, '26w0006@zongolica.tecnm.mx', '26w0006', 0),
(12, 'adelita colohua duran', '26w0003@zongolica.tecnm.mx', '$2y$10$Dh0vHnlmuYgL8ptAFqpXROY8WZOGB7LwBvNgX3WUNnIT/1TB.5HM.', 'Estudiante', NULL, NULL, NULL, NULL, '101', '2721456789', 'norte 11 mun41 colonia san jose ixtaczoquitlan', '2003-05-22', 1, 3, '2026-09-10 15:56:18', 1, '26w0003@zongolica.tecnm.mx', '26w0003', 0),
(26, 'Carlos Rodrí­guez Ruiz', 'carlos.rodriguez@gmail.com', '$2y$10$SWMg3a3er2XtC1J.Nz/zmO2owS8uT6gT0TSuSXZONluCXVx61tWha', 'Estudiante', NULL, NULL, NULL, NULL, '102', '2721234569', 'Av. Reforma 789 Zongolica', NULL, 2, 6, '2026-09-14 15:55:39', 1, '216w0003@zongolica.tecnm.mx', '216w0003', 1),
(27, 'Ana Martí­nez Silva', 'ana.martinez@gmail.com', '$2y$10$.pL5xyFf7C.psqkcrjfm1eAdryYH8bqigva/Lb4T/fi0mffR/dw4G', 'Estudiante', NULL, NULL, NULL, NULL, '103A', '2721234570', 'Calle Morelos 321 Zongolica', NULL, 1, 6, '2026-09-14 15:55:39', 1, '216w0004@zongolica.tecnm.mx', '216w0004', 1),
(28, 'Pedro Sánchez Torres', 'pedro.sanchez@gmail.com', '$2y$10$8e9R2tCPTfJx/k9EmP363uXZWuNBiRiCOWkX1LFMmNY9NgtsD6ynS', 'Estudiante', NULL, NULL, NULL, NULL, '103A', '2721234571', 'Av. Independencia 654 Zongolica', NULL, 1, 6, '2026-09-14 15:55:39', 1, '216w0005@zongolica.tecnm.mx', '216w0005', 1),
(24, 'Juan Pérez Garcí­a', 'juan.perez@gmail.com', '$2y$10$sdv0emh0TQIwSYhMsexIY.eF9VNWi3IjF0./ytwKIf7wmKio9tDoa', 'Estudiante', NULL, NULL, NULL, NULL, '101', '2721234567', 'Av. JuÃ¡rez 123 Zongolica', NULL, 1, 6, '2026-09-14 15:55:39', 1, '216w0001@zongolica.tecnm.mx', '216w0001', 1),
(25, 'María López Hernández', 'maria.lopez@gmail.com', '$2y$10$UFZehe65N0Ga4KKokhp41OuL5lXbXkfeQRHPk/Xg08NOXc5n1ILQy', 'Estudiante', NULL, NULL, NULL, NULL, '101', '2721234568', 'Calle Hidalgo 456 Zongolica', NULL, 1, 6, '2026-09-14 15:55:39', 1, '216w0002@zongolica.tecnm.mx', '216w0002', 1),
(19, 'Dr. Roberto Méndez López', 'roberto.mendez@gmail.com', '$2y$10$YMuq0z.ptE8Kj4UHhPy7AO..Xz/jZCDNawr0n91t8BhULVVOo3M/2', 'Docente', 'IngenierÃ­a en Sistemas', NULL, NULL, NULL, NULL, '2721234573', 'Av. Universidad 100', NULL, NULL, NULL, '2026-09-14 15:50:33', 1, 'roberto.mendez@itsz.edu.mx', 'roberto.mendez', 1),
(20, 'Dra. Patricia Ruiz Garcí­a', 'patricia.ruiz@gmail.com', '$2y$10$G4/DG4XGD4/A1UHrEfpadOnqcSWiXmovunAJv/r7y1GeuIKZ26dVq', 'Docente', 'IngenierÃ­a ElectrÃ³nica', NULL, NULL, NULL, NULL, '2721234574', 'Calle TecnolÃ³gico 200', NULL, NULL, NULL, '2026-09-14 15:50:33', 1, 'patricia.ruiz@itsz.edu.mx', 'patricia.ruiz', 1),
(21, 'Mtro. Fernando Vargas Díaz', 'fernando.vargas@gmail.com', '$2y$10$fbR54KWmIXtN89Z.qpzJb.xLKvwxT9M7zwdBKJVPnUjwfGXXQcaLe', 'Docente', 'IngenierÃ­a Civil', NULL, NULL, NULL, NULL, '2721234575', 'Av. Central 300', NULL, NULL, NULL, '2026-09-14 15:50:34', 1, 'fernando.vargas@itsz.edu.mx', 'fernando.vargas', 1),
(22, 'Mtra. Claudia Romero Silva', 'claudia.romero@gmail.com', '$2y$10$CHOu7hTzOwKy631iU/g.J.35lnFgulewD3YFlgFy5ovmw8cq5C2zq', 'Docente', 'IngenierÃ­a Industrial', NULL, NULL, NULL, NULL, '2721234576', 'Calle Principal 400', NULL, NULL, NULL, '2026-09-14 15:50:34', 1, 'claudia.romero@itsz.edu.mx', 'claudia.romero', 1),
(23, 'Ing. Alejandro Castro Núñez', 'alejandro.castro@gmail.com', '$2y$10$bYRiADBUioy3AnpPJI1CceINcO1u.XDP60jtAwRkp.iN463O/WzJm', 'Docente', 'IngenierÃ­a MecatrÃ³nica', NULL, NULL, NULL, NULL, '2721234577', 'Av. Reforma 500', NULL, NULL, NULL, '2026-09-14 15:50:34', 1, 'alejandro.castro@itsz.edu.mx', 'alejandro.castro', 1),
(30, 'Diego Ramí­rez Cruz', 'diego.ramirez@gmail.com', '$2y$10$yFLILvBeQ/MeNAOPekXFCOxv6ZJYlerm8rAUsXQlluuzTckQ4VES2', 'Estudiante', NULL, NULL, NULL, NULL, '201', '2721234573', 'Av. CuauhtÃ©moc 111 Zongolica', NULL, 1, 7, '2026-09-14 15:55:39', 1, '216w0007@zongolica.tecnm.mx', '216w0007', 1),
(31, 'Sofí­a Torres Mendoza', 'sofia.torres@gmail.com', '$2y$10$HL4FDegDMWc1LZ9kVwecQu4DEU8gVm7ig1eI0TJz/FLiO9qYb6EZu', 'Estudiante', NULL, NULL, NULL, NULL, '202', '2721234574', 'Calle 5 de Mayo 222 Zongolica', NULL, 2, 7, '2026-09-14 15:55:40', 1, '216w0008@zongolica.tecnm.mx', '216w0008', 1),
(32, 'Roberto Flores Jiménez', 'roberto.flores@gmail.com', '$2y$10$Irqx4TKmrQ1btTmvJesnHO306nIDmu.HbuAThFvMgcuI4Mdzj1VY2', 'Estudiante', NULL, NULL, NULL, NULL, '301', '2721234575', 'Av. Benito JuÃ¡rez 333 Zongolica', NULL, 1, 8, '2026-09-14 15:55:40', 1, '216w0009@zongolica.tecnm.mx', '216w0009', 1),
(33, 'Fernanda Cruz Rojas', 'fernanda.cruz@gmail.com', '$2y$10$BqfcxJtc5VQsOuHUIsoxPeOjHD8qW4HnwC5Wpxx37sIAsF3LHJ8se', 'Estudiante', NULL, NULL, NULL, NULL, '302', '2721234576', 'Calle Allende 444 Zongolica', NULL, 2, 8, '2026-09-14 15:55:40', 1, '216w0010@zongolica.tecnm.mx', '216w0010', 1),
(34, 'Miguel Ángel Reyes', 'miguel.reyes@gmail.com', '$2y$10$YZ/An1PYJXYFWHEYaXHzXebZrmWJU/Lmhqfp98zSyRbqgk52n7CY.', 'Estudiante', NULL, NULL, NULL, NULL, '401', '2721234577', 'Av. Madero 555 Zongolica', NULL, 1, 9, '2026-09-14 15:55:40', 1, '216w0011@zongolica.tecnm.mx', '216w0011', 1),
(35, 'Valeria Morales Luna', 'valeria.morales@gmail.com', '$2y$10$afRNkspAPaDrg7aUO0Cxx.JTFfvov.H1nmDiweQBFzDnbFMzDmJwy', 'Estudiante', NULL, NULL, NULL, NULL, '501', '2721234578', 'Calle Guerrero 666 Zongolica', NULL, 1, 10, '2026-09-14 15:55:40', 1, '216w0012@zongolica.tecnm.mx', '216w0012', 1),
(36, 'javier francisco colohua galvez', 'colohuagalvezj@gmail.com', '$2y$10$NjGndtlBE4eB2OBz3o6ek.NX3.qUXTh88kQrOuhxPDNLMNNygyyha', 'NuevoIngreso', NULL, NULL, NULL, NULL, NULL, '2722359431', 'norte 11 mun41 colonia san jose ixtaczoquitlan', '2003-12-03', NULL, NULL, '2026-09-18 18:45:47', 1, NULL, NULL, 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `anuncios`
--
ALTER TABLE `anuncios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `autor_id` (`autor_id`);

--
-- Indices de la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `becas`
--
ALTER TABLE `becas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `becas_solicitudes`
--
ALTER TABLE `becas_solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `beca_id` (`beca_id`);

--
-- Indices de la tabla `carreras`
--
ALTER TABLE `carreras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `docentes_especialidades`
--
ALTER TABLE `docentes_especialidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `docente_id` (`docente_id`),
  ADD KEY `carrera_id` (`carrera_id`);

--
-- Indices de la tabla `documentos_alumnos`
--
ALTER TABLE `documentos_alumnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `entregas`
--
ALTER TABLE `entregas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tarea_id` (`tarea_id`),
  ADD KEY `estudiante_id` (`estudiante_id`);

--
-- Indices de la tabla `entregas_tareas`
--
ALTER TABLE `entregas_tareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_tarea` (`id_tarea`),
  ADD KEY `id_estudiante` (`id_estudiante`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carrera_id` (`carrera_id`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materia_id` (`materia_id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `periodo_id` (`periodo_id`);

--
-- Indices de la tabla `horarios_detalle`
--
ALTER TABLE `horarios_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materia_id` (`materia_id`),
  ADD KEY `aula_id` (`aula_id`);

--
-- Indices de la tabla `importaciones`
--
ALTER TABLE `importaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `carrera_id` (`carrera_id`),
  ADD KEY `periodo_id` (`periodo_id`);

--
-- Indices de la tabla `inscripciones_detalle`
--
ALTER TABLE `inscripciones_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inscripcion_id` (`inscripcion_id`),
  ADD KEY `materia_id` (`materia_id`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`),
  ADD KEY `carrera_id` (`carrera_id`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `remitente_id` (`remitente_id`),
  ADD KEY `destinatario_id` (`destinatario_id`);

--
-- Indices de la tabla `metas`
--
ALTER TABLE `metas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`);

--
-- Indices de la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `libro_id` (`libro_id`),
  ADD KEY `estudiante_id` (`estudiante_id`);

--
-- Indices de la tabla `solicitudes_becas`
--
ALTER TABLE `solicitudes_becas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_estudiante` (`id_estudiante`),
  ADD KEY `id_beca` (`id_beca`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materia_id` (`materia_id`),
  ADD KEY `docente_id` (`docente_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `Correo` (`Correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `anuncios`
--
ALTER TABLE `anuncios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `becas`
--
ALTER TABLE `becas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `becas_solicitudes`
--
ALTER TABLE `becas_solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `docentes_especialidades`
--
ALTER TABLE `docentes_especialidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `documentos_alumnos`
--
ALTER TABLE `documentos_alumnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `entregas`
--
ALTER TABLE `entregas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entregas_tareas`
--
ALTER TABLE `entregas_tareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `horarios_detalle`
--
ALTER TABLE `horarios_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `importaciones`
--
ALTER TABLE `importaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `inscripciones_detalle`
--
ALTER TABLE `inscripciones_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `libros`
--
ALTER TABLE `libros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `metas`
--
ALTER TABLE `metas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `periodos`
--
ALTER TABLE `periodos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_becas`
--
ALTER TABLE `solicitudes_becas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tareas`
--
ALTER TABLE `tareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
