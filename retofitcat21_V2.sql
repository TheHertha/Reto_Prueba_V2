-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-07-2025 a las 04:17:32
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `retofitcat21`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciclos`
--

CREATE TABLE `ciclos` (
  `id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciclos`
--

INSERT INTO `ciclos` (`id`, `start_date`, `end_date`, `created_at`) VALUES
(1, '2025-07-01', '2025-07-10', '2025-07-06 00:13:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `coaches`
--

CREATE TABLE `coaches` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `coaches`
--

INSERT INTO `coaches` (`id`, `nombre`) VALUES
(1, 'Coach Juan'),
(2, 'Coach María'),
(3, 'Coach Pedro');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_semanales`
--

CREATE TABLE `datos_semanales` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `semana` int(11) NOT NULL,
  `estatura` decimal(5,1) DEFAULT NULL,
  `peso` decimal(5,1) DEFAULT NULL,
  `masa` decimal(5,1) DEFAULT NULL,
  `grasa` decimal(5,1) DEFAULT NULL,
  `musculo` decimal(5,1) DEFAULT NULL,
  `image` text DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `ciclo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `datos_semanales`
--

INSERT INTO `datos_semanales` (`id`, `usuario_id`, `semana`, `estatura`, `peso`, `masa`, `grasa`, `musculo`, `image`, `creado_en`, `ciclo_id`) VALUES
(43, 12, 0, 170.0, 70.0, 60.0, 20.0, 40.0, NULL, '2025-07-05 19:08:46', 1),
(44, 12, 3, 170.0, 68.0, 60.0, 18.0, 42.0, NULL, '2025-07-05 19:09:23', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `imagen` varchar(255) DEFAULT 'default.jpg',
  `enlace_zoom` varchar(500) DEFAULT NULL,
  `enlace_youtube` varchar(500) DEFAULT NULL,
  `enlace_facebook` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `nombre`, `descripcion`, `fecha`, `hora`, `imagen`, `enlace_zoom`, `enlace_youtube`, `enlace_facebook`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(2, 'Uno', '1', '2025-06-28', '19:44:00', '1750628509_eje.jpg', 'https://photocolorpicker.com/es', 'https://photocolorpicker.com/es', 'https://photocolorpicker.com/es', 1, '2025-06-22 21:41:49', '2025-06-22 21:41:49'),
(3, 'a', 'a', '2025-07-15', '18:38:00', '1751751500_gato.jpeg', 'https://www.youtube.com/', 'https://www.youtube.com/', 'https://www.youtube.com/', 1, '2025-07-05 21:38:20', '2025-07-05 21:38:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `generos`
--

CREATE TABLE `generos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `generos`
--

INSERT INTO `generos` (`id`, `nombre`) VALUES
(1, 'Masculino'),
(2, 'Femenino'),
(3, 'Otro');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paises`
--

CREATE TABLE `paises` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paises`
--

INSERT INTO `paises` (`id`, `nombre`) VALUES
(1, 'México'),
(2, 'Estados Unidos'),
(3, 'España'),
(4, 'Argentina'),
(5, 'Colombia');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ranking`
--

CREATE TABLE `ranking` (
  `id` int(11) NOT NULL,
  `ciclo_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido_paterno` varchar(255) NOT NULL,
  `promedio_avance` decimal(10,2) NOT NULL,
  `puesto` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ranking`
--

