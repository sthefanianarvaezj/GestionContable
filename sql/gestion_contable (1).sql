-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-05-2025 a las 05:15:25
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestion_contable`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `tipo_documento` varchar(50) DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre_completo`, `tipo_documento`, `numero_documento`, `direccion`, `telefono`, `email`, `fecha_registro`) VALUES
(1, 'Sthefania', 'CC', '110233344', 'cra 234', '30404041', 'sthefi094@hotmail.com', '2025-05-16 02:43:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estados`
--

CREATE TABLE `historial_estados` (
  `id` int(11) NOT NULL,
  `trabajo_id` int(11) NOT NULL,
  `estado_anterior` enum('recibido','en_fabricacion','entregado') DEFAULT NULL,
  `estado_nuevo` enum('recibido','en_fabricacion','entregado') DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_estados`
--

INSERT INTO `historial_estados` (`id`, `trabajo_id`, `estado_anterior`, `estado_nuevo`, `fecha_cambio`) VALUES
(1, 1, 'recibido', 'en_fabricacion', '2025-04-21 00:59:18'),
(2, 1, 'en_fabricacion', 'entregado', '2025-04-21 01:16:15'),
(3, 2, 'recibido', 'en_fabricacion', '2025-04-21 01:38:27'),
(4, 2, 'en_fabricacion', 'entregado', '2025-04-21 01:39:17'),
(5, 3, 'recibido', 'en_fabricacion', '2025-04-21 01:40:14'),
(6, 4, 'recibido', 'en_fabricacion', '2025-05-16 03:14:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `trabajo_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `fecha_pago` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `trabajo_id`, `monto`, `metodo_pago`, `fecha_pago`, `observaciones`, `fecha_creacion`) VALUES
(1, 1, 100000.00, 'efectivo', '2025-04-21', '', '2025-04-21 01:10:13'),
(2, 1, 19900000.00, 'efectivo', '2025-04-21', '', '2025-04-21 01:16:06'),
(3, 2, 1000000.00, 'efectivo', '2025-04-21', 'Pago abono', '2025-04-21 01:38:08'),
(4, 2, 98999999.99, 'tarjeta', '2025-04-21', 'Pago total', '2025-04-21 01:39:02'),
(5, 3, 10000.00, 'efectivo', '2025-04-21', '', '2025-04-21 01:40:38'),
(6, 4, 200000.00, 'efectivo', '2025-05-16', '', '2025-05-16 03:15:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_parciales`
--

CREATE TABLE `pagos_parciales` (
  `id` int(11) NOT NULL,
  `trabajo_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `nombre_permiso` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `nombre_permiso`, `descripcion`) VALUES
(4, 'gestionar_clientes', 'Permite administrar la información de clientes.'),
(5, 'gestionar_roles_permisos', 'Permite administrar roles y asignar permisos a roles.'),
(2, 'gestionar_trabajos', 'Permite crear, ver, editar y eliminar trabajos contables.'),
(1, 'gestionar_usuarios', 'Permite crear, ver, editar y eliminar usuarios y sus roles.'),
(6, 'registrar_pagos', 'Permite registrar pagos para los trabajos.'),
(3, 'ver_dashboard', 'Permite acceder al dashboard principal.'),
(7, 'ver_reportes', 'Permite acceder a reportes financieros o de gestión.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre_rol`) VALUES
(1, 'Administrador'),
(2, 'Usuario Estándar');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `rol_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol_permisos`
--

INSERT INTO `rol_permisos` (`rol_id`, `permiso_id`) VALUES
(2, 4),
(2, 2),
(2, 6),
(2, 3),
(2, 7),
(1, 4),
(1, 2),
(1, 1),
(1, 6),
(1, 3),
(1, 7),
(1, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trabajos_contables`
--

CREATE TABLE `trabajos_contables` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `nombre_cliente` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `abonos_realizados` decimal(10,2) DEFAULT 0.00,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `requiere_factura` tinyint(1) DEFAULT 0,
  `numero_factura` varchar(50) DEFAULT NULL,
  `estado` enum('recibido','en_fabricacion','entregado') DEFAULT 'recibido',
  `fecha_cambio_estado` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `trabajos_contables`
--

INSERT INTO `trabajos_contables` (`id`, `cliente_id`, `fecha_ingreso`, `fecha_inicio`, `nombre_cliente`, `descripcion`, `valor_total`, `abonos_realizados`, `saldo_pendiente`, `requiere_factura`, `numero_factura`, `estado`, `fecha_cambio_estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, NULL, '2025-04-21', NULL, 'Alejandro Duran', 'TRABAJO PESADO', 20000000.00, 0.00, 0.00, 0, '', 'entregado', '2025-04-21 01:16:15', '2025-04-21 00:39:38', '2025-04-21 01:16:15'),
(2, 1, '2025-04-21', NULL, 'Sthefania narvaez', 'trabajo de construccion', 99999999.99, 0.00, 0.00, 1, '20001', 'entregado', '2025-04-21 01:39:17', '2025-04-21 01:35:42', '2025-05-16 03:08:03'),
(3, NULL, '2025-04-21', NULL, 'sthefania', 'carro', 100000.00, 0.00, 90000.00, 0, '', 'en_fabricacion', '2025-04-21 01:40:14', '2025-04-21 01:39:50', '2025-04-21 01:40:38'),
(4, 1, '2025-05-15', NULL, '', 'Prueba de ingreso', 200000.00, 0.00, 0.00, 1, '223333', 'en_fabricacion', '2025-05-16 03:14:45', '2025-05-16 03:14:38', '2025-05-16 03:15:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol_id`, `fecha_registro`) VALUES
(1, 'Alejo', 'alejoduran@example.com', '$2y$10$x8cGqizj1Hso2DHyFW8S1uMwZMSO.2r51MdmDmEkBHUplb/BASDO6', 1, '2025-04-21 00:03:43'),
(2, 'Sthefania', 'sthefi@example.com', '$2y$10$eVMbPsznVvEh3mVQo4zz0.lQjrIcbsCCMWMRhXPyDNsmzuAMdVwb6', 2, '2025-04-21 00:06:12'),
(3, 'Alejandro', 'alejo@example.com', '$2y$10$wPe5LAhWH3NeJnIw9uDOROxTP6qN34/mXnpiRYppcuOq9tW1wUJFi', 1, '2025-05-16 01:59:47'),
(4, 'Sthefa2', 'sthefi0@example.com', '$2y$10$p13gqd.RRCak7oz4AG.So.nxQTQrMHdZ08hxqIF4gNvCzJxMVCjdy', 2, '2025-05-16 02:08:22');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_documento` (`numero_documento`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trabajo_id` (`trabajo_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trabajo_id` (`trabajo_id`);

--
-- Indices de la tabla `pagos_parciales`
--
ALTER TABLE `pagos_parciales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trabajo_id` (`trabajo_id`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD UNIQUE KEY `nombre_permiso` (`nombre_permiso`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `trabajos_contables`
--
ALTER TABLE `trabajos_contables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trabajo_cliente` (`cliente_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pagos_parciales`
--
ALTER TABLE `pagos_parciales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `trabajos_contables`
--
ALTER TABLE `trabajos_contables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD CONSTRAINT `historial_estados_ibfk_1` FOREIGN KEY (`trabajo_id`) REFERENCES `trabajos_contables` (`id`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`trabajo_id`) REFERENCES `trabajos_contables` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos_parciales`
--
ALTER TABLE `pagos_parciales`
  ADD CONSTRAINT `pagos_parciales_ibfk_1` FOREIGN KEY (`trabajo_id`) REFERENCES `trabajos_contables` (`id`);

--
-- Filtros para la tabla `trabajos_contables`
--
ALTER TABLE `trabajos_contables`
  ADD CONSTRAINT `fk_trabajo_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
