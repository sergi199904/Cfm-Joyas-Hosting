<?php
// =======================================================
// ADMIN LOGIN - CFM JOYAS - VERSIÓN SEGURA
// admin/login.php
// IMPORTANTE: Códigos de acceso ahora en variables de entorno
// =======================================================

// INICIAR SESIÓN DE FORMA SEGURA ANTES QUE NADA
$session_dir = __DIR__ . '/../tmp/sessions';
if (!is_dir($session_dir)) {
    mkdir($session_dir, 0755, true);
}

ini_set('session.save_path', $session_dir);
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración de base de datos
require_once '../includes/db.php';

$error = '';
$bloqueado = false;
$success = '';

// Verificar si viene de logout exitoso
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = 'Sesión cerrada exitosamente. ¡Hasta pronto!';
}

// Verificar si viene de registro exitoso
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success = 'Cuenta creada exitosamente. Ya puedes iniciar sesión.';
}

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Verificar que la sesión no haya expirado
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) < 3600) {
        header('Location: dashboard.php');
        exit;
    } else {
        // Sesión expirada, limpiar
        session_destroy();
        $error = 'Tu sesión había expirado. Por favor, inicia sesión nuevamente.';
    }
}

// También verificar autenticación por cookies como backup
if (!$error) {
    $auth_data = verifyAuthCookie();
    if ($auth_data) {
        // Crear sesión desde cookie válida
        $_SESSION['user_id'] = $auth_data['user_id'];
        $_SESSION['user_name'] = $auth_data['user_name'];
        $_SESSION['user_email'] = $auth_data['user_email'];
        $_SESSION['login_time'] = time();
        
        error_log("CFM Login: Autenticado automáticamente por cookie válida - user_id: " . $auth_data['user_id']);
        header('Location: dashboard.php');
        exit;
    }
}

