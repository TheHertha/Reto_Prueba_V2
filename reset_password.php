<?php
session_start();
require_once 'config.php';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
unset($_SESSION['success'], $_SESSION['error']);

// Debug: Verify $pdo is defined
if (!isset($pdo)) {
    $_SESSION['error'] = 'Error: Database connection not initialized in config.php';
    header('Location: recuperar.php');
    exit;
}

// Validate token
$token = $_GET['token'] ?? '';
$email = '';
$valid_token = false;

if ($token) {
    try {
        $stmt = $pdo->prepare('SELECT email, expires_at FROM password_reset_tokens WHERE token = ?');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && strtotime($row['expires_at']) > time()) {
            $email = $row['email'];
            $valid_token = true;
        } else {
            $error = 'El enlace de recuperación es inválido o ha expirado.';
        }
    } catch (Exception $e) {
        $error = 'Error del servidor: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Restablecer Contraseña</title>
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

        .reset-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 40px 60px;
            background-image: url('assets/img/AF1.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        .reset-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }

        .reset-card {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: row;
            width: 100%;
            max-width: 1200px;
            min-height: 600px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #333333;
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: fadeIn 0.8s ease-out;
        }

        .reset-image {
            flex: 1;
            background: linear-gradient(135deg, #000000, #333333);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            border-right: 1px solid #333333;
            position: relative;
        }

        .reset-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .reset-image img {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 300px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.4));
            transition: all 0.3s ease;
        }

        .reset-image img:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 30px rgba(255, 215, 0, 0.6));
        }

        .reset-form-container {
            flex: 1.5;
            padding: 60px;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #000000;
            position: relative;
        }

        .reset-form-container h1 {
            font-size: 48px;
            font-weight: 100;
            margin-bottom: 40px;
            letter-spacing: 3px;
            text-transform: uppercase;
            text-align: center;
            color: #000000;
            border-left: 3px solid #FFD700;
            padding-left: 20px;
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 300;
            margin-bottom: 8px;
            color: #000000;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .form-group input[type="password"] {
            width: 100%;
            padding: 15px 0;
            border: none;
            border-bottom: 1px solid #333333;
            background: transparent;
            color: #000000;
            font-size: 16px;
            font-weight: 300;
            transition: all 0.3s ease;
            outline: none;
            pointer-events: auto;
        }

        .form-group input[type="password"]:focus {
            border-bottom: 2px solid #FFD700;
        }

        .form-group input[type="password"]::placeholder {
            color: #999999;
            font-weight: 300;
        }

        .reset-btn {
            background: transparent;
            color: #000000;
            border: 1px solid #000000;
            padding: 16px 32px;
            font-size: 14px;
            font-weight: 400;
            cursor: pointer;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .reset-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: #000000;
            transition: left 0.4s ease;
            z-index: -1;
        }

        .reset-btn:hover::before {
            left: 0;
        }

        .reset-btn:hover {
            color: #ffffff;
            border-color: #000000;
            box-shadow: 0 0 0 2px #FFD700;
        }

        .form-links {
            display: flex;
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .form-links a {
            color: #000000;
            text-decoration: none;
            font-size: 12px;
            font-weight: 300;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            position: relative;
        }

        .form-links a::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 1px;
            background: #FFD700;
            transition: width 0.3s ease;
        }

        .form-links a:hover::after {
            width: 100%;
        }

        .form-links a:hover {
            color: #FFD700;
        }

        .success, .error {
            padding: 15px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 400;
            font-size: 14px;
            border-left: 3px solid;
            letter-spacing: 0.5px;
        }

        .success {
            background: rgba(212, 237, 218, 0.9);
            color: #155724;
            border-color: #28a745;
        }

        .error {
            background: rgba(248, 215, 218, 0.9);
            color: #721c24;
            border-color: #dc3545;
        }

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

        @media (max-width: 768px) {
            .reset-container {
                padding: 20px 30px;
            }
            
            .reset-card {
                flex-direction: column;
                min-height: auto;
            }
            
            .reset-image {
                border-right: none;
                border-bottom: 1px solid #333333;
                padding: 40px;
            }
            
            .reset-form-container {
                padding: 40px 30px;
            }
            
            .reset-form-container h1 {
                font-size: 32px;
                margin-bottom: 30px;
            }
            
            .form-group {
                margin-bottom: 25px;
            }
            
            .reset-btn {
                padding: 14px 24px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .reset-container {
                padding: 15px;
            }
            
            .reset-form-container {
                padding: 30px 20px;
            }
            
            .reset-form-container h1 {
                font-size: 24px;
                letter-spacing: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-image">
                <img src="assets/img/1-4.png" alt="Logo CAT 21">
            </div>
            <div class="reset-form-container">
                <h1>Restablecer Contraseña</h1>
                
                <?php if (!empty($success)): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($valid_token): ?>
                    <form action="reset_password.php" method="POST" id="resetForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label for="new_password">Nueva Contraseña</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Ingresa tu nueva contraseña" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmar Contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirma tu nueva contraseña" required>
                        </div>

                        <button type="submit" class="reset-btn">Restablecer Contraseña</button>

                        <div class="form-links">
                            <a href="login.php">Volver al inicio de sesión</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="error">Enlace inválido o expirado. <a href="recuperar.php">Solicita un nuevo enlace</a>.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        <?php if ($valid_token): ?>
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden.');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Error de seguridad. Intente de nuevo.';
        header("Location: reset_password.php?token=$token");
        exit;
    }

    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'Las contraseñas no coinciden.';
        header("Location: reset_password.php?token=$token");
        exit;
    }

    if (strlen($new_password) < 8) {
        $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres.';
        header("Location: reset_password.php?token=$token");
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT email, expires_at FROM password_reset_tokens WHERE token = ?');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && strtotime($row['expires_at']) > time()) {
            $email = $row['email'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password in users table
            $stmt = $pdo->prepare('UPDATE users SET contrasena = ? WHERE email = ?');
            $stmt->execute([$hashed_password, $email]);

            // Delete used token
            $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE token = ?');
            $stmt->execute([$token]);

            $_SESSION['success'] = 'Contraseña restablecida con éxito. Inicia sesión con tu nueva contraseña.';
            header('Location: login.php');
            exit;
        } else {
            $_SESSION['error'] = 'El enlace de recuperación es inválido o ha expirado.';
            header("Location: reset_password.php?token=$token");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error del servidor: ' . $e->getMessage();
        header("Location: reset_password.php?token=$token");
        exit;
    }
}
?>