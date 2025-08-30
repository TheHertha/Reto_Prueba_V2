<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor, inicia sesión para acceder al panel de administración.";
    error_log("admin_productos.php: Redirecting to login.php, no user_id");
    header("Location: login.php");
    exit;
}

// Check admin role
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "No tienes permisos para acceder a esta página.";
    error_log("admin_productos.php: Redirecting to inicio.php, rol=" . ($_SESSION['rol'] ?? 'unset'));
    header("Location: inicio.php");
    exit;
}

// Generate or reuse CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
error_log("admin_productos.php: CSRF token=$csrf_token");

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $submitted_token = $_POST['csrf_token'] ?? 'unset';
    error_log("admin_productos.php: Submitted CSRF token=$submitted_token, Expected=$csrf_token");
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad. Intenta de nuevo.";
        error_log("admin_productos.php: CSRF token validation failed");
        header("Location: admin_productos.php");
        exit;
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);

    // Validate inputs
    if (empty($nombre)) {
        $_SESSION['error'] = "El nombre del producto es obligatorio.";
        error_log("admin_productos.php: Validation failed, empty nombre");
        header("Location: admin_productos.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
    if ($precio <= 0) {
        $_SESSION['error'] = "El precio debe ser mayor a 0.";
        error_log("admin_productos.php: Validation failed, invalid precio");
        header("Location: admin_productos.php" . ($id ? "?edit=$id" : ""));
        exit;
    }

    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $imagen = subirImagen($_FILES['imagen'], 'product_');
        if ($imagen === false) {
            $_SESSION['error'] = "Error al subir la imagen. Verifica el formato (JPG, PNG, GIF) y tamaño (<5MB).";
            error_log("admin_productos.php: Image upload failed");
            header("Location: admin_productos.php" . ($id ? "?edit=$id" : ""));
            exit;
        }
    }

    try {
        if ($id > 0) {
            // Update product
            $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?";
            $params = [$nombre, $descripcion, $precio];
            if ($imagen) {
                $sql .= ", imagen = ?";
                $params[] = $imagen;
            }
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['success'] = "Producto actualizado exitosamente.";
            error_log("admin_productos.php: Product updated, id=$id");
        } else {
            // Create new product
            $imagen = $imagen ?: 'default.jpg';
            $sql = "INSERT INTO productos (nombre, descripcion, precio, imagen, activo) VALUES (?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $descripcion, $precio, $imagen]);
            $_SESSION['success'] = "Producto creado exitosamente.";
            error_log("admin_productos.php: Product created");
        }
        // Regenerate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: admin_productos.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
        error_log("admin_productos.php: Database error: " . $e->getMessage());
        header("Location: admin_productos.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
}

// Deactivate product
if (isset($_GET['deactivate'])) {
    // Validate CSRF for deactivation
    $submitted_token = $_GET['csrf_token'] ?? 'unset';
    error_log("admin_productos.php: Deactivate CSRF token=$submitted_token, Expected=$csrf_token");
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad. Intenta de nuevo.";
        error_log("admin_productos.php: CSRF token validation failed for deactivate");
        header("Location: admin_productos.php");
        exit;
    }

    try {
        $id = intval($_GET['deactivate']);
        $stmt = $pdo->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Producto desactivado exitosamente.";
        error_log("admin_productos.php: Product deactivated, id=$id");
        // Regenerate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: admin_productos.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al desactivar el producto: " . $e->getMessage();
        error_log("admin_productos.php: Deactivate error: " . $e->getMessage());
        header("Location: admin_productos.php");
        exit;
    }
}

// Reactivate product
if (isset($_GET['reactivate'])) {
    // Validate CSRF for reactivation
    $submitted_token = $_GET['csrf_token'] ?? 'unset';
    error_log("admin_productos.php: Reactivate CSRF token=$submitted_token, Expected=$csrf_token");
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad. Intenta de nuevo.";
        error_log("admin_productos.php: CSRF token validation failed for reactivate");
        header("Location: admin_productos.php");
        exit;
    }

    try {
        $id = intval($_GET['reactivate']);
        $stmt = $pdo->prepare("UPDATE productos SET activo = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Producto reactivado exitosamente.";
        error_log("admin_productos.php: Product reactivated, id=$id");
        // Regenerate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: admin_productos.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al reactivar el producto: " . $e->getMessage();
        error_log("admin_productos.php: Reactivate error: " . $e->getMessage());
        header("Location: admin_productos.php");
        exit;
    }
}

// Permanently delete product
if (isset($_GET['delete'])) {
    // Validate CSRF for deletion
    $submitted_token = $_GET['csrf_token'] ?? 'unset';
    error_log("admin_productos.php: Delete CSRF token=$submitted_token, Expected=$csrf_token");
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad. Intenta de nuevo.";
        error_log("admin_productos.php: CSRF token validation failed for delete");
        header("Location: admin_productos.php");
        exit;
    }

    try {
        $id = intval($_GET['delete']);
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Producto eliminado definitivamente exitosamente.";
        error_log("admin_productos.php: Product permanently deleted, id=$id");
        // Regenerate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: admin_productos.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al eliminar definitivamente el producto: " . $e->getMessage();
        error_log("admin_productos.php: Delete error: " . $e->getMessage());
        header("Location: admin_productos.php");
        exit;
    }
}

