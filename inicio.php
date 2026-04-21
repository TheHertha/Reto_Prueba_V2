<?php
require_once 'config.php'; 
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


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


@media (max-width: 480px) {
    
    .header {
        padding: 8px 15px;
        height: auto;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

   
    .logo {
        height: 40px;
        width: 40px;
        margin-right: 0;
    }

   
    .title {
        font-size: 18px;
        letter-spacing: 1px;
        margin: 5px 0;
    }


    .header-right {
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

   
    .profile-btn,
    .logout-btn {
        padding: 6px 12px;
        font-size: 11px;
    }


    .navigation {
        margin: 20px 0;
        gap: 10px;
    }


    .nav-btn {
        padding: 10px 20px;
        font-size: 12px;
    }


    .carousel-container {
        height: 40vh;
        max-width: 100%;
    }

 
    .footer-minimal {
        padding: 15px;
    }

  
    .footer-minimal p {
        font-size: 11px;
    }
}


@media (max-width: 320px) {
 
    .header {
        padding: 6px 10px;
        gap: 8px;
    }

    
    .logo {
        height: 35px;
        width: 35px;
    }

   
    .title {
        font-size: 16px;
        letter-spacing: 0.8px;
    }

  
    .header-right {
        gap: 6px;
    }


    .profile-btn,
    .logout-btn {
        padding: 5px 10px;
        font-size: 10px;
    }

  
    .navigation {
        margin: 15px 0;
        gap: 8px;
    }

 
    .nav-btn {
        padding: 8px 16px;
        font-size: 11px;
    }


    .carousel-container {
        height: 35vh;
    }


    .footer-minimal {
        padding: 10px;
    }


    .footer-minimal p {
        font-size: 10px;
    }
}


.layout-container {
    display: flex;
    min-height: calc(100vh - 80px); 
}

/* Sidebar */
.sidebar {
    width: 240px;
    background: #ffffff;
    border-right: 1px solid #e0e0e0;
    position: fixed;
    height: 100%;
    top: 80px;             
    left: 0;
    padding-top: 20px;
    overflow-y: auto;
    z-index: 10;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 0 20px;
}


.sidebar-btn {
    display: block;
    width: 100%;
    text-align: left;
    padding: 14px 24px;
    border: 1px solid black;
    border-radius: 6px;
    background: transparent;
    color: #000;
    text-decoration: none;
    font-size: 15px;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.sidebar-btn::before {
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

.sidebar-btn:hover::before {
    left: 0;
}

.sidebar-btn:hover {
    color: white;
}

.sidebar-btn span {
    position: relative;
    z-index: 1;
}


.admin-btn {
    margin-top: 20px;
    border-color: #444;
}


.main-content {
    margin-left: 240px;     
    flex: 1;
    padding: 40px 30px;
    background: #ffffff;
}


.feed-header {
    margin-bottom: 30px;
}

.feed-header h2 {
    font-size: 28px;
    margin-bottom: 8px;
}

.feed-header p {
    color: #555;
    font-size: 16px;
}


.feed-container {
    max-width: 800px;
}


.post-placeholder {
    background: #f8f8f8;
    border: 1px dashed #aaa;
    border-radius: 12px;
    padding: 60px 30px;
    text-align: center;
    color: #666;
    font-size: 17px;
    line-height: 1.7;
}


@media (max-width: 992px) {
    .sidebar {
        width: 220px;
    }
    .main-content {
        margin-left: 220px;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 80px;               
        padding: 20px 0;
    }
    
    .sidebar-btn {
        text-align: center;
        padding: 16px 0;
        font-size: 0;              
    }
    
    .sidebar-btn span {
        display: none;
    }
    

    .main-content {
        margin-left: 80px;
    }
    
    .feed-header h2 {
        font-size: 24px;
    }
}

.sidebar {
    width: 68px;                /* ancho inicial = solo íconos */
    background: #ffffff;
    border-right: 1px solid #e0e0e0;
    position: fixed;
    top: 80px;                  /* debajo del header */
    left: 0;
    height: calc(100vh - 80px);
    transition: width 0.35s ease;   /* animación suave */
    overflow: hidden;
    z-index: 100;
    box-shadow: 1px 0 8px rgba(0,0,0,0.08); /* sutil sombra para que destaque */
}

.sidebar:hover {
    width: 240px;               /* ancho expandido */
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    padding: 16px 0;
    gap: 8px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    color: #000;
    text-decoration: none;
    position: relative;
    overflow: hidden;
    white-space: nowrap;
    transition: background 0.3s ease, color 0.3s ease;
}

.sidebar-link .icon {
    font-size: 24px;            /* tamaño de íconos */
    min-width: 36px;            /* espacio fijo para ícono */
    text-align: center;
}

.sidebar-link .text {
    opacity: 0;
    transform: translateX(-12px);
    transition: opacity 0.35s ease, transform 0.35s ease;
    margin-left: 12px;
    font-size: 15px;
    text-transform: uppercase;
}

/* Al hover del sidebar → mostrar texto */
.sidebar:hover .sidebar-link .text {
    opacity: 1;
    transform: translateX(0);
}

/* Efecto hover individual (reutilizamos tu estilo anterior) */
.sidebar-link::before {
    content: "";
    position: absolute;
    left: -100%;
    top: 0;
    height: 100%;
    width: 100%;
    background-color: black;
    z-index: -1;
    transition: left 0.4s ease;
}

.sidebar-link:hover::before {
    left: 0;
}

.sidebar-link:hover {
    color: white;
}


.admin-link {
    margin-top: 24px;
    border-top: 1px solid #eee;
    padding-top: 20px;
}


.main-content {
    margin-left: 68px;        
    transition: margin-left 0.35s ease;
}

.sidebar:hover ~ .main-content {
    margin-left: 240px;         
}


@media (max-width: 992px) {
    .sidebar {
        width: 68px;
    }
    .main-content {
        margin-left: 68px;
    }
  
}

.sidebar-link i {
    font-size: 1.5rem;          /* ≈ 24px, buen tamaño para íconos */
    min-width: 40px;            /* espacio fijo para alinear bien */
    text-align: center;
    transition: transform 0.3s ease;  /* opcional: pequeño efecto al hover */
}

.sidebar-link:hover i {
    transform: scale(1.15);     /* leve zoom al hover para más feedback */
}

/* Asegurar que el texto no se corte y se alinee bien */
.sidebar-link {
    padding: 14px 18px;         /* un poco más de padding horizontal */
}

/* Cuando está colapsado, centrar íconos perfectamente */
.sidebar {
    width: 72px;                /* ajustado un poco para que quepan íconos bien */
}

.sidebar:hover {
    width: 260px;               /* un poco más ancho para texto + padding */
}

/* Tarjetas de publicación */
.post-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    margin-bottom: 28px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
}

/* Contenedor de imagen (para fondo y control) */
.post-image-container {
    width: 100%;
    background: #f8f8f8;           /* fondo neutro en espacios laterales o arriba/abajo */
    overflow: hidden;
}

/* Imagen responsiva - respeta TODOS los aspect ratios que mencionaste */
.post-image {
    width: 100%;
    height: auto;
    max-height: 800px;             /* limita posts muy verticales (ej. 1080×1920) para mejor UX */
    display: block;
    object-fit: contain;           /* muestra imagen COMPLETA sin cortar nada */
    object-position: center;
    background: #f8f8f8;           /* respaldo mientras carga */
}

/* En móviles: altura más controlada para scroll cómodo */
@media (max-width: 768px) {
    .post-image {
        max-height: 650px;
    }
}

/* Contenido debajo de la imagen */
.post-content {
    padding: 16px 18px;
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    font-size: 14px;
    color: #444;
}

.post-header strong {
    color: #000;
    font-weight: 600;
}

.post-text {
    font-size: 16px;
    line-height: 1.55;
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
}


.feed-container {
    max-width: 720px;             
    margin: 0 auto;
}

.post-video-container {
    width: 100%;
    background: #000;
    overflow: hidden;
    border-radius: 8px 8px 0 0;
}

.post-video {
    width: 100%;
    height: auto;
    max-height: 800px;
    display: block;
    object-fit: contain;
}

    </style>
<!-- Marked.js (solo una vez, versión reciente) -->
<script src="https://cdn.jsdelivr.net/npm/marked@14.0.0/lib/marked.umd.min.js"></script>

<style>
  /* ESTILOS DEL CHATBOT - versión naranja que querías originalmente */
  .chatbot-toggle {
    position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px;
    background: linear-gradient(135deg, #ed563b, #ed563b); color: white;
    border: none; border-radius: 50%; cursor: pointer;
    box-shadow: 0 6px 20px rgba(0, 184, 148, 0.4); z-index: 2000; /* subimos z-index por si acaso */
    display: flex; align-items: center; justify-content: center; transition: all 0.3s;
  }
  .chatbot-toggle:hover { transform: scale(1.1); }

  .chatbot-container {
    position: fixed; bottom: 110px; right: 30px; width: 380px; height: 520px;
    background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.25);
    overflow: hidden; display: none; flex-direction: column; z-index: 1999;
  }
  .chatbot-container.open { display: flex; }

  .chatbot-header {
    background: linear-gradient(135deg, #ed563b, #ed563b); color: white;
    padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;
  }
  .chatbot-header h3 { margin: 0; font-size: 18px; }

  .chatbot-close { background: none; border: none; color: white; font-size: 28px; cursor: pointer; }

  .chatbot-messages {
    flex: 1; padding: 20px; overflow-y: auto; background: #f8f9fa;
    display: flex; flex-direction: column; gap: 12px;
  }

  .chatbot-message {
    max-width: 80%; padding: 12px 16px; border-radius: 18px; font-size: 15px; line-height: 1.4;
  }
  .chatbot-message.bot { align-self: flex-start; background: #e9ecef; border-bottom-left-radius: 4px; }
  .chatbot-message.user { align-self: flex-end; background: #ed563b; color: white; border-bottom-right-radius: 4px; }

  .chatbot-input {
    padding: 15px; background: white; border-top: 1px solid #eee; display: flex;
  }
  .chatbot-input input {
    flex: 1; padding: 12px 16px; border: 1px solid #ddd; border-radius: 30px; font-size: 15px;
  }
  .chatbot-input button {
    margin-left: 10px; background: #ed563b; color: white; border: none;
    border-radius: 50%; width: 44px; height: 44px; cursor: pointer;
  }

  .chatbot-footer {
    padding: 8px 15px; background: #f8f9fa; border-top: 1px solid #eee;
    font-size: 12px; color: #666; text-align: center;
  }

  @media (max-width: 500px) {
    .chatbot-container { width: 90vw; right: 5vw; height: 70vh; bottom: 90px; }
  }

  /* Asegura que no choque con el body background */
  body { background: #ffffff !important; } /* fuerza tu fondo blanco original */
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

<div class="layout-container">

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <a href="reto.php" class="sidebar-link" title="Reto">
            <i class="fas fa-trophy"></i>
            <span class="text">Reto</span>
        </a>
        <a href="ranking.php" class="sidebar-link" title="Ranking">
            <i class="fas fa-chart-bar"></i>
            <span class="text">Ranking</span>
        </a>
        <a href="eventos.php" class="sidebar-link" title="Eventos">
            <i class="fas fa-calendar-days"></i>
            <span class="text">Eventos</span>
        </a>
        <a href="productos.php" class="sidebar-link" title="Productos">
            <i class="fas fa-cart-shopping"></i>
            <span class="text">Productos</span>
        </a>
        <a href="biblioteca.html" class="sidebar-link" title="Biblioteca">
            <i class="fas fa-book"></i>
            <span class="text">Biblioteca</span>
        </a>
        
       <?php if (isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'facilitador_admin'])): ?>
    <a href="admin_reto.php" class="sidebar-link admin-link" title="Administrar">
        <i class="fas fa-gear"></i>
        <span class="text">Administrar</span>
    </a>
<?php endif; ?>
    </nav>
</aside>

<main class="main-content">
    <div class="feed-header">
        <h2>Bienvenido, <?php echo $nombre; ?></h2>
        <p>Últimas publicaciones de la comunidad</p>
    </div>
    
    <div class="feed-container">
        <?php
        try {
            $stmt = $pdo->prepare("
                SELECT id, contenido, imagen, media_tipo, media_url, fecha
                FROM publicaciones
                WHERE activo = 1
                ORDER BY fecha DESC
                LIMIT 15
            ");
            $stmt->execute();
            $publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($publicaciones)) {
                echo '<div class="post-placeholder">Aún no hay publicaciones. ¡Sé el primero en compartir algo!</div>';
            } else {
                foreach ($publicaciones as $pub) {
                    $media_html = '';

                    // Imagen
                    if (!empty($pub['imagen']) && $pub['media_tipo'] === 'image') {
                        $media_html = '
                            <div class="post-image-container">
                                <img 
                                    src="' . htmlspecialchars($pub['imagen']) . '" 
                                    alt="Publicación" 
                                    class="post-image" 
                                    loading="lazy" 
                                    decoding="async"
                                >
                            </div>';
                    }
                    // Video
                    elseif (!empty($pub['media_url']) && $pub['media_tipo'] === 'video') {
                        $media_html = '
                            <div class="post-video-container">
                                <video 
                                    src="' . htmlspecialchars($pub['media_url']) . '" 
                                    class="post-video" 
                                    controls 
                                    preload="metadata" 
                                    loading="lazy"
                                    playsinline
                                ></video>
                            </div>';
                    }

                    ?>
                    <div class="post-card">
                        <?= $media_html ?>
                        
                        <div class="post-content">
                            <div class="post-header">
                                <strong><?= htmlspecialchars($nombre) ?></strong>
                                <small>· <?= date('d M Y • H:i', strtotime($pub['fecha'])) ?></small>
                            </div>
                            <p class="post-text"><?= nl2br(htmlspecialchars($pub['contenido'])) ?></p>
                        </div>
                    </div>
                    <?php
                }
            }
        } catch (Exception $e) {
            echo '<div style="color:red; text-align:center; padding: 20px;">Error al cargar publicaciones: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</main>

</div> <!-- cierre layout-container -->

<div class="footer-minimal">
    <p>CAT 21</p>
</div>

<script src="assets/js/script.js"></script>
<!-- Botón flotante chatbot -->
<button class="chatbot-toggle" id="chatbotToggle">
  <svg width="28" height="28" fill="white" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
</button>

<!-- Contenedor chat -->
<div class="chatbot-container" id="chatbotContainer">
  <div class="chatbot-header">
    <h3>🩺 Dr. NutriBot</h3>
    <button class="chatbot-close" id="chatbotClose">×</button>
  </div>

  <div class="chatbot-messages" id="chatbotMessages"></div> <!-- vacío al inicio -->

  <div class="chatbot-input">
    <input type="text" id="chatbotInput" placeholder="Escribe tu duda...">
    <button id="chatbotSend">→</button>
  </div>

  <div class="chatbot-footer">
    <small>⚠️ Dr. NutriBot es IA. No sustituye a un profesional de salud.<br>Consulta a un nutricionista certificado.</small>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked@14.0.0/lib/marked.umd.min.js"></script>

<script>
  // === CONFIGURACIÓN DEL CHATBOT ===
  const toggle = document.getElementById('chatbotToggle');
  const container = document.getElementById('chatbotContainer');
  const closeBtn = document.getElementById('chatbotClose');
  const messagesDiv = document.getElementById('chatbotMessages');
  const input = document.getElementById('chatbotInput');
  const sendBtn = document.getElementById('chatbotSend');

  let history = [];

  // Mensaje inicial
  const initialText = "¡Hola! Soy el Dr. NutriBot.\n¿Qué duda tienes sobre nutrición hoy?";
  addMessage(initialText, 'bot');
  history.push({ role: "model", parts: [{ text: initialText }] });

  if (toggle) {
    toggle.addEventListener('click', () => {
      container.classList.toggle('open');
      messagesDiv.scrollTop = messagesDiv.scrollHeight;
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      container.classList.remove('open');
    });
  }

  async function sendMessage() {
    const text = input?.value?.trim();
    if (!text) return;

    addMessage(text, 'user');
    history.push({ role: "user", parts: [{ text }] });
    input.value = '';

    const typing = document.createElement('div');
    typing.className = 'chatbot-message bot typing';
    typing.textContent = 'Dr. NutriBot está pensando...';
    messagesDiv.appendChild(typing);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;

    try {
      console.log("Enviando mensaje al proxy...");

      const res = await fetch('chatbot-proxy.php', {   // ←←← ESTA ES LA LÍNEA IMPORTANTE
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          contents: history,
          generationConfig: { temperature: 0.7, maxOutputTokens: 800 },
          systemInstruction: {
            parts: [{
              text: `Eres Dr. NutriBot, asistente de nutrición amigable y profesional.
Responde SIEMPRE en español, claro, corto y positivo.
Usa Markdown cuando sea útil.
Solo dudas generales. Nunca dietas personalizadas ni consejos médicos.`
            }]
          }
        })
      });

      if (!res.ok) {
        const errText = await res.text();
        throw new Error(`Error ${res.status}: ${errText}`);
      }

      const data = await res.json();
      const botText = data.candidates?.[0]?.content?.parts?.[0]?.text || "Lo siento, no pude responder en este momento.";

      typing.remove();
      addMessage(botText, 'bot');
      history.push({ role: "model", parts: [{ text: botText }] });

    } catch (err) {
      console.error("Error en el chatbot:", err);
      typing.remove();
      addMessage("¡Ups! Hubo un problema de conexión. Inténtalo de nuevo.", 'bot');
    }
  }

  function addMessage(text, sender) {
    if (!messagesDiv) return;
    const msg = document.createElement('div');
    msg.className = `chatbot-message ${sender}`;
    if (sender === 'bot') {
      try {
        msg.innerHTML = marked.parse(text);
      } catch (e) {
        msg.textContent = text;
      }
    } else {
      msg.textContent = text;
    }
    messagesDiv.appendChild(msg);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
  }

  // Event listeners
  if (sendBtn) sendBtn.addEventListener('click', sendMessage);
  if (input) {
    input.addEventListener('keypress', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }
</script>
</body>
</html>
</html> 