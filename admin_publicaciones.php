<?php
session_start();
require_once 'config.php'; 

$pdo->exec("SET time_zone = '-06:00'");

// Solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Solo administradores.";
    header("Location: login.php");
    exit;
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ────────────────────────────────────────────────
//          PROCESAMIENTO DE PETICIONES POST (AJAX)
// ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Verificación CSRF (obligatoria para todas las acciones POST)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        echo json_encode(['error' => true, 'message' => 'Error de seguridad (CSRF inválido).']);
        exit;
    }

    // ──── 1. EDITAR publicación ──── (primero porque es la más específica)
    if (isset($_POST['edit_publicacion'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            echo json_encode(['error' => true, 'message' => 'ID de publicación inválido.']);
            exit;
        }

        $contenido = trim($_POST['contenido'] ?? '');
        if (empty($contenido)) {
            echo json_encode(['error' => true, 'message' => 'El contenido es obligatorio.']);
            exit;
        }

        // Obtener datos actuales
        $stmt = $pdo->prepare("SELECT imagen, media_tipo, media_url FROM publicaciones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            echo json_encode(['error' => true, 'message' => 'Publicación no encontrada.']);
            exit;
        }

        $media      = $current['imagen'] ?? $current['media_url'] ?? null;
        $media_tipo = $current['media_tipo'] ?? 'none';

        // Procesar nueva media (si se subió)
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['media'];
            $mime = mime_content_type($file['tmp_name']);
            $size_limit = 50 * 1024 * 1024; // 50 MB

            if ($file['size'] > $size_limit) {
                echo json_encode(['error' => true, 'message' => 'Archivo demasiado grande (máx 50MB).']);
                exit;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $nombre_archivo = 'media_' . time() . '_' . uniqid() . '.' . $ext;
            $ruta_nueva = 'Uploads/' . $nombre_archivo;

            if (!move_uploaded_file($file['tmp_name'], $ruta_nueva)) {
                echo json_encode(['error' => true, 'message' => 'Error al mover el nuevo archivo.']);
                exit;
            }

            if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                $media_tipo = 'image';
            } elseif (in_array($mime, ['video/mp4', 'video/webm'])) {
                $media_tipo = 'video';
            } else {
                @unlink($ruta_nueva);
                echo json_encode(['error' => true, 'message' => 'Formato de archivo no soportado.']);
                exit;
            }

            // Borrar media antigua si existía
            if ($media && file_exists($media)) {
                @unlink($media);
            }

            $media = $ruta_nueva;
        }

        // Actualizar registro
        try {
            $stmt = $pdo->prepare("
                UPDATE publicaciones 
                SET contenido   = :contenido,
                    imagen      = :imagen,
                    media_tipo  = :media_tipo,
                    media_url   = :media_url,
                    fecha       = NOW()           -- quítalo si quieres conservar la fecha original
                WHERE id = :id
            ");
            $stmt->execute([
                ':contenido'   => $contenido,
                ':imagen'      => ($media_tipo === 'image' ? $media : null),
                ':media_tipo'  => $media_tipo,
                ':media_url'   => ($media_tipo === 'video' ? $media : null),
                ':id'          => $id
            ]);

            echo json_encode([
                'success'   => true,
                'message'   => 'Publicación actualizada correctamente.',
                'contenido' => htmlspecialchars($contenido)
            ]);
        } catch (PDOException $e) {
            // Rollback: borrar archivo nuevo si se subió
            if (isset($ruta_nueva) && file_exists($ruta_nueva)) {
                @unlink($ruta_nueva);
            }
            echo json_encode(['error' => true, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
        }
        exit;
    }

    // ──── 2. ELIMINAR publicación ────
    if (isset($_POST['delete_publicacion'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            echo json_encode(['error' => true, 'message' => 'ID de publicación inválido.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT imagen FROM publicaciones WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $pub = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pub) {
                echo json_encode(['error' => true, 'message' => 'Publicación no encontrada.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id = :id");
            $stmt->execute([':id' => $id]);

            if ($pub['imagen'] && file_exists($pub['imagen'])) {
                @unlink($pub['imagen']);
            }

            echo json_encode(['success' => true, 'message' => 'Publicación eliminada correctamente.']);
        } catch (PDOException $e) {
            echo json_encode(['error' => true, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
        }
        exit;
    }

    // ──── 3. CREAR publicación ──── (solo si se envía explícitamente el campo)
    if (isset($_POST['create_publicacion'])) {
        $contenido = trim($_POST['contenido'] ?? '');
        if (empty($contenido)) {
            echo json_encode(['error' => true, 'message' => 'El contenido es obligatorio.']);
            exit;
        }

        $media = null;
        $media_tipo = 'none';

        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['media'];
            $mime = mime_content_type($file['tmp_name']);
            $size_limit = 50 * 1024 * 1024;

            if ($file['size'] > $size_limit) {
                echo json_encode(['error' => true, 'message' => 'Archivo demasiado grande (máx 50MB).']);
                exit;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $nombre_archivo = 'media_' . time() . '_' . uniqid() . '.' . $ext;
            $ruta = 'Uploads/' . $nombre_archivo;

            if (!move_uploaded_file($file['tmp_name'], $ruta)) {
                echo json_encode(['error' => true, 'message' => 'Error al mover el archivo.']);
                exit;
            }

            $media = $ruta;

            if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                $media_tipo = 'image';
            } elseif (in_array($mime, ['video/mp4', 'video/webm'])) {
                $media_tipo = 'video';
            } else {
                @unlink($ruta);
                echo json_encode(['error' => true, 'message' => 'Formato no soportado.']);
                exit;
            }
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO publicaciones 
                (contenido, imagen, media_tipo, media_url, fecha, activo, creado_por)
                VALUES (:contenido, :imagen, :media_tipo, :media_url, NOW(), 1, :creado_por)
            ");
            $stmt->execute([
                ':contenido'   => $contenido,
                ':imagen'      => ($media_tipo === 'image' ? $media : null),
                ':media_tipo'  => $media_tipo,
                ':media_url'   => ($media_tipo === 'video' ? $media : null),
                ':creado_por'  => ($_SESSION['nombre'] ?? 'Admin') . ' (admin)'
            ]);

            echo json_encode(['success' => true, 'message' => 'Publicación creada correctamente.']);
        } catch (PDOException $e) {
            echo json_encode(['error' => true, 'message' => 'Error al guardar: ' . $e->getMessage()]);
        }
        exit;
    }

    // Fallback: acción no reconocida
    echo json_encode(['error' => true, 'message' => 'Acción no válida o no reconocida.']);
    exit;
}

// ────────────────────────────────────────────────
//          CARGA DE DATOS PARA MOSTRAR (GET)
// ────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        SELECT id, contenido, imagen, media_tipo, media_url, fecha
        FROM publicaciones
        ORDER BY fecha DESC
    ");
    $stmt->execute();
    $publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $publicaciones = [];
    // Opcional: $_SESSION['error'] = "Error al cargar publicaciones.";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Publicaciones - CAT21</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; color: #333; min-height: 100vh; line-height: 1.6; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #000000; color: #ffffff; padding: 20px; display: flex; flex-direction: column; gap: 20px; }
        .sidebar-header { display: flex; align-items: center; gap: 15px; padding-bottom: 20px; border-bottom: 1px solid #333; }
        .logo { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; transition: transform 0.3s ease; }
        .logo:hover { transform: rotate(5deg); }
        .sidebar h2 { font-size: 1.5rem; font-weight: 300; letter-spacing: 2px; text-transform: uppercase; }
        .sidebar-nav { display: flex; flex-direction: column; gap: 10px; }
        .nav-item { display: block; padding: 12px 20px; color: #ffffff; text-decoration: none; font-size: 1rem; font-weight: 400; border-radius: 4px; transition: all 0.3s ease; }
        .nav-item:hover { background: #FFD700; color: #000000; }
        .nav-item.active { background: #FFD700; color: #000000; }
        .main-content { flex: 1; padding: 40px; background: #ffffff; }
        .main-header h1 { font-size: 2rem; font-weight: 300; text-transform: uppercase; letter-spacing: 3px; color: #000000; margin-bottom: 30px; }
        .alert { position: relative; padding: 15px 40px 15px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; justify-content: space-between; }
        .alert-success { background: #e6f4ea; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background: #fce4ec; color: #c62828; border: 1px solid #ef9a9a; }
        .alert-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: inherit; }
        .form-card { background: #ffffff; border-radius: 8px; padding: 30px; margin-bottom: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; font-size: 1rem; border: 1px solid #ddd; border-radius: 6px; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group input:focus, .form-group textarea:focus { border-color: #FFD700; box-shadow: 0 0 6px rgba(255,215,0,0.3); outline: none; }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn { padding: 12px 24px; border-radius: 6px; font-size: 1rem; font-weight: 500; text-transform: uppercase; cursor: pointer; border: none; transition: all 0.3s ease; }
        .btn-submit { background: #000000; color: #FFD700; border: 1px solid #FFD700; }
        .btn-submit:hover { background: #FFD700; color: #000000; }
        .btn-delete { background: #c62828; color: white; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
        .btn-delete:hover { background: #b71c1c; }
        .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .data-table th { background: #000000; color: #FFD700; text-transform: uppercase; letter-spacing: 1px; }
        .data-table tr:hover { background: #f8f8f8; }
        .preview-img { max-width: 80px; height: auto; border-radius: 4px; }
        .content-short { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .btn-edit {
    background: #1976d2;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    margin-right: 8px;
}
.btn-edit:hover {
    background: #1565c0;
}
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/img/1-4.png" alt="CAT21 Logo" class="logo">
                <h2>CAT21 Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="inicio.php" class="nav-item">Inicio</a>
                <a href="reto.php" class="nav-item">Reto</a>
                <a href="ranking.php" class="nav-item">Ranking</a>
                <a href="admin_reto.php" class="nav-item">Administrar Reto</a>
                <a href="inventario.php" class="nav-item">Inventario</a>
                <a href="admin_publicaciones.php" class="nav-item active">Publicaciones</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1>Administrar Publicaciones</h1>
            </header>

            <div class="content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button class="alert-close">×</button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>




            <div class="form-card">
    <h2>Crear Nueva Publicación</h2>
    <form id="create-pub-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="create_publicacion" value="1">
        
        <div class="form-group">
            <label for="contenido">Contenido</label>
            <textarea id="contenido" name="contenido" required placeholder="Escribe el texto de la publicación..."></textarea>
        </div>
        
        <div class="form-group">
            <label for="media">Imagen o Video (opcional)</label>
            <input type="file" id="media" name="media" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm">
            <small>Máx 50MB para videos - JPG/PNG/WEBP o MP4/WEBM</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-submit">Publicar</button>
        </div>
    </form>
</div>


<div id="editModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div class="form-card" style="width:90%; max-width:600px; position:relative;">
        <button id="closeModal" style="position:absolute; top:10px; right:15px; font-size:24px; background:none; border:none; cursor:pointer;">×</button>
        <h2>Editar Publicación</h2>
    <form id="edit-pub-form" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="edit_publicacion" value="1">
    <input type="hidden" name="id" id="edit-id">
    
    <div class="form-group">
        <label for="edit-contenido">Contenido</label>
        <textarea id="edit-contenido" name="contenido" required style="min-height:160px;"></textarea>
    </div>
    
    <div class="form-group">
        <label>Media actual</label>
        <div id="current-media-preview" style="margin:10px 0;"></div>
    </div>
    
    <div class="form-group">
        <label for="edit-media">Cambiar imagen o video (opcional)</label>
        <input type="file" id="edit-media" name="media" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm">
        <small>Deja en blanco para mantener la actual. Máx 50MB para videos.</small>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-submit">Guardar Cambios</button>
        <button type="button" id="cancelEdit" class="btn" style="background:#666; color:white;">Cancelar</button>
    </div>
</form>
    </div>
</div>


                <div class="publicaciones-section">
                    <h2>Publicaciones Existentes</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Contenido</th>
                                <th>Imagen</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="pubs-table">
                            <?php foreach ($publicaciones as $pub): ?>
    <tr data-pub-id="<?= $pub['id'] ?>">
        <td><?= $pub['id'] ?></td>
        <td><?= date('d/m/Y H:i', strtotime($pub['fecha'])) ?></td>
        <td class="content-short">
            <?= htmlspecialchars(substr($pub['contenido'], 0, 100)) ?>...
        </td>
        <td>
            <?php if ($pub['imagen']): ?>
                <img src="<?= htmlspecialchars($pub['imagen']) ?>" alt="Preview" class="preview-img">
            <?php else: ?>
                Sin imagen
            <?php endif; ?>
        </td>
        <td>
            <button class="btn btn-delete delete-pub" 
                    data-id="<?= $pub['id'] ?>" 
                    data-contenido="<?= htmlspecialchars(substr($pub['contenido'], 0, 50)) ?>">
                Eliminar
            </button>

            <button class="btn btn-edit edit-pub" 
                    data-id="<?= $pub['id'] ?>" 
                    data-contenido="<?= htmlspecialchars($pub['contenido']) ?>"
                    data-media="<?= htmlspecialchars($pub['imagen'] ?? $pub['media_url'] ?? '') ?>"
                    data-media-tipo="<?= htmlspecialchars($pub['media_tipo'] ?? 'none') ?>">
                Editar
            </button>
        </td>
    </tr>
<?php endforeach; ?>
                            <?php if (empty($publicaciones)): ?>
                                <tr><td colspan="5">No hay publicaciones aún.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', () => btn.parentElement.remove());
        });

      
        document.getElementById('create-pub-form').addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const btn = e.target.querySelector('.btn-submit');
            btn.disabled = true;
            btn.textContent = 'Publicando...';

            try {
                const res = await fetch('', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Error desconocido');
                }
            } catch (err) {
                alert('Error de conexión: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Publicar';
            }
        });


        document.querySelectorAll('.delete-pub').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const cont = btn.dataset.contenido;

                if (!confirm(`¿Eliminar publicación "${cont}..."?`)) return;
                if (!confirm('¡Esta acción es irreversible! ¿Estás seguro?')) return;

                const formData = new FormData();
                formData.append('csrf_token', '<?= htmlspecialchars($csrf_token) ?>');
                formData.append('delete_publicacion', '1');
                formData.append('id', id);

                try {
                    btn.disabled = true;
                    btn.textContent = 'Eliminando...';

                    const res = await fetch('', { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.success) {
                        btn.closest('tr').remove();
                        alert(data.message);
                    } else {
                        alert(data.message || 'No se pudo eliminar');
                    }
                } catch (err) {
                    alert('Error de conexión: ' + err.message);
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Eliminar';
                }
            });
        });


        // Abrir modal de edición
document.querySelectorAll('.edit-pub').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const contenido = btn.dataset.contenido;

        document.getElementById('edit-id').value = id;
        document.getElementById('edit-contenido').value = contenido;

       
        // Para esto debes modificar la tabla: agregar data-media="<?= htmlspecialchars($pub['imagen'] ?? $pub['media_url'] ?? '') ?>"
        // y data-media-tipo="<?= $pub['media_tipo'] ?? 'none' ?>"
        
        const mediaUrl = btn.dataset.media || '';  
        const mediaTipo = btn.dataset.mediaTipo || 'none';
        const previewDiv = document.getElementById('current-media-preview');
        previewDiv.innerHTML = '';

        if (mediaUrl) {
            if (mediaTipo === 'image') {
                previewDiv.innerHTML = `<img src="${mediaUrl}" alt="Media actual" style="max-width:200px; border-radius:6px;">`;
            } else if (mediaTipo === 'video') {
                previewDiv.innerHTML = `<video src="${mediaUrl}" controls style="max-width:300px; border-radius:6px;"></video>`;
            } else {
                previewDiv.innerHTML = `<p>Media actual: ${mediaUrl}</p>`;
            }
        } else {
            previewDiv.innerHTML = '<p>Sin media actual</p>';
        }

        document.getElementById('editModal').style.display = 'flex';
    });
});

document.getElementById('edit-pub-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('.btn-submit');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Guardando...';

    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message || 'Publicación actualizada');
            document.getElementById('editModal').style.display = 'none';
            location.reload();           // ← refresca para ver cambios
        } else {
            alert(data.message || 'Error al guardar los cambios');
        }
    } catch (err) {
        console.error('Error en fetch:', err);
        alert('Error de conexión o servidor: ' + err.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

    </script>
</body>
</html>