<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor, inicia sesión para acceder a esta página.";
    header("Location: login.php");
    exit;
}

// Fetch user data for navigation
try {
    $stmt = $pdo->prepare("SELECT nombre, apellido_paterno, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION['error'] = "Usuario no encontrado.";
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar datos: " . $e->getMessage();
    error_log("productos.php: Database error: " . $e->getMessage());
    header("Location: login.php");
    exit;
}

// Fetch products
try {
    $stmt = $pdo->query("SELECT * FROM productos WHERE activo = 1 ORDER BY id DESC");
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar productos: " . $e->getMessage();
    error_log("productos.php: Database error: " . $e->getMessage());
    header("Location: inicio.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - CAT21</title>
    <link rel="stylesheet" href="assets/css/productos.css">

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

/* Encabezado con disposición en cuadrícula */
.header {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    background: #000000;
    color: #ffffff;
    padding: 20px 40px;
    border-bottom: 1px solid #333333;
}

/* Contenedores del encabezado */
.header-left,
.header-center,
.header-right {
    display: flex;
    align-items: center;
}

/* Alinea elementos a la izquierda */
.header-left {
    justify-content: flex-start;
    gap: 10px;
}

/* Centra elementos */
.header-center {
    justify-content: center;
}

/* Alinea elementos a la derecha */
.header-right {
    justify-content: flex-end;
    gap: 10px;
}

/* Título del encabezado */
.title {
    font-size: 28px;
    font-weight: 300;
    color: #ffffff;
    letter-spacing: 4px;
    text-transform: uppercase;
    text-align: center;
}

/* Estilo de los botones animados */
.btn-animated {
    position: relative;
    overflow: hidden;
    background: transparent;
    color: #ffffff;
    border: 1px solid #ffffff;
    padding: 10px 20px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 400;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-decoration: none;
    transition: color 0.3s ease, border-color 0.3s ease;
}

/* Contenido del botón */
.btn-animated span {
    position: relative;
    z-index: 1;
}

/* Efecto de fondo para el botón */
.btn-animated::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: #FFD700;
    z-index: 0;
    transition: left 0.3s ease;
}

/* Efecto hover para el botón */
.btn-animated:hover::before {
    left: 0;
}

/* Efecto hover para el color y borde del botón */
.btn-animated:hover {
    color: #000000;
    border-color: #FFD700;
    box-shadow: 0 0 0 2px #FFCA28;
}

/* Contenido principal con animación de entrada */
.productos-main {
    padding: 40px;
    animation: fadeIn 0.8s ease-out;
}

/* Sección de productos */
.productos-section {
    max-width: 2040px;
    margin: 0 auto;
}

/* Título de la sección de productos */
.productos-title {
    font-size: 24px;
    font-weight: 300;
    color: #000000;
    letter-spacing: 3px;
    text-transform: uppercase;
    text-align: center;
    margin-bottom: 40px;
    padding: 20px;
    border: 1px solid #333333;
    background: #f8f8f8;
    border-radius: 8px;
    position: relative;
}

/* Línea decorativa superior en el título */
.productos-title::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #FFD700, #FF0000, #FFD700);
}

/* Cuadrícula de productos */
.productos-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    padding: 20px;
}

/* Contenedor de cada producto */
.producto {
    border: 3px solid transparent;
    background: linear-gradient(180deg, #ffffff, #f8f8f8), linear-gradient(90deg, #FFD700, #FFCA28);
    background-clip: padding-box, border-box;
    background-origin: padding-box, border-box;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.4s ease;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    animation: cardScaleIn 0.6s ease-out;
    max-width: 500px;
    height: 450px; /* Altura fija para uniformidad */
    display: flex;
    flex-direction: column;
}

/* Efecto hover para el producto */
.producto:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.3), 0 0 10px rgba(255, 215, 0, 0.5);
    background: linear-gradient(180deg, #ffffff, #f8f8f8), linear-gradient(90deg, #FFCA28, #FFD700);
}