// Get product for editing
$productoEditar = null;
if (isset($_GET['edit'])) {
    try {
        $id = intval($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $productoEditar = $stmt->fetch();
        if (!$productoEditar) {
            $_SESSION['error'] = "Producto no encontrado.";
            error_log("admin_productos.php: Product not found, id=$id");
            header("Location: admin_productos.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al cargar el producto: " . $e->getMessage();
        error_log("admin_productos.php: Edit load error: " . $e->getMessage());
        header("Location: admin_productos.php");
        exit;
    }
}

// Get all products
try {
    $stmt = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar productos: " . $e->getMessage();
    error_log("admin_productos.php: Products load error: " . $e->getMessage());
    header("Location: admin_productos.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Productos - CAT21</title>
  <style>
/* Reset general */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5;
    color: #333;
    min-height: 100vh;
    line-height: 1.6;
}

/* Layout */
.layout {
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background: #000000;
    color: #ffffff;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding-bottom: 20px;
    border-bottom: 1px solid #333;
}

.logo {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.logo:hover {
    transform: rotate(5deg);
}

.sidebar h2 {
    font-size: 1.5rem;
    font-weight: 300;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.nav-item {
    display: block;
    padding: 12px 20px;
    color: #ffffff;
    text-decoration: none;
    font-size: 1rem;
    font-weight: 400;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.nav-item:hover {
    background: #FFD700;
    color: #000000;
}

.nav-item.active {
    background: #FFD700;
    color: #000000;
}

.nav-item.logout {
    margin-top: auto;
}

/* Main Content */
.main-content {
    flex: 1;
    padding: 40px;
    background: #ffffff;
}

.main-header {
    margin-bottom: 30px;
}

.main-header h1 {
    font-size: 2rem;
    font-weight: 300;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #000000;
}

/* Alerts */
.alert {
    position: relative;
    padding: 15px 40px 15px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.alert-success {
    background: #e6f4ea;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.alert-error {
    background: #fce4ec;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

.alert-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: inherit;
}

/* Form Card */
.form-card {
    background: #ffffff;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.form-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.form-card h2 {
    font-size: 1.5rem;
    font-weight: 400;
    margin-bottom: 20px;
    color: #000000;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #FFD700;
    box-shadow: 0 0 6px rgba(255, 215, 0, 0.3);
    outline: none;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Buttons */
.btn {
    position: relative;
    overflow: hidden;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    min-width: 120px; /* Ensure buttons have enough width */
}

.btn-submit {
    background: #000000;
    color: #FFD700;
    border: 1px solid #FFD700;
}

.btn-submit::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 0%;
    height: 100%;
    background: #FFD700;
    z-index: -1;
    transition: width 0.3s ease;
}

.btn-submit:hover::before {
    width: 100%;
}

.btn-submit:hover {
    color: #000000;
}

.btn-cancel {
    background: transparent;
    color: #333;
    border: 1px solid #ddd;
}

.btn-cancel:hover {
    background: #FFD700;
    color: #000000;
    border-color: #FFD700;
}

.btn-edit {
    background: #000000;
    color: #FFD700;
    border: 1px solid #FFD700;
}

.btn-edit::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 0%;
    height: 100%;
    background: #FFD700;
    z-index: -1;
    transition: width 0.3s ease;
}

.btn-edit:hover::before {
    width: 100%;
}

.btn-edit:hover {
    color: #000000;
}

.btn-deactivate {
    background: #ff6f61;
    color: #ffffff;
    border: 1px solid #ff6f61;
}

.btn-deactivate::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 0%;
    height: 100%;
    background: #e55a50;
    z-index: -1;
    transition: width 0.3s ease;
}

.btn-deactivate:hov
er::before {
    width: 100%;
}

.btn-deactivate:hover {
    color: #ffffff;
}

.btn-reactivate {
    background: #28a745;
    color: #ffffff;
    border: 1px solid #28a745;
}

.btn-reactivate::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 0%;
    height: 100%;
    background: #218838;
    z-index: -1;
    transition: width 0.3s ease;
}

.btn-reactivate:hover::before {
    width: 100%;
}

.btn-reactivate:hover {
    color: #ffffff;
}

.btn-delete {
    background: #d32f2f;
    color: #ffffff;
    border: 1px solid #d32f2f;
}

.btn-delete::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 0%;
    height: 100%;
    background: #b71c1c;
    z-index: -1;
    transition: width 0.3s ease;
}

.btn-delete:hover::before {
    width: 100%;
}

.btn-delete:hover {
    color: #ffffff;
}

/* Productos Section */
.productos-section h2 {
    font-size: 1.5rem;
    font-weight: 400;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #000000;
}

.productos-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
}

/* Producto Card */
.producto-card {
    background: #ffffff;
    border-radius: 8px;
    width: 100%;
    max-width: 300px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
}

.producto-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.producto-imagen {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px 8px 0 0;
}

.producto-placeholder {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #FFD700 0%, #FF0000 100%);
    border-radius: 8px 8px 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000;
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
}

.producto-info {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.producto-nombre {
    font-size: 1.2rem;
    font-weight: 600;
    color: #000000;
}

.producto-descripcion {
    font-size: 0.9rem;
    color: #555;
    line-height: 1.4;
}

.producto-precio {
    font-size: 1.1rem;
    font-weight: 600;
    color: #FFD700;
}

.producto-status {
    font-size: 0.85rem;
    color: #666;
    font-style: italic;
}

.producto-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 15px; /* Increased gap for better spacing */
    padding: 0 20px 20px;
    justify-content: flex-end;
    align-items: center;
}

/* Responsive */
@media (max-width: 1024px) {
    .layout {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        padding: 20px;
    }

    .main-content {
        padding: 20px;
    }

    .main-header h1 {
        font-size: 1.5rem;
    }

    .producto-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .btn {
        width: 100%;
        margin-bottom: 10px;
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 15px;
    }

    .form-card {
        padding: 20px;
    }

    .productos-grid {
        gap: 15px;
    }

    .producto-card {
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .sidebar-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-card h2,
    .productos-section h2 {
        font-size: 1.2rem;
    }

    .btn {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}

  </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/img/1-4.png" alt="CAT21 Logo" class="logo">
                <h2>CAT21 Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="inicio.php" class="nav-item">Inicio</a>
                <a href="productos.php" class="nav-item">Productos</a>
                <a href="admin_productos.php" class="nav-item active">Administrar Productos</a>
            </nav>
        </aside>
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Administrar Productos</h1>
            </header>
            <div class="content">
                <!-- Alerts -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                        <button class="alert-close">×</button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                        <button class="alert-close">×</button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <!-- Form -->
                <div class="form-card">
                    <h2><?php echo $productoEditar ? 'Editar Producto' : 'Agregar Producto'; ?></h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <?php if ($productoEditar): ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($productoEditar['id']); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="nombre">Nombre del Producto</label>
                            <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($productoEditar['nombre'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($productoEditar['descripcion'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="precio">Precio</label>
                            <input type="number" id="precio" name="precio" step="0.01" required value="<?php echo htmlspecialchars($productoEditar['precio'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="imagen">Imagen del Producto</label>
                            <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/gif">
                            <?php if ($productoEditar && $productoEditar['imagen']): ?>
                                <small>Imagen actual: <?php echo htmlspecialchars($productoEditar['imagen']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-submit"><?php echo $productoEditar ? 'Actualizar' : 'Crear'; ?></button>
                            <?php if ($productoEditar): ?>
                                <a href="admin_productos.php" class="btn btn-cancel">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <!-- Product List -->
                <div class="productos-section">
                    <h2>Productos Existentes</h2>
                    <div class="productos-grid">
                        <?php foreach ($productos as $producto): ?>
                            <div class="producto-card">
                                <?php if ($producto['imagen'] && file_exists('Uploads/' . $producto['imagen'])): ?>
                                    <img src="Uploads/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="producto-imagen">
                                <?php else: ?>
                                    <div class="producto-placeholder">Sin Imagen</div>
                                <?php endif; ?>
                                <div class="producto-info">
                                    <h3 class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                    <p class="producto-descripcion"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                    <p class="producto-precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                                    <p class="producto-status">Activo: <?php echo $producto['activo'] ? 'Sí' : 'No'; ?></p>
                                </div>
                                <div class="producto-actions">
                                    <a href="admin_productos.php?edit=<?php echo $producto['id']; ?>" class="btn btn-edit">Editar</a>
                                    <?php if ($producto['activo']): ?>
                                        <a href="admin_productos.php?deactivate=<?php echo $producto['id']; ?>&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>" 
                                           class="btn btn-deactivate" 
                                           onclick="return confirm('¿Estás seguro de desactivar este producto?')">
                                           Desactivar
                                        </a>
                                    <?php else: ?>
                                        <a href="admin_productos.php?reactivate=<?php echo $producto['id']; ?>&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>" 
                                           class="btn btn-reactivate" 
                                           onclick="return confirm('¿Estás seguro de reactivar este producto?')">
                                           Reactivar
                                        </a>
                                    <?php endif; ?>
                                    <a href="admin_productos.php?delete=<?php echo $producto['id']; ?>&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('¿Estás seguro de eliminar definitivamente este producto? Esta acción no se puede deshacer.')">
                                       Eliminar Definitivamente
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Close alert buttons
        document.querySelectorAll('.alert-close').forEach(button => {
            button.addEventListener('click', () => {
                button.parentElement.style.display = 'none';
            });
        });
    </script>
</body>
</html>