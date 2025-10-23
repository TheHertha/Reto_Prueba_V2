<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor, inicia sesión para acceder a la página de inicio.";
    header("Location: login.php");
    exit;
}

$nombre = htmlspecialchars($_SESSION['nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - CAT 21</title>
    <link rel="stylesheet" href="assets/css/inicio.css">
    <style>
/* Reinicia márgenes, paddings y configura box-sizing para consistencia */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Configura el cuerpo de la página con fuente moderna y altura mínima */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #ffffff;
    color: #000000;
    min-height: 100vh;
    line-height: 1.6;
}

/* Encabezado con disposición flexible y fondo oscuro */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 40px;
    background-color: #000000;
    height: 80px;
    position: relative;
}

/* Logo en el encabezado */
.logo {
    height: 60px;
    width: 60px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 20px;
}

/* Título centrado en el encabezado */
.title {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    text-transform: uppercase;
    font-size: 24px;
    letter-spacing: 2px;
}

/* Contenedor de los botones en la derecha del encabezado */
.header-right {
    display: flex;
    gap: 15px;
}

/* Estilo de los botones de perfil y cerrar sesión */
.profile-btn,
.logout-btn {
    background: transparent;
    color: #ffffff;
    border: 1px solid #ffffff;
    padding: 10px 20px;
    border-radius: 4px;
    font-size: 14px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.3s ease;
}

/* Efecto hover para los botones de perfil y cerrar sesión */
.profile-btn:hover,
.logout-btn:hover {
    background-color: #ffffff;
    color: #000000;
}

/* Contenedor de la navegación */
.navigation {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin: 40px 0;
}

/* Estilo de los botones de navegación */
.nav-btn {
    position: relative;
    overflow: hidden;
    border: 1px solid black;
    padding: 14px 28px;
    text-transform: uppercase;
    background: transparent;
    color: #000;
    text-decoration: none;
    transition: color 0.4s ease;
}

/* Efecto de fondo para los botones de navegación */
.nav-btn::before {
    content: "";
    position: absolute;
    left: -100%;
    top: 0;
    height: 100%;
    width: 100%;
    background-color: black;
    z-index: 0;
    transition: left 0.4s ease;
}

/* Efecto hover para el fondo de los botones de navegación */
.nav-btn:hover::before {
    left: 0;
}

/* Efecto hover para el color del texto en los botones de navegación */
.nav-btn:hover {
    color: white;
}

/* Contenido de los botones de navegación */
.nav-btn span {
    position: relative;
    z-index: 1;
}

/* Contenedor del carrusel */
.carousel-container {
    margin: 40px auto;
    max-width: 90%;
    height: 60vh;
    background: #f8f8f8;
    border: 1px solid #333;
    border-radius: 8px;
    overflow: hidden;
}

/* Pie de página minimalista */
.footer-minimal {
    text-align: center;
    padding: 30px;
    border-top: 1px solid #e0e0e0;
    margin-top: 30px;
}

/* Fondos personalizados para los primeros 3 slides del carrusel */
.carousel-slide.slide1 {
    background-image: url('assets/img/AF1.png');
    background-size: cover;
    background-position: center;
}
.carousel-slide.slide2 {
    background-image: url('assets/img/img2.png');
    background-size: cover;
    background-position: center;
}
.carousel-slide.slide3 {
    background-image: url('assets/img/img3.jpg');
    background-size: cover;
    background-position: center;
}

.carousel-slide.slide4 {
    background-image: url('assets/img/img4.jpg');
    background-size: cover;
    background-position: center;
}
.carousel-slide.slide5 {
    background-image: url('assets/img/img5.png');
    background-size: cover;
    background-position: center;
}

/* Texto blanco para el contenido de los slides del carrusel */
.carousel-slide .slide-content,
.carousel-slide .slide-title,
.carousel-slide .slide-subtitle {
    color: #fff;
    text-shadow: 1px 1px 8px rgba(0,0,0,0.5);
}



/* Texto del pie de página */
.footer-minimal p {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
}