// PROCESAR LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = limpiar_input($_POST['email']);
    $password = $_POST['password'];
    $codigo_acceso = limpiar_input($_POST['codigo_acceso']);
    
    // Log del intento de login (para debugging)
    error_log("CFM Login attempt: email=$email, code=$codigo_acceso, time=" . date('Y-m-d H:i:s'));
    
    // Verificar si puede intentar login
    if (!verificar_intentos_login($email)) {
        $error = 'Cuenta temporalmente bloqueada por seguridad. Intente en 15 minutos.';
        $bloqueado = true;
        error_log("CFM Login blocked: $email");
    } else {
        // Verificar código de acceso primero
        if (!validar_codigo_acceso($codigo_acceso)) {
            registrar_intento_fallido($email);
            $error = 'Código de acceso inválido. Contacte al administrador para obtener un código válido.';
            error_log("CFM Invalid access code: $codigo_acceso for $email");
        } else {
            // Buscar usuario en la base de datos
            try {
                $stmt = $conn->prepare("SELECT id, password, nombre FROM usuarios WHERE email = ? AND activo = 1");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($user = $result->fetch_assoc()) {
                    // Verificar contraseña
                    if (password_verify($password, $user['password'])) {
                        
                        // *** LOGIN EXITOSO ***
                        error_log("CFM Login SUCCESS: user_id=" . $user['id'] . ", email=$email");
                        
                        // MÉTODO 1: Intentar crear sesión PHP
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['nombre'];
                        $_SESSION['user_email'] = $email;
                        $_SESSION['login_time'] = time();
                        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        
                        // MÉTODO 2: Crear cookie de respaldo
                        createAuthCookie($user['id'], $user['nombre'], $email);
                        
                        // Limpiar intentos fallidos
                        limpiar_intentos($email);
                        
                        // Log de sesión creada
                        error_log("CFM Session created: user_id=" . $user['id'] . ", session_id=" . session_id());
                        
                        // Redirigir al dashboard
                        header('Location: dashboard.php');
                        exit;
                        
                    } else {
                        registrar_intento_fallido($email);
                        $error = 'Contraseña incorrecta.';
                        error_log("CFM Wrong password for: $email");
                    }
                } else {
                    registrar_intento_fallido($email);
                    $error = 'Usuario no encontrado o inactivo.';
                    error_log("CFM User not found: $email");
                }
                
            } catch (Exception $e) {
                $error = 'Error del sistema. Intente más tarde.';
                error_log("CFM Login error: " . $e->getMessage());
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
    <title>Admin - Login Seguro CFM Joyas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Essential custom styles that can't be replaced with Bootstrap */
        body { 
            background: linear-gradient(135deg, #000 0%, #2c2c2c 100%) !important; 
            min-height: 100vh; 
        }

        .login-card { 
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

        .secret-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 12px;
            height: 12px;
            background: rgba(255, 215, 0, 0.4) !important;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.3;
        }

        .secret-btn:hover {
            opacity: 1;
            background: rgba(255, 215, 0, 0.8) !important;
            transform: scale(1.3);
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .text-warning { 
            color: #ffd700 !important; 
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5) !important;
        }

        .debug-info {
            background: rgba(0,0,0,0.8);
            border: 1px solid #444;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card shadow-lg position-relative rounded-4">
                    
                    <!-- BOTÓN PARA REGISTRO - OCULTO POR REQUERIMIENTO -->
                    <!-- 
                    <button onclick="window.location.href='register.php'" 
                            class="secret-btn" 
                            title="Crear cuenta de administrador"></button>
                    -->
                    
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-gem fa-3x text-warning mb-3"></i>
                            <h3 class="text-white">CFM Joyas Admin</h3>
                            <p class="text-light">Panel de Administración</p>
                        </div>
                        
                        <!-- ALERTAS DE ÉXITO -->
                        <?php if($success): ?>
                            <div class="alert alert-success alert-dismissible fade show border-0 rounded-3" style="background: rgba(40,167,69,0.9) !important; color: white !important; border: 2px solid rgba(40,167,69,0.5) !important;">
                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- ALERTAS DE ERROR -->
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3" style="background: rgba(220,53,69,0.9) !important; color: white !important; border: 2px solid rgba(220,53,69,0.5) !important;">
                                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($bloqueado): ?>
                            <div class="alert alert-warning border-0 rounded-3" style="background: rgba(255,193,7,0.9) !important; color: #000 !important; border: 2px solid rgba(255,193,7,0.5) !important;">
                                <i class="fas fa-lock"></i> Por seguridad, intente más tarde.
                            </div>
                        <?php endif; ?>
                        
                        <!-- FORMULARIO DE LOGIN -->
                        <form method="POST" novalidate id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label text-white fw-semibold">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" name="email" id="email" class="form-control rounded-3" 
                                       style="background: rgba(255,255,255,0.9) !important; border: 2px solid rgba(255, 215, 0, 0.3) !important; padding: 12px 15px !important; transition: all 0.3s ease !important;"
                                       required 
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                       placeholder="admin@cfmjoyas.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label text-white fw-semibold">
                                    <i class="fas fa-lock"></i> Contraseña
                                </label>
                                <div class="position-relative">
                                    <input type="password" name="password" id="password" class="form-control rounded-3" 
                                           style="background: rgba(255,255,255,0.9) !important; border: 2px solid rgba(255, 215, 0, 0.3) !important; padding: 12px 15px !important; transition: all 0.3s ease !important;"
                                           required minlength="6" placeholder="Tu contraseña">
                                    <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 border-0"
                                            style="background: none; color: #666;"
                                            onclick="togglePassword()" id="togglePasswordBtn">
                                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="codigo_acceso" class="form-label text-white fw-semibold">
                                    <i class="fas fa-key"></i> Código de Acceso
                                </label>
                                <input type="text" name="codigo_acceso" id="codigo_acceso" class="form-control rounded-3" 
                                       style="background: rgba(255,255,255,0.9) !important; border: 2px solid rgba(255, 215, 0, 0.3) !important; padding: 12px 15px !important; transition: all 0.3s ease !important;"
                                       required 
                                       placeholder="Código de acceso requerido"
                                       value="<?= isset($_POST['codigo_acceso']) ? htmlspecialchars($_POST['codigo_acceso']) : '' ?>">
                                <small class="text-light">
                                    <i class="fas fa-info-circle"></i> Solicite el código de acceso al administrador
                                </small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning btn-lg rounded-3" <?= $bloqueado ? 'disabled' : '' ?> id="submitBtn"
                                        style="padding: 12px 25px !important;">
                                    <span id="submitText">
                                        <i class="fas fa-sign-in-alt"></i> Ingresar al Panel
                                    </span>
                                </button>
                            </div>
                        </form>
                        
                        <!-- ENLACES -->
                        <div class="text-center mt-4">
                            <a href="../index.php" class="text-decoration-none" style="color: #ffd700 !important; transition: all 0.3s ease;">
                                <i class="fas fa-arrow-left"></i> Volver al sitio web
                            </a>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-light">
                                <i class="fas fa-shield-alt"></i> Acceso protegido con doble autenticación
                            </small>
                        </div>

                        <!-- INFORMACIÓN DE DEBUG (solo mostrar si hay errores y debug está activado) -->
                        <?php if ($error && isset($_GET['debug'])): ?>
                        <div class="debug-info mt-3 p-3 rounded-3 small">
                            <strong>Debug Info:</strong><br>
                            Session ID: <?= session_id() ?><br>
                            Session Path: <?= session_save_path() ?><br>
                            Session Status: <?= session_status() ?><br>
                            Writable: <?= is_writable(session_save_path()) ? 'YES' : 'NO' ?><br>
                            Time: <?= date('Y-m-d H:i:s') ?><br>
                            <small>Para ver debug: ?debug=1</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            
            // Auto-focus en el primer campo vacío
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const codigo = document.getElementById('codigo_acceso');
            
            if (!email.value) {
                email.focus();
            } else if (!password.value) {
                password.focus();
            } else if (!codigo.value) {
                codigo.focus();
            }
            
            // Manejar envío del formulario
            form.addEventListener('submit', function(e) {
                // Validar campos
                if (!email.value || !password.value || !codigo.value) {
                    e.preventDefault();
                    showError('Por favor complete todos los campos');
                    return;
                }
                
                // Mostrar estado de carga
                submitBtn.disabled = true;
                submitBtn.classList.add('loading-btn');
                submitText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
                
                // El formulario se enviará normalmente
            });
            
            // Auto-dismiss alerts después de 5 segundos
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                setTimeout(() => {
                    try {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    } catch (e) {
                        // Si no se puede cerrar automáticamente, no hacer nada
                    }
                }, 5000);
            });
            
            // Validación en tiempo real
            email.addEventListener('blur', function() {
                if (this.value && !isValidEmail(this.value)) {
                    this.classList.add('is-invalid');
                } else if (this.value) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
            
            password.addEventListener('input', function() {
                if (this.value.length >= 6) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else if (this.value.length > 0) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            });
            
            codigo.addEventListener('input', function() {
                // Removed client-side validation - codes are now in environment variables
                // Validation is done server-side for security
                if (this.value.length > 0) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.remove('is-invalid');
                }
            });
        });
        
        // Función para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Función para validar email
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Función para mostrar errores
        function showError(message) {
            // Remover alertas existentes
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Crear nueva alerta
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i> ${message}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            `;
            
            // Insertar antes del formulario
            const form = document.getElementById('loginForm');
            form.parentNode.insertBefore(alertDiv, form);
        }
        
        // Efecto en el botón secreto
        document.querySelector('.secret-btn').addEventListener('mouseenter', function() {
            this.style.opacity = '1';
            this.title = 'Crear nueva cuenta de administrador';
        });
        
        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter para enviar formulario
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('loginForm').submit();
            }
            
            // Escape para limpiar formulario
            if (e.key === 'Escape') {
                document.getElementById('loginForm').reset();
                document.getElementById('email').focus();
            }
        });
        
        // Prevenir ataques de fuerza bruta básicos
        let loginAttempts = 0;
        const maxAttempts = 5;
        
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            loginAttempts++;
            
            if (loginAttempts > maxAttempts) {
                e.preventDefault();
                showError('Demasiados intentos. Recarga la página para continuar.');
                return false;
            }
        });
        
        console.log('CFM Joyas Login loaded');
        console.log('Session ID:', '<?= session_id() ?>');
        console.log('Session Status:', '<?= session_status() ?>');
    </script>
</body>
</html>