INSERT INTO `ranking` (`id`, `ciclo_id`, `usuario_id`, `nombre`, `apellido_paterno`, `promedio_avance`, `puesto`, `created_at`) VALUES
(8, 1, 12, 'juan', 'juan', 2.00, 1, '2025-07-06 01:09:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tokens`
--

CREATE TABLE `tokens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tokens`
--

INSERT INTO `tokens` (`id`, `usuario_id`, `token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
(1, 6, 'bc54652dd56db211b03537a2b3f7dfc2aadaa0c1d35958a3787a3a2576cf5017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-04 05:30:02', '2025-08-03 13:30:02'),
(2, 6, 'f3a85fe26c1b671328e998db3bc2f08cd097507a5a86831c09596a44af32abe9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-04 05:34:46', '2025-08-03 13:34:46'),
(3, 6, '2692e71df7e4ae320a9ce07838ef2b2dfe6d0b3d406c03f411f691c7f451a6cd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-04 05:35:45', '2025-08-03 13:35:45'),
(4, 6, '6d697816e74ec3f3417d2ccd64c19d3e6c762e50a5d04d7d615a8bdf2d7d43aa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-04 05:39:17', '2025-08-03 13:39:17'),
(5, 6, '313f1483fb2f26531502639b97b8d73c97ba741a7f8f0de8d51e7a9ad6d601d6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-04 05:47:15', '2025-08-03 13:47:15'),
(7, 6, '8c0d2965e98ccd0f50dc2334ff051c93f1cf6118cfef8f3d79cfecc8c1e510e7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-04 05:54:01', '2025-08-03 13:54:01'),
(8, 6, 'c281c7d9483ee10b27c463fbb17ec91da5f019986defc48a12ba3431e62e6037', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-04 05:56:46', '2025-08-03 13:56:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `genero` varchar(20) NOT NULL,
  `pais` varchar(50) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `id_herbalife` varchar(100) NOT NULL,
  `seleccion_couch` varchar(100) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `rol` enum('admin','user') DEFAULT 'user',
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `contrasena`, `nombre`, `apellido_paterno`, `apellido_materno`, `fecha_nacimiento`, `genero`, `pais`, `telefono`, `id_herbalife`, `seleccion_couch`, `fecha_registro`, `rol`, `foto_perfil`) VALUES
(4, 'le@sserafim.com', '$2y$10$5mek4G/PLNiSTSBpnhPv2OkA72bMsCD3pwL061DiJlEHvw3nnS5mm', 'alex', 'a', 'a', '2025-06-26', 'masculino', 'mexico', '5577', '111', 'coach1', '2025-06-06 07:54:01', 'user', NULL),
(5, 'ITZY@gmail.com', '$2y$10$xe02tMoPIKtGkdmWgRlq/eBdPR539qAulzA6ZNK6H5YH3UojrVSni', 'ITZY', 'A', 'B', '2025-06-10', 'femenino', 'canada', '8855577458', '111', 'coach1', '2025-06-22 12:32:19', 'user', NULL),
(6, 'hola@gmail.com', '$2y$10$Zasw5yisgTPL4JR7Q2DQtuZ3iYgoJgl2F2w9XWJUJoygquvf49Q4u', 'Hola', 'Hola', 'Hola', '2000-01-07', 'masculino', 'mexico', '1234567890', '00000', 'coach1', '2025-07-04 05:30:02', 'user', NULL),
(7, 'a@a.com', '$2y$10$RS4msCkol2XN4VtZ3oVrdOJKKm7fd5VNa8ujcvT431hwPaA4jQtL.', 'a', 'a', 'a', '2002-01-24', 'masculino', 'mexico', '1234567890', '000000', 'coach1', '2025-07-04 07:09:01', 'user', NULL),
(8, 'iznanana@flop.com', '$2y$10$u0bwKTJ1Omm16k0WuFdm4ewZwOiiiOD9QOGWo4U4aJiMaYFKwv6Tu', 'Alex', 'Gui', 'Aisx', '2001-01-18', 'masculino', 'mexico', '5589621374', '111111', 'coach2', '2025-07-05 01:18:36', 'user', NULL),
(9, 'b@b.com', '$2y$10$jpV6Rz1iPEgxXdQJpnAxcu0/wFsp9Z4R3yD0.XWkaSTYzkT0bUfp.', 'b', 'b', 'b', '1997-06-19', 'Masculino', 'México', '1234567890', '2222222222222', 'Coach Juan', '2025-07-05 20:23:48', 'admin', NULL),
(10, 'c@c.com', '$2y$10$4yKkg7kVMurm9Z0ZffthMuq0QWyeEcaaGXlW5BdemCMyJFSogXu2.', 'c', 'c', 'c', '2000-01-06', 'Masculino', 'México', '1234567890', '8888888888888', 'Coach Pedro', '2025-07-05 21:02:09', 'user', NULL),
(11, 'd@d.com', '$2y$10$M0LtCENKdpuM79dz4W0a5.RwsRmz5ZdmH2S4d9sNf5bhScQ9P9gYe', 'd', 'd', 'd', '1997-06-03', 'Masculino', 'México', '1234567890', '9999999999', 'Coach Pedro', '2025-07-05 21:11:49', 'admin', '1751752123_gato.jpeg'),
(12, 'juan@gmail.com', '$2y$10$E4m5kRllNX33r8QLvFeTZ.bm99Z.r/lYg3a8omvLoo19cJp33K4Ka', 'juan', 'juan', 'juan', '1990-01-24', 'Masculino', 'Estados Unidos', '1234567890', '5555555555555', 'Coach María', '2025-07-05 21:33:16', 'user', '1751755131_Captura de pantalla 2025-07-05 163807.png'),
(100, '', '', 'Juan', 'Perez', '', '1990-01-01', '', '', '', '', '', '2025-07-06 00:24:26', 'user', NULL),
(200, '', '', 'Maria', 'Lopez', '', '1985-05-10', '', '', '', '', '', '2025-07-06 00:24:26', 'user', NULL),
(300, '', '', 'Carlos', 'Gomez', '', '1995-03-15', '', '', '', '', '', '2025-07-06 00:24:26', 'user', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ciclos`
--
ALTER TABLE `ciclos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `coaches`
--
ALTER TABLE `coaches`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `datos_semanales`
--
ALTER TABLE `datos_semanales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`,`semana`),
  ADD KEY `fk_datos_ciclo` (`ciclo_id`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `generos`
--
ALTER TABLE `generos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `paises`
--
ALTER TABLE `paises`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ciclo_id` (`ciclo_id`,`puesto`),
  ADD UNIQUE KEY `ciclo_id_2` (`ciclo_id`,`usuario_id`),
  ADD UNIQUE KEY `ciclo_id_3` (`ciclo_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_token` (`token`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ciclos`
--
ALTER TABLE `ciclos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `coaches`
--
ALTER TABLE `coaches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `datos_semanales`
--
ALTER TABLE `datos_semanales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `generos`
--
ALTER TABLE `generos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `paises`
--
ALTER TABLE `paises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `ranking`
--
ALTER TABLE `ranking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=301;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `datos_semanales`
--
ALTER TABLE `datos_semanales`
  ADD CONSTRAINT `datos_semanales_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_datos_ciclo` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD CONSTRAINT `ranking_ibfk_1` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ranking_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `fk_tokens_usuario_id` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