/* Media query para pantallas de hasta 768px */
@media (max-width: 768px) {
    /* Ajusta el encabezado para tablets y móviles grandes */
    .header {
        padding: 10px 20px;
        height: 70px;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    /* Reduce el tamaño del logo */
    .logo {
        height: 50px;
        width: 50px;
        margin-right: 10px;
    }

    /* Ajusta el título para que sea flexible y centrado */
    .title {
        position: static;
        transform: none;
        font-size: 20px;
        letter-spacing: 1.5px;
        text-align: center;
        flex: 1;
    }

    /* Reduce el espacio entre los botones del encabezado */
    .header-right {
        gap: 10px;
    }

    /* Ajusta los botones de perfil y cerrar sesión */
    .profile-btn,
    .logout-btn {
        padding: 8px 15px;
        font-size: 12px;
    }

    /* Navegación en columna para mejor usabilidad */
    .navigation {
        flex-direction: column;
        gap: 15px;
        margin: 30px 0;
    }

    /* Ajusta los botones de navegación */
    .nav-btn {
        padding: 12px 24px;
        font-size: 13px;
    }

    /* Reduce la altura del carrusel */
    .carousel-container {
        height: 50vh;
        max-width: 95%;
    }

    /* Ajusta el pie de página */
    .footer-minimal {
        padding: 20px;
    }
}

/* Media query para pantallas de hasta 480px */
@media (max-width: 480px) {
    /* Ajusta el encabezado para móviles estándar */
    .header {
        padding: 8px 15px;
        height: auto;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    /* Reduce aún más el tamaño del logo */
    .logo {
        height: 40px;
        width: 40px;
        margin-right: 0;
    }

    /* Ajusta el título del encabezado */
    .title {
        font-size: 18px;
        letter-spacing: 1px;
        margin: 5px 0;
    }

    /* Ajusta los botones del encabezado */
    .header-right {
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

    /* Reduce el tamaño de los botones de perfil y cerrar sesión */
    .profile-btn,
    .logout-btn {
        padding: 6px 12px;
        font-size: 11px;
    }

    /* Ajusta la navegación */
    .navigation {
        margin: 20px 0;
        gap: 10px;
    }

    /* Reduce el tamaño de los botones de navegación */
    .nav-btn {
        padding: 10px 20px;
        font-size: 12px;
    }

    /* Reduce la altura del carrusel */
    .carousel-container {
        height: 40vh;
        max-width: 100%;
    }

    /* Ajusta el pie de página */
    .footer-minimal {
        padding: 15px;
    }

    /* Reduce el tamaño del texto del pie de página */
    .footer-minimal p {
        font-size: 11px;
    }
}

/* Media query para pantallas muy pequeñas (hasta 320px) */
@media (max-width: 320px) {
    /* Ajusta el encabezado para dispositivos muy pequeños */
    .header {
        padding: 6px 10px;
        gap: 8px;
    }

    /* Reduce aún más el tamaño del logo */
    .logo {
        height: 35px;
        width: 35px;
    }

    /* Ajusta el título del encabezado */
    .title {
        font-size: 16px;
        letter-spacing: 0.8px;
    }

    /* Reduce el espacio entre los botones del encabezado */
    .header-right {
        gap: 6px;
    }

    /* Reduce aún más el tamaño de los botones de perfil y cerrar sesión */
    .profile-btn,
    .logout-btn {
        padding: 5px 10px;
        font-size: 10px;
    }

    /* Ajusta la navegación */
    .navigation {
        margin: 15px 0;
        gap: 8px;
    }

    /* Reduce el tamaño de los botones de navegación */
    .nav-btn {
        padding: 8px 16px;
        font-size: 11px;
    }

    /* Reduce la altura del carrusel */
    .carousel-container {
        height: 35vh;
    }

    /* Ajusta el pie de página */
    .footer-minimal {
        padding: 10px;
    }

    /* Reduce el tamaño del texto del pie de página */
    .footer-minimal p {
        font-size: 10px;
    }
}
    </style>
</head>
<body>
<header class="header">
    <div style="display: flex; align-items: center;">
        <img src="assets/img/1-4.png" alt="Logo" class="logo">
    </div>
    <h1 class="title">Inicio</h1>
    <div class="header-right">
        <a class="profile-btn" href="perfil.php">Perfil de <?php echo $nombre; ?></a>
        <a class="logout-btn" href="logout.php">Cerrar Sesión</a>
    </div>
</header>

<nav class="navigation">
    <a class="nav-btn" href="reto.php"><span>Reto</span></a>
    <a class="nav-btn" href="ranking.php"><span>Ranking</span></a>
    <a class="nav-btn" href="eventos.php"><span>Eventos</span></a>
    <a class="nav-btn" href="productos.php"><span>Productos</span></a>
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
        <a class="nav-btn" href="admin_reto.php"><span>Administrador</span></a>
    <?php endif; ?>

</nav>

    <div class="carousel-container">
        <div class="carousel">
            <div class="carousel-slide active slide1">
                <div class="slide-content">
                    <h2 class="slide-title">Bienvenido, <?php echo $nombre; ?></h2>
                    <p class="slide-subtitle">Explora nuestra plataforma</p>
                </div>
            </div>
            <div class="carousel-slide slide2">
                <div class="slide-content">
                    <h2 class="slide-title">Desafía</h2>
                    <p class="slide-subtitle">Supera tus límites</p>
                </div>
            </div>
            <div class="carousel-slide slide3">
                <div class="slide-content">
                    <h2 class="slide-title">Compite</h2>
                    <p class="slide-subtitle">Alcanza la cima</p>
                </div>
            </div>
            <div class="carousel-slide slide4">
                <div class="slide-content">
                    <h2 class="slide-title">Participa</h2>
                    <p class="slide-subtitle">Únete a los eventos</p>
                </div>
            </div>
            <div class="carousel-slide slide5">
                <div class="slide-content">
                    <h2 class="slide-title">Conecta</h2>
                    <p class="slide-subtitle">Forma parte de la comunidad</p>
                </div>
            </div>
            <div class="carousel-slide slide6">
                <div class="slide-content">
                    <h2 class="slide-title">Descubre</h2>
                    <p class="slide-subtitle">Explora nuevos retos</p>
                </div>
            </div>
            <div class="carousel-slide slide7">
                <div class="slide-content">
                    <h2 class="slide-title">Gana</h2>
                    <p class="slide-subtitle">Consigue recompensas</p>
                </div>
            </div>
            <div class="carousel-slide slide8">
                <div class="slide-content">
                    <h2 class="slide-title">Aprende</h2>
                    <p class="slide-subtitle">Mejora tus habilidades</p>
                </div>
            </div>
            <div class="carousel-slide slide9">
                <div class="slide-content">
                    <h2 class="slide-title">Innova</h2>
                    <p class="slide-subtitle">Crea y comparte</p>
                </div>
            </div>
            <div class="carousel-slide slide10">
                <div class="slide-content image-slide">
                    <img src="assets/img/carousel-image.png" alt="Destacado" class="slide-image">
                    <h2 class="slide-title">Destacado</h2>
                    <p class="slide-subtitle">Únete a la experiencia</p>
                </div>
            </div>
        </div>

        <button class="carousel-nav prev" onclick="changeSlide(-1)">←</button>
        <button class="carousel-nav next" onclick="changeSlide(1)">→</button>

        <div class="carousel-indicators">
            <div class="indicator active" onclick="currentSlide(1)"></div>
            <div class="indicator" onclick="currentSlide(2)"></div>
            <div class="indicator" onclick="currentSlide(3)"></div>
            <div class="indicator" onclick="currentSlide(4)"></div>
            <div class="indicator" onclick="currentSlide(5)"></div>
            <div class="indicator" onclick="currentSlide(6)"></div>
            <div class="indicator" onclick="currentSlide(7)"></div>
            <div class="indicator" onclick="currentSlide(8)"></div>
            <div class="indicator" onclick="currentSlide(9)"></div>
            <div class="indicator" onclick="currentSlide(10)"></div>
        </div>
    </div>

<div class="footer-minimal">
    <p>CAT 21</p>
</div>
<script src="assets/js/script.js"></script>
</body>
</html>
