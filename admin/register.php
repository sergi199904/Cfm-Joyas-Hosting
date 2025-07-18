<?php
session_start();
require_once '../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = limpiar_input($_POST['email']);
    $nombre = limpiar_input($_POST['nombre']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $codigo_acceso = limpiar_input($_POST['codigo_acceso']);
    
    // Validaciones
    if (empty($email) || empty($nombre) || empty($password) || empty($codigo_acceso)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!validar_codigo_acceso($codigo_acceso)) {
        $error = 'Código de acceso inválido. Use: CFM2025, JOYAS2025 o ADMIN2025';
    } else {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $error = 'El email ya está registrado.';
        } else {
            // Crear usuario
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO usuarios (email, nombre, password, codigo_acceso, activo) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param('ssss', $email, $nombre, $hash, $codigo_acceso);
            
            if ($stmt->execute()) {
                $success = 'Cuenta creada exitosamente. Ya puedes iniciar sesión.';
            } else {
                $error = 'Error al crear la cuenta. Intente nuevamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear Administrador - CFM Joyas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Essential custom styles that can't be replaced with Bootstrap */
        body { 
            background: linear-gradient(135deg, #000 0%, #2c2c2c 100%) !important; 
            min-height: 100vh; 
        }

        .register-card { 
            backdrop-filter: blur(15px); 
            background: rgba(0,0,0,0.8) !important; 
            border: 2px solid rgba(255, 215, 0, 0.3) !important;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .form-control:focus {
            border-color: #ffd700 !important;
            box-shadow: 0 0 0 0.2rem rgba(255,215,0,0.25) !important;
        }

        .btn-warning {
            background: linear-gradient(45deg, #ffd700, #ffb347) !important;
            border: none !important;
            color: #000 !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3) !important;
        }

        .btn-warning:hover {
            background: linear-gradient(45deg, #ffb347, #ffa500) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5) !important;
            color: #000 !important;
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997) !important;
            border: none !important;
            color: white !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3) !important;
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #20c997, #17a2b8) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.5) !important;
        }

        .code-help {
            background: rgba(255, 215, 0, 0.1) !important;
            border: 1px solid rgba(255, 215, 0, 0.3) !important;
        }

        .code-help code {
            background: rgba(255, 215, 0, 0.2) !important;
            color: #ffd700 !important;
            font-weight: 600;
        }

        .text-warning { 
            color: #ffd700 !important; 
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5) !important;
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card register-card shadow-lg rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-3x text-warning mb-3"></i>
                            <h3 class="text-white">Crear Administrador</h3>
                            <p class="text-light">CFM Joyas - Acceso Seguro</p>
                        </div>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger border-0 rounded-3" style="background: rgba(220,53,69,0.9) !important; color: white !important; border: 2px solid rgba(220,53,69,0.5) !important;">
                                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success border-0 rounded-3" style="background: rgba(40,167,69,0.9) !important; color: white !important; border: 2px solid rgba(40,167,69,0.5) !important;">
                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-success rounded-3" style="padding: 12px 25px !important;">
                                    <i class="fas fa-sign-in-alt"></i> Ir a Iniciar Sesión
                                </a>
                            </div>
                        <?php else: ?>
                        
                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="nombre" class="form-label text-white fw-semibold">
                                    <i class="fas fa-user"></i> Nombre Completo
                                </label>
                                <input type="text" name="nombre" id="nombre" class="form-control rounded-3" 
                                       style="background: rgba(255,255,255,0.9) !important; border: 2px solid rgba(255, 215, 0, 0.3) !important; padding: 12px 15px !important; transition: all 0.3s ease !important;"
                                       required placeholder="Tu nombre completo"
                                       value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label text-white fw-semibold">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" name="email" id="email" class="form-control rounded-3" 
                                       style="background: rgba(255,255,255,0.9) !important; border: 2px solid rgba(255, 215, 0, 0.3) !important; padding: 12px 15px !important; transition: all 0.3s ease !important;"
                                       required placeholder="tu@email.com"
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label text-white fw-semibold">
                                    <i class="fas fa-lock"></i> Contraseña
                                </label>
                                <input type="password" name="password" id="password" class="form-control rounded-3" 
                                       style="background: rgba(255,255,255,0.9) !important; border: 2px solid rgba(255, 215, 0, 0.3) !important; padding: 12px 15px !important; transition: all 0.3s ease !important;"
                                       required minlength="6" placeholder="Mínimo 6 caracteres">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm" class="form-label text-white fw-semibold">
                                    <i class="fas fa-lock"></i> Confirmar Contraseña
                                </label>
                                <input type="password" name="confirm" id="confirm" class="form-control rounded-3" 
                                       style="background: rgba(255,255,255,0.9) !important; border: 2px solid rgba(255, 215, 0, 0.3) !important; padding: 12px 15px !important; transition: all 0.3s ease !important;"
                                       required placeholder="Repite tu contraseña">
                            </div>
                            
                            <div class="mb-4">
                                <label for="codigo_acceso" class="form-label text-white fw-semibold">
                                    <i class="fas fa-key"></i> Código de Acceso Especial
                                </label>
                                <input type="text" name="codigo_acceso" id="codigo_acceso" class="form-control rounded-3" 
                                       style="background: rgba(255,255,255,0.9) !important; border: 2px solid rgba(255, 215, 0, 0.3) !important; padding: 12px 15px !important; transition: all 0.3s ease !important;"
                                       required placeholder="Código CFM Joyas"
                                       value="<?= isset($_POST['codigo_acceso']) ? htmlspecialchars($_POST['codigo_acceso']) : '' ?>">
                                
                                <div class="code-help mt-2 p-3 rounded-3">
                                    <small class="text-light">
                                        <i class="fas fa-info-circle"></i> <strong>Códigos válidos:</strong><br>
                                        • <code class="px-2 py-1 rounded">CFM2025</code><br>
                                        • <code class="px-2 py-1 rounded">JOYAS2025</code><br>
                                        • <code class="px-2 py-1 rounded">ADMIN2025</code>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning btn-lg rounded-3" style="padding: 12px 25px !important;">
                                    <i class="fas fa-user-plus"></i> Crear Cuenta
                                </button>
                            </div>
                        </form>
                        
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="login.php" class="text-decoration-none me-3" style="color: #ffd700 !important; transition: all 0.3s ease;">
                                <i class="fas fa-arrow-left"></i> Ya tengo cuenta
                            </a>
                            <a href="../index.php" class="text-decoration-none" style="color: #ffd700 !important; transition: all 0.3s ease;">
                                <i class="fas fa-home"></i> Volver al sitio
                            </a>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-light">
                                <i class="fas fa-shield-alt"></i> Solo para administradores autorizados de CFM Joyas
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación en tiempo real de contraseñas
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm');
            
            function validatePasswords() {
                if (confirm.value && password.value !== confirm.value) {
                    confirm.setCustomValidity('Las contraseñas no coinciden');
                    confirm.classList.add('is-invalid');
                } else {
                    confirm.setCustomValidity('');
                    confirm.classList.remove('is-invalid');
                }
            }
            
            password.addEventListener('input', validatePasswords);
            confirm.addEventListener('input', validatePasswords);
        });
    </script>
</body>
</html>