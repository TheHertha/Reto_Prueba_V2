<?php
session_start();
require_once 'config.php';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
unset($_SESSION['success'], $_SESSION['error']);

// Define allowed email domains (same as login.php and registro.php)
$ALLOWED_EMAIL_DOMAINS = [
    '@gmail.com' => '@gmail.com',
    '@hotmail.com' => '@hotmail.com',
    '@outlook.com' => '@outlook.com'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Recuperar Contraseña</title>
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

        .recovery-container {
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

        .recovery-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }

        .recovery-card {
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

        .recovery-image {
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

        .recovery-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .recovery-image img {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 300px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.4));
            transition: all 0.3s ease;
        }

        .recovery-image img:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 30px rgba(255, 215, 0, 0.6));
        }

        .recovery-form-container {
            flex: 1.5;
            padding: 60px;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #000000;
            position: relative;
        }

        .recovery-form-container h1 {
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

        .email-group {
            display: flex !important;
            flex-direction: row;
            align-items: flex-end;
            gap: 10px;
            width: 100%;
        }

        .email-input {
            flex: 1 !important;
            min-width: 0;
        }

        .email-domain {
            flex: 0 0 150px !important;
            width: 150px !important;
            max-width: 150px;
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

        .form-group input[type="text"],
        .form-group select {
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

        .form-group input[type="text"]:focus,
        .form-group select:focus {
            border-bottom: 2px solid #FFD700;
        }

        .form-group input[type="text"]::placeholder {
            color: #999999;
            font-weight: 300;
        }

        .form-group select {
            cursor: pointer;
        }

        .form-group select option {
            background: #ffffff;
            color: #000000;
            padding: 10px;
        }

        .recovery-btn {
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

        .recovery-btn::before {
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

        .recovery-btn:hover::before {
            left: 0;
        }

        .recovery-btn:hover {
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
            .recovery-container {
                padding: 20px 30px;
            }
            
            .recovery-card {
                flex-direction: column;
                min-height: auto;
            }
            
            .recovery-image {
                border-right: none;
                border-bottom: 1px solid #333333;
                padding: 40px;
            }
            
            .recovery-form-container {
                padding: 40px 30px;
            }
            
            .recovery-form-container h1 {
                font-size: 32px;
                margin-bottom: 30px;
            }
            
            .form-group {
                margin-bottom: 25px;
            }
            
            .recovery-btn {
                padding: 14px 24px;
                font-size: 12px;
            }
            
            .email-group {
                flex-direction: column !important;
                gap: 15px;
            }
            
            .email-domain {
                width: 100% !important;
                max-width: none !important;
            }
        }

        @media (max-width: 480px) {
            .recovery-container {
                padding: 15px;
            }
            
            .recovery-form-container {
                padding: 30px 20px;
            }
            
            .recovery-form-container h1 {
                font-size: 24px;
                letter-spacing: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="recovery-card">
            <div class="recovery-image">
                <img src="assets/img/1-4.png" alt="Logo CAT 21">
            </div>
            <div class="recovery-form-container">
                <h1>Recuperar Contraseña</h1>
                
                <?php if (!empty($success)): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form action="send_reset_link.php" method="POST" id="recoveryForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="email" id="fullEmail">
                    
                    <div class="form-group">
                        <label for="emailUsername">Correo electrónico</label>
                        <div class="email-group">
                            <input type="text" id="emailUsername" class="email-input" placeholder="pablo123" required>
                            <select id="emailDomain" class="email-domain" required>
                                <option value="" disabled selected>Seleccione dominio</option>
                                <?php foreach ($ALLOWED_EMAIL_DOMAINS as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="recovery-btn">Enviar enlace de recuperación</button>

                    <div class="form-links">
                        <a href="login.php">Volver al inicio de sesión</a>
                        <a href="register.php">¿No tienes cuenta? Regístrate aquí</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Ensure the email username input is fully editable
        const emailUsernameInput = document.getElementById('emailUsername');
        emailUsernameInput.removeAttribute('readonly');
        emailUsernameInput.removeAttribute('disabled');
        emailUsernameInput.style.pointerEvents = 'auto';
        emailUsernameInput.style.userSelect = 'auto';
        emailUsernameInput.focus();

        // Debug: Log input and layout state
        console.log('emailUsername input - readonly:', emailUsernameInput.readOnly, 'disabled:', emailUsernameInput.disabled);
        console.log('email-group computed style:', getComputedStyle(document.querySelector('.email-group')).display);
        console.log('email-domain computed width:', getComputedStyle(document.querySelector('.email-domain')).width);
        console.log('email-input computed width:', getComputedStyle(document.querySelector('.email-input')).width);

        function validateEmail() {
            const emailUsername = document.getElementById('emailUsername').value.trim();
            const emailDomain = document.getElementById('emailDomain').value;
            const emailUsernameRegex = /^[^\s@]{1,}$/;

            if (!emailUsernameRegex.test(emailUsername)) {
                alert('El nombre de usuario del correo debe contener al menos un carácter y no debe incluir espacios ni @.');
                return false;
            }

            if (!emailDomain) {
                alert('Por favor, seleccione un dominio de correo.');
                return false;
            }

            document.getElementById('fullEmail').value = emailUsername + emailDomain;
            return true;
        }

        document.getElementById('recoveryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateEmail()) {
                document.getElementById('fullEmail').value = document.getElementById('emailUsername').value.trim() + document.getElementById('emailDomain').value;
                this.submit();
            }
        });

        document.getElementById('emailUsername').addEventListener('input', function() {
            const emailUsername = this.value.trim();
            const emailDomain = document.getElementById('emailDomain').value;
            document.getElementById('fullEmail').value = emailUsername && emailDomain ? emailUsername + emailDomain : '';
        });

        document.getElementById('emailDomain').addEventListener('change', function() {
            const emailUsername = document.getElementById('emailUsername').value.trim();
            const emailDomain = this.value;
            document.getElementById('fullEmail').value = emailUsername && emailDomain ? emailUsername + emailDomain : '';
        });
    </script>
</body>
</html>