/* Imagen del producto */
.producto-imagen {
    width: 100%;
    max-width: 100%;
    height: 250px; /* Altura fija para escritorio */
    object-fit: cover;
    object-position: center; /* Centra el recorte */
    border-bottom: 1px solid #e0e0e0;
}

/* Placeholder para productos sin imagen */
.producto-placeholder {
    width: 100%;
    max-width: 100%;
    height: 250px; /* Igual altura que la imagen */
    background: linear-gradient(135deg, #FFD700, #FFF8E1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #666666;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px solid #e0e0e0;
}

/* Información del producto */
.producto-info {
    padding: 20px;
    background: #FFF8E1;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* Nombre del producto */
.producto-nombre {
    font-size: 24px;
    font-weight: 500;
    color: #000000;
    letter-spacing: 1px;
    margin-bottom: 10px;
    text-transform: uppercase;
    position: relative;
}

/* Línea decorativa bajo el nombre */
.producto-nombre::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    width: 80px;
    height: 3px;
    background: #FFD700;
}

/* Descripción del producto */
.producto-descripcion {
    font-size: 16px;
    color: #666666;
    margin-bottom: 10px;
    font-weight: 300;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2; /* Limita a 2 líneas */
    -webkit-box-orient: vertical;
}

/* Precio del producto */
.producto-precio {
    font-size: 20px;
    font-weight: 400;
    color: #000000;
    background: linear-gradient(90deg, #FFD700, #FFCA28);
    padding: 8px 16px;
    border-radius: 6px;
    display: inline-block;
    transition: all 0.3s ease;
    animation: pulse 1.5s ease-in-out infinite;
}

/* Efecto hover para el precio */
.producto-precio:hover {
    background: linear-gradient(90deg, #FFCA28, #FFD700);
    box-shadow: 0 0 0 2px #FFD700;
}

/* Mensaje cuando no hay productos */
.no-productos {
    text-align: center;
    font-size: 18px;
    font-weight: 300;
    color: #666666;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 40px 20px;
    border: 1px solid #e0e0e0;
    background: #f8f8f8;
    border-radius: 8px;
    grid-column: 1 / -1;
}

/* Contenedor de alertas */
.alert {
    text-align: center;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 4px;
    font-weight: 400;
    letter-spacing: 1px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Estilo de alerta de error */
.alert-error {
    border: 1px solid #FF0000;
    background: #fff5f5;
    color: #FF0000;
}

/* Pie de página minimalista */
.footer-minimal {
    text-align: center;
    padding: 30px;
    border-top: 1px solid #e0e0e0;
    margin-top: 30px;
}

/* Texto del pie de página */
.footer-minimal p {
    font-size: 12px;
    font-weight: 300;
    color: #999999;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Animación de entrada */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Animación de entrada para tarjetas */
@keyframes cardScaleIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Animación de pulsación para el precio */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

/* Media query para pantallas de hasta 1920px */
@media (max-width: 1920px) {
    /* Ajusta la cuadrícula de productos */
    .productos-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    /* Ajusta el contenedor de producto */
    .producto {
        max-width: 450px;
        height: 400px;
        margin: 0 auto;
    }

    /* Ajusta la imagen y placeholder */
    .producto-imagen,
    .producto-placeholder {
        height: 220px;
    }

    /* Ajusta la información del producto */
    .producto-info {
        padding: 15px;
    }
}

/* Media query para pantallas de hasta 1200px */
@media (max-width: 1200px) {
    /* Ajusta la cuadrícula de productos */
    .productos-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    /* Ajusta el contenedor de producto */
    .producto {
        max-width: 400px;
        height: 380px;
    }

    /* Ajusta la imagen y placeholder */
    .producto-imagen,
    .producto-placeholder {
        height: 200px;
    }

    /* Ajusta la información del producto */
    .producto-info {
        padding: 15px;
    }
}

/* Media query para pantallas de hasta 1024px */
@media (max-width: 1024px) {
    /* Ajusta el encabezado */
    .header {
        padding: 20px 30px;
    }

    /* Ajusta el título */
    .title {
        font-size: 24px;
    }

    /* Ajusta el contenido principal */
    .productos-main {
        padding: 30px;
    }

    /* Ajusta la cuadrícula de productos */
    .productos-grid {
        gap: 20px;
    }

    /* Ajusta el contenedor de producto */
    .producto {
        max-width: 350px;
        height: 350px;
    }

    /* Ajusta la imagen y placeholder */
    .producto-imagen,
    .producto-placeholder {
        height: 180px;
    }

    /* Ajusta la información del producto */
    .producto-info {
        padding: 12px;
    }

    /* Ajusta el nombre del producto */
    .producto-nombre {
        font-size: 20px;
    }

    /* Ajusta la descripción del producto */
    .producto-descripcion {
        font-size: 14px;
    }

    /* Ajusta el precio del producto */
    .producto-precio {
        font-size: 18px;
        padding: 6px 12px;
    }
}

/* Media query para pantallas de hasta 768px */
@media (max-width: 768px) {
    /* Ajusta el encabezado */
    .header {
        grid-template-columns: 1fr;
        gap: 15px;
        padding: 15px 20px;
        text-align: center;
    }

    /* Centra los contenedores del encabezado */
    .header-left,
    .header-right {
        justify-content: center;
    }

    /* Ajusta los botones */
    .btn-animated {
        width: 100%;
        max-width: 300px;
        padding: 8px 16px;
        font-size: 12px;
    }

    /* Ajusta el título */
    .title {
        font-size: 20px;
        letter-spacing: 2px;
    }

    /* Ajusta el contenido principal */
    .productos-main {
        padding: 25px;
    }

    /* Ajusta el título de la sección */
    .productos-title {
        font-size: 20px;
        padding: 15px;
    }

    /* Ajusta la cuadrícula de productos */
    .productos-grid {
        display: flex;
        flex-direction: column;
        gap: 20px;
        padding: 15px;
    }

    /* Ajusta el contenedor de producto */
    .producto {
        max-width: 400px;
        height: 320px;
        margin: 0 auto;
    }

    /* Ajusta la imagen y placeholder */
    .producto-imagen,
    .producto-placeholder {
        height: 160px;
    }

    /* Ajusta la información del producto */
    .producto-info {
        padding: 10px;
    }

    /* Ajusta el nombre del producto */
    .producto-nombre {
        font-size: 18px;
    }

    /* Ajusta la descripción del producto */
    .producto-descripcion {
        font-size: 13px;
    }

    /* Ajusta el precio del producto */
    .producto-precio {
        font-size: 16px;
        padding: 6px 12px;
    }

    /* Ajusta el mensaje de no productos */
    .no-productos {
        font-size: 16px;
        padding: 30px 15px;
    }

    /* Ajusta las alertas */
    .alert {
        padding: 15px;
        font-size: 12px;
    }

    /* Ajusta el pie de página */
    .footer-minimal {
        padding: 20px;
    }

    /* Ajusta el texto del pie de página */
    .footer-minimal p {
        font-size: 11px;
    }
}

/* Media query para pantallas de hasta 480px */
@media (max-width: 480px) {
    /* Ajusta el encabezado */
    .header {
        padding: 10px 15px;
    }

    /* Ajusta los botones */
    .btn-animated {
        padding: 6px 12px;
        font-size: 11px;
    }

    /* Ajusta el título */
    .title {
        font-size: 18px;
    }

    /* Ajusta el contenido principal */
    .productos-main {
        padding: 20px;
    }

    /* Ajusta el título de la sección */
    .productos-title {
        font-size: 18px;
        padding: 12px;
    }

    /* Ajusta la cuadrícula de productos */
    .productos-grid {
        gap: 15px;
        padding: 10px;
    }

    /* Ajusta el contenedor de producto */
    .producto {
        max-width: 350px;
        height: 300px;
    }

    /* Ajusta la imagen y placeholder */
    .producto-imagen,
    .producto-placeholder {
        height: 140px;
    }

    /* Ajusta la información del producto */
    .producto-info {
        padding: 8px;
    }

    /* Ajusta el nombre del producto */
    .producto-nombre {
        font-size: 16px;
    }

    /* Ajusta la descripción del producto */
    .producto-descripcion {
        font-size: 12px;
    }

    /* Ajusta el precio del producto */
    .producto-precio {
        font-size: 14px;
        padding: 4px 8px;
    }

    /* Ajusta el mensaje de no productos */
    .no-productos {
        font-size: 14px;
        padding: 20px 10px;
    }

    /* Ajusta las alertas */
    .alert {
        padding: 12px;
        font-size: 11px;
    }

    /* Ajusta el pie de página */
    .footer-minimal {
        padding: 15px;
    }

    /* Ajusta el texto del pie de página */
    .footer-minimal p {
        font-size: 10px;
    }
}

/* Media query para pantallas muy pequeñas (hasta 320px) */
@media (max-width: 320px) {
    /* Ajusta el encabezado */
    .header {
        padding: 8px 10px;
    }

    /* Ajusta los botones */
    .btn-animated {
        padding: 5px 10px;
        font-size: 10px;
    }

    /* Ajusta el título */
    .title {
        font-size: 16px;
        letter-spacing: 1px;
    }

    /* Ajusta el contenido principal */
    .productos-main {
        padding: 15px;
    }

    /* Ajusta el título de la sección */
    .productos-title {
        font-size: 16px;
        padding: 10px;
    }

    /* Ajusta la cuadrícula de productos */
    .productos-grid {
        gap: 10px;
        padding: 8px;
    }

    /* Ajusta el contenedor de producto */
    .producto {
        max-width: 300px;
        height: 280px;
    }

    /* Ajusta la imagen y placeholder */
    .producto-imagen,
    .producto-placeholder {
        height: 120px; /* Reducida para pantallas muy pequeñas */
    }

    /* Ajusta la información del producto */
    .producto-info {
        padding: 8px;
    }

    /* Ajusta el nombre del producto */
    .producto-nombre {
        font-size: 14px;
    }

    /* Ajusta la descripción del producto */
    .producto-descripcion {
        font-size: 11px;
    }

    /* Ajusta el precio del producto */
    .producto-precio {
        font-size: 12px;
        padding: 4px 8px;
    }

    /* Ajusta el mensaje de no productos */
    .no-productos {
        font-size: 13px;
        padding: 15px 8px;
    }

    /* Ajusta las alertas */
    .alert {
        padding: 10px;
        font-size: 10px;
    }

    /* Ajusta el pie de página */
    .footer-minimal {
        padding: 10px;
    }

    /* Ajusta el texto del pie de página */
    .footer-minimal p {
        font-size: 10px;
    }
}
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <a href="inicio.php" class="btn-animated"><span>Regresar a Inicio</span></a>
        </div>
        <div class="header-center">
            <h1 class="title">Productos y Promociones</h1>
        </div>
        <div class="header-right">
            <?php if ($user['rol'] === 'admin'): ?>
                <a href="admin_productos.php" class="btn-animated"><span>Administrar Productos</span></a>
            <?php endif; ?>
        </div>
    </div>

    <main class="productos-main">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <section class="productos-section">
            <h2 class="productos-title">Productos Disponibles</h2>
            <div class="productos-grid">
                <?php foreach ($productos as $producto): ?>
                    <article class="producto">
                        <?php if ($producto['imagen'] && file_exists('Uploads/' . $producto['imagen'])): ?>
                            <img src="Uploads/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="producto-imagen">
                        <?php else: ?>
                            <div class="producto-placeholder">Sin imagen</div>
                        <?php endif; ?>
                        <div class="producto-info">
                            <h3 class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <p class="producto-descripcion"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            <p class="producto-precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (empty($productos)): ?>
                    <div class="no-productos">No hay productos disponibles.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="footer-minimal">
        <p>© 2025 CAT21 - Todos los derechos reservados</p>
    </footer>
</body>
</html>