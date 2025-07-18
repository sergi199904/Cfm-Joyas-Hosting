<?php
// =======================================================
// TEST DE SESIONES PARA CFM JOYAS
// admin/test_session.php - ARCHIVO COMPLETO PARA DIAGNOSTICAR PROBLEMAS
// =======================================================

// Configurar sesiones igual que en los otros archivos
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

// Incluir funciones de base de datos
require_once '../includes/db.php';

// Procesar acciones de limpieza ANTES del HTML
if (isset($_GET['clear_session'])) {
    session_destroy();
    header('Location: test_session.php?msg=session_cleared');
    exit;
}

if (isset($_GET['clear_cookies'])) {
    clearAuthCookie();
    setcookie('cfm_test', '', time() - 3600, '/');
    header('Location: test_session.php?msg=cookies_cleared');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test de Sesiones - CFM Joyas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
            font-family: 'Inter', sans-serif;
        }
        .test-card { 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .code-block { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            border-radius: 8px; 
            padding: 15px; 
            font-family: 'Courier New', monospace; 
            font-size: 0.9rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .test-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }
        .result-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8rem;
        }
        .card-header {
            position: relative;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- HEADER PRINCIPAL -->
            <div class="card test-card">
                <div class="card-header test-header text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-bug"></i> CFM Joyas - Test de Sesiones
                    </h2>
                    <p class="mb-0">Diagnóstico completo del sistema de autenticación</p>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <?php
                            switch($_GET['msg']) {
                                case 'session_cleared':
                                    echo '<i class="fas fa-check"></i> Sesión limpiada exitosamente';
                                    break;
                                case 'cookies_cleared':
                                    echo '<i class="fas fa-check"></i> Cookies limpiadas exitosamente';
                                    break;
                            }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle"></i> Información del Sistema:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Fecha/Hora:</strong> <?= date('Y-m-d H:i:s') ?></li>
                                <li><strong>Servidor:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido' ?></li>
                                <li><strong>PHP Version:</strong> <?= phpversion() ?></li>
                                <li><strong>HostGator:</strong> <?= strpos($_SERVER['SERVER_NAME'] ?? '', 'hostgator') !== false ? 'SÍ' : 'NO' ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-network-wired"></i> Información de Red:</h6>
                            <ul class="list-unstyled">
                                <li><strong>IP Cliente:</strong> <?= $_SERVER['REMOTE_ADDR'] ?? 'Desconocida' ?></li>
                                <li><strong>Host:</strong> <?= $_SERVER['HTTP_HOST'] ?? 'Desconocido' ?></li>
                                <li><strong>HTTPS:</strong> <?= isset($_SERVER['HTTPS']) ? 'SÍ' : 'NO' ?></li>
                                <li><strong>Puerto:</strong> <?= $_SERVER['SERVER_PORT'] ?? 'Desconocido' ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TEST 1: CONFIGURACIÓN DE SESIONES -->
            <div class="card test-card">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-cog"></i> Test 1: Configuración de Sesiones PHP</h5>
                    <?php
                    $session_path = session_save_path();
                    $session_exists = is_dir($session_path);
                    $session_writable = is_writable($session_path);
                    $test1_passed = $session_exists && $session_writable && session_status() === PHP_SESSION_ACTIVE;
                    ?>
                    <span class="badge <?= $test1_passed ? 'bg-success' : 'bg-danger' ?> result-badge">
                        <?= $test1_passed ? 'PASÓ' : 'FALLÓ' ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Configuración Actual:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Session Path:</strong> <code><?= $session_path ?></code></li>
                                <li><strong>Session ID:</strong> <code><?= session_id() ?></code></li>
                                <li><strong>Session Status:</strong> 
                                    <?php
                                    $status = session_status();
                                    switch($status) {
                                        case PHP_SESSION_DISABLED:
                                            echo '<span class="status-error">DESHABILITADO</span>';
                                            break;
                                        case PHP_SESSION_NONE:
                                            echo '<span class="status-warning">NO INICIADO</span>';
                                            break;
                                        case PHP_SESSION_ACTIVE:
                                            echo '<span class="status-ok">ACTIVO ✓</span>';
                                            break;
                                    }
                                    ?>
                                </li>
                                <li><strong>Cookie Lifetime:</strong> <?= ini_get('session.cookie_lifetime') ?> segundos</li>
                                <li><strong>GC Maxlifetime:</strong> <?= ini_get('session.gc_maxlifetime') ?> segundos</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Estado del Directorio:</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <i class="fas fa-folder"></i> 
                                    <strong>Existe:</strong> 
                                    <?= $session_exists ? '<span class="status-ok">SÍ ✓</span>' : '<span class="status-error">NO ✗</span>' ?>
                                </li>
                                <li>
                                    <i class="fas fa-edit"></i> 
                                    <strong>Escribible:</strong> 
                                    <?= $session_writable ? '<span class="status-ok">SÍ ✓</span>' : '<span class="status-error">NO ✗</span>' ?>
                                </li>
                                <li>
                                    <i class="fas fa-shield-alt"></i> 
                                    <strong>Permisos:</strong> 
                                    <?= $session_exists ? substr(sprintf('%o', fileperms($session_path)), -4) : 'N/A' ?>
                                </li>
                                <li>
                                    <i class="fas fa-file"></i> 
                                    <strong>Archivos:</strong> 
                                    <?= $session_exists ? count(glob($session_path . '/sess_*')) : 'N/A' ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TEST 2: PRUEBA DE ESCRITURA/LECTURA -->
            <div class="card test-card">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-vial"></i> Test 2: Prueba de Escritura y Lectura</h5>
                    <?php
                    // Test de escritura de variables de sesión
                    $test2_passed = true;
                    $test_messages = [];
                    
                    // Crear variable de test si no existe
                    if (!isset($_SESSION['test_var'])) {
                        $_SESSION['test_var'] = 'CFM Joyas Test - ' . date('Y-m-d H:i:s');
                        $_SESSION['test_counter'] = 1;
                        $test_messages[] = '<span class="status-ok">✓ Variable de sesión CREADA</span>';
                    } else {
                        $_SESSION['test_counter'] = ($_SESSION['test_counter'] ?? 0) + 1;
                        $test_messages[] = '<span class="status-ok">✓ Variable de sesión LEÍDA (visita #' . $_SESSION['test_counter'] . ')</span>';
                    }
                    
                    // Test de escritura de archivos
                    $test_file = $session_path . '/cfm_test_write.txt';
                    if (file_put_contents($test_file, 'CFM Joyas Test - ' . time())) {
                        $test_messages[] = '<span class="status-ok">✓ Puede ESCRIBIR archivos en directorio de sesiones</span>';
                        if (file_exists($test_file)) {
                            $content = file_get_contents($test_file);
                            $test_messages[] = '<span class="status-ok">✓ Puede LEER archivos: ' . $content . '</span>';
                            unlink($test_file); // Limpiar
                        }
                    } else {
                        $test_messages[] = '<span class="status-error">✗ NO puede escribir archivos</span>';
                        $test2_passed = false;
                    }
                    ?>
                    <span class="badge <?= $test2_passed ? 'bg-success' : 'bg-danger' ?> result-badge">
                        <?= $test2_passed ? 'PASÓ' : 'FALLÓ' ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="alert <?= $test2_passed ? 'alert-success' : 'alert-danger' ?>">
                        <h6><i class="fas fa-clipboard-check"></i> Resultados del Test:</h6>
                        <ul class="mb-0">
                            <?php foreach ($test_messages as $message): ?>
                                <li><?= $message ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="code-block">
                        <strong>Contenido actual de $_SESSION:</strong><br>
                        <?php if (empty($_SESSION)): ?>
                            <span class="status-warning">Vacío - Esto podría ser el problema</span>
                        <?php else: ?>
                            <pre><?= print_r($_SESSION, true) ?></pre>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TEST 3: PRUEBA DE COOKIES -->
            <div class="card test-card">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="fas fa-cookie-bite"></i> Test 3: Sistema de Cookies (Alternativo)</h5>
                    <?php
                    // Test del sistema de cookies
                    $cookie_test_passed = true;
                    $cookie_messages = [];
                    
                    // Verificar si hay cookie de autenticación
                    if (isset($_COOKIE['cfm_auth'])) {
                        $auth_data = verifyAuthCookie();
                        if ($auth_data) {
                            $cookie_messages[] = '<span class="status-ok">✓ Cookie de autenticación VÁLIDA</span>';
                            $cookie_messages[] = '<span class="status-ok">Usuario: ' . htmlspecialchars($auth_data['user_name']) . '</span>';
                        } else {
                            $cookie_messages[] = '<span class="status-error">✗ Cookie de autenticación INVÁLIDA</span>';
                            $cookie_test_passed = false;
                        }
                    } else {
                        $cookie_messages[] = '<span class="status-warning">⚠ No hay cookie de autenticación</span>';
                    }
                    
                    // Test de creación de cookie de prueba
                    if (!isset($_COOKIE['cfm_test'])) {
                        setcookie('cfm_test', 'test_value_' . time(), time() + 300, '/');
                        $cookie_messages[] = '<span class="status-ok">✓ Cookie de test CREADA (recarga la página para verla)</span>';
                    } else {
                        $cookie_messages[] = '<span class="status-ok">✓ Cookie de test LEÍDA: ' . htmlspecialchars($_COOKIE['cfm_test']) . '</span>';
                    }
                    ?>
                    <span class="badge bg-info result-badge">
                        <?= count($cookie_messages) ?> pruebas
                    </span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Estado de Cookies:</h6>
                        <ul class="mb-0">
                            <?php foreach ($cookie_messages as $message): ?>
                                <li><?= $message ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <?php if (!empty($_COOKIE)): ?>
                    <div class="code-block">
                        <strong>Cookies disponibles:</strong><br>
                        <ul>
                            <?php foreach ($_COOKIE as $name => $value): ?>
                                <li>
                                    <strong><?= htmlspecialchars($name) ?>:</strong> 
                                    <?= htmlspecialchars(substr($value, 0, 50)) ?><?= strlen($value) > 50 ? '...' : '' ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TEST 4: CONEXIÓN A BASE DE DATOS -->
            <div class="card test-card">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-database"></i> Test 4: Conexión a Base de Datos</h5>
                    <?php
                    $db_test_passed = true;
                    $db_messages = [];
                    
                    try {
                        if ($conn && $conn->ping()) {
                            $db_messages[] = '<span class="status-ok">✓ Conexión a BD EXITOSA</span>';
                            
                            // Test de tabla usuarios
                            $result = $conn->query("SELECT COUNT(*) as total FROM usuarios");
                            if ($result) {
                                $row = $result->fetch_assoc();
                                $db_messages[] = '<span class="status-ok">✓ Tabla usuarios: ' . $row['total'] . ' registros</span>';
                            }
                            
                            // Test de tabla productos
                            $result = $conn->query("SELECT COUNT(*) as total FROM productos");
                            if ($result) {
                                $row = $result->fetch_assoc();
                                $db_messages[] = '<span class="status-ok">✓ Tabla productos: ' . $row['total'] . ' registros</span>';
                            }
                            
                            // Test de tabla categorias
                            $result = $conn->query("SELECT COUNT(*) as total FROM categorias");
                            if ($result) {
                                $row = $result->fetch_assoc();
                                $db_messages[] = '<span class="status-ok">✓ Tabla categorias: ' . $row['total'] . ' registros</span>';
                            }
                            
                        } else {
                            $db_messages[] = '<span class="status-error">✗ Error de conexión a BD</span>';
                            $db_test_passed = false;
                        }
                    } catch (Exception $e) {
                        $db_messages[] = '<span class="status-error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
                        $db_test_passed = false;
                    }
                    ?>
                    <span class="badge <?= $db_test_passed ? 'bg-success' : 'bg-danger' ?> result-badge">
                        <?= $db_test_passed ? 'PASÓ' : 'FALLÓ' ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="alert <?= $db_test_passed ? 'alert-success' : 'alert-danger' ?>">
                        <h6><i class="fas fa-server"></i> Estado de Base de Datos:</h6>
                        <ul class="mb-0">
                            <?php foreach ($db_messages as $message): ?>
                                <li><?= $message ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- TEST 5: SIMULACIÓN DE LOGIN -->
            <div class="card test-card">
                <div class="card-header bg-dark text-white">
                    <h5><i class="fas fa-user-lock"></i> Test 5: Simulación de Login</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_POST['test_login'])): ?>
                        <?php
                        // Simular proceso de login
                        $test_email = 'test@cfmjoyas.com';
                        $test_user_id = 999;
                        $test_user_name = 'Usuario Test';
                        
                        // Método 1: Sesión PHP
                        $_SESSION['test_user_id'] = $test_user_id;
                        $_SESSION['test_user_name'] = $test_user_name;
                        $_SESSION['test_login_time'] = time();
                        
                        // Método 2: Cookie
                        createAuthCookie($test_user_id, $test_user_name, $test_email);
                        
                        echo '<div class="alert alert-success">';
                        echo '<h6>✓ Simulación de login completada</h6>';
                        echo '<ul>';
                        echo '<li>Sesión PHP creada con user_id: ' . $test_user_id . '</li>';
                        echo '<li>Cookie de autenticación creada</li>';
                        echo '<li>Hora de login: ' . date('H:i:s') . '</li>';
                        echo '</ul>';
                        echo '</div>';
                        ?>
                    <?php endif; ?>
                    
                    <form method="POST" class="mb-3">
                        <button type="submit" name="test_login" class="btn btn-primary">
                            <i class="fas fa-play"></i> Ejecutar Simulación de Login
                        </button>
                    </form>
                    
                    <?php if (isset($_SESSION['test_user_id'])): ?>
                        <div class="alert alert-info">
                            <h6>Estado de Login de Test:</h6>
                            <ul class="mb-0">
                                <li><strong>User ID:</strong> <?= $_SESSION['test_user_id'] ?></li>
                                <li><strong>Nombre:</strong> <?= $_SESSION['test_user_name'] ?></li>
                                <li><strong>Tiempo de login:</strong> <?= date('H:i:s', $_SESSION['test_login_time']) ?></li>
                                <li><strong>Tiempo transcurrido:</strong> <?= time() - $_SESSION['test_login_time'] ?> segundos</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TEST 6: DIAGNÓSTICO DE HOSTGATOR -->
            <div class="card test-card">
                <div class="card-header bg-danger text-white">
                    <h5><i class="fas fa-server"></i> Test 6: Diagnóstico Específico de HostGator</h5>
                </div>
                <div class="card-body">
                    <?php
                    $hostgator_issues = [];
                    $is_hostgator = strpos($_SERVER['SERVER_NAME'] ?? '', 'hostgator') !== false || 
                                   strpos($_SERVER['HTTP_HOST'] ?? '', 'gator') !== false;
                    
                    // Verificar problemas conocidos de HostGator
                    if ($is_hostgator) {
                        $hostgator_issues[] = '<span class="status-warning">⚠ Servidor detectado como HostGator</span>';
                    }
                    
                    // Verificar directorio de sesiones por defecto
                    $default_session_path = '/var/cpanel/php/sessions/ea-php83';
                    if (session_save_path() === $default_session_path) {
                        $hostgator_issues[] = '<span class="status-error">✗ Usando directorio de sesiones por defecto (problemático)</span>';
                    } else {
                        $hostgator_issues[] = '<span class="status-ok">✓ Usando directorio de sesiones personalizado</span>';
                    }
                    
                    // Verificar permisos de tmp
                    if (!is_dir('/tmp') || !is_writable('/tmp')) {
                        $hostgator_issues[] = '<span class="status-warning">⚠ Directorio /tmp no disponible o sin permisos</span>';
                    } else {
                        $hostgator_issues[] = '<span class="status-ok">✓ Directorio /tmp disponible</span>';
                    }
                    
                    // Verificar PHP ini settings problemáticos
                    if (ini_get('session.auto_start') == '1') {
                        $hostgator_issues[] = '<span class="status-warning">⚠ session.auto_start está habilitado</span>';
                    }
                    
                    // Verificar open_basedir restriction
                    $open_basedir = ini_get('open_basedir');
                    if ($open_basedir) {
                        $hostgator_issues[] = '<span class="status-warning">⚠ open_basedir restriction activa: ' . $open_basedir . '</span>';
                    }
                    ?>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Diagnóstico HostGator:</h6>
                        <ul class="mb-0">
                            <?php foreach ($hostgator_issues as $issue): ?>
                                <li><?= $issue ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="code-block">
                        <strong>Variables de entorno relevantes:</strong><br>
                        <ul>
                            <li><strong>SERVER_SOFTWARE:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></li>
                            <li><strong>DOCUMENT_ROOT:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' ?></li>
                            <li><strong>SCRIPT_FILENAME:</strong> <?= $_SERVER['SCRIPT_FILENAME'] ?? 'N/A' ?></li>
                            <li><strong>TMP:</strong> <?= sys_get_temp_dir() ?></li>
                            <li><strong>Session Save Path:</strong> <?= session_save_path() ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ACCIONES Y ENLACES -->
            <div class="card test-card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-tools"></i> Acciones y Enlaces</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Navegación:</h6>
                            <div class="d-grid gap-2">
                                <a href="test_session.php" class="btn btn-outline-primary">
                                    <i class="fas fa-redo"></i> Recargar Test
                                </a>
                                <a href="login.php" class="btn btn-outline-success">
                                    <i class="fas fa-sign-in-alt"></i> Ir al Login
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-info">
                                    <i class="fas fa-tachometer-alt"></i> Ir al Dashboard
                                </a>
                                <a href="../index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home"></i> Sitio Principal
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6>Limpieza:</h6>
                            <div class="d-grid gap-2">
                                <a href="test_session.php?clear_session=1" class="btn btn-outline-warning">
                                    <i class="fas fa-trash"></i> Limpiar Sesión
                                </a>
                                <a href="test_session.php?clear_cookies=1" class="btn btn-outline-warning">
                                    <i class="fas fa-cookie"></i> Limpiar Cookies
                                </a>
                                <a href="logout.php" class="btn btn-outline-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout Completo
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6>Debug:</h6>
                            <div class="d-grid gap-2">
                                <a href="test_session.php?auto=1" class="btn btn-outline-info">
                                    <i class="fas fa-sync"></i> Auto-refresh (30s)
                                </a>
                                <button onclick="window.location.reload()" class="btn btn-outline-secondary">
                                    <i class="fas fa-refresh"></i> Recargar Página
                                </button>
                                <button onclick="copyDebugInfo()" class="btn btn-outline-dark">
                                    <i class="fas fa-copy"></i> Copiar Info Debug
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RESUMEN FINAL -->
            <div class="card test-card">
                <div class="card-header <?= ($test1_passed && $test2_passed && $db_test_passed) ? 'bg-success' : 'bg-danger' ?> text-white">
                    <h5><i class="fas fa-clipboard-check"></i> Resumen Final</h5>
                </div>
                <div class="card-body">
                    <?php
                    $tests_passed = 0;
                    $total_tests = 4;
                    
                    if ($test1_passed) $tests_passed++;
                    if ($test2_passed) $tests_passed++;
                    if ($db_test_passed) $tests_passed++;
                    if ($cookie_test_passed) $tests_passed++;
                    
                    $percentage = round(($tests_passed / $total_tests) * 100);
                    ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Estado General: 
                                <?php if ($percentage >= 75): ?>
                                    <span class="text-success">BUENO</span>
                                <?php elseif ($percentage >= 50): ?>
                                    <span class="text-warning">REGULAR</span>
                                <?php else: ?>
                                    <span class="text-danger">CRÍTICO</span>
                                <?php endif; ?>
                            </h4>
                            <div class="progress mb-3">
                                <div class="progress-bar <?= $percentage >= 75 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                     role="progressbar" style="width: <?= $percentage ?>%">
                                    <?= $tests_passed ?>/<?= $total_tests ?> tests (<?= $percentage ?>%)
                                </div>
                            </div>
                            <ul class="list-unstyled">
                                <li><?= $test1_passed ? '✅' : '❌' ?> Configuración de Sesiones</li>
                                <li><?= $test2_passed ? '✅' : '❌' ?> Escritura/Lectura</li>
                                <li><?= $db_test_passed ? '✅' : '❌' ?> Base de Datos</li>
                                <li><?= $cookie_test_passed ? '✅' : '❌' ?> Sistema de Cookies</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Recomendaciones:</h6>
                            <?php if ($percentage >= 75): ?>
                                <div class="alert alert-success">
                                    <strong>¡Excelente!</strong> El sistema parece estar funcionando correctamente. 
                                    Puedes proceder a probar el login normal.
                                </div>
                            <?php elseif ($percentage >= 50): ?>
                                <div class="alert alert-warning">
                                    <strong>Atención:</strong> Hay algunos problemas menores. 
                                    El sistema podría funcionar pero con limitaciones.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <strong>Problema crítico:</strong> Hay errores importantes que deben solucionarse 
                                    antes de que el login funcione correctamente.
                                </div>
                            <?php endif; ?>
                            
                            <h6>Próximos pasos:</h6>
                            <ol>
                                <?php if (!$test1_passed): ?>
                                    <li>Corregir configuración de sesiones</li>
                                <?php endif; ?>
                                <?php if (!$test2_passed): ?>
                                    <li>Verificar permisos de directorio</li>
                                <?php endif; ?>
                                <?php if (!$db_test_passed): ?>
                                    <li>Revisar conexión a base de datos</li>
                                <?php endif; ?>
                                <?php if ($percentage >= 50): ?>
                                    <li>Probar login en <code>/admin/login.php</code></li>
                                    <li>Si falla, usar sistema de cookies alternativo</li>
                                <?php endif; ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-dark text-white text-center py-3 mt-5">
    <div class="container">
        <small>
            <i class="fas fa-gem text-warning"></i> CFM Joyas - Test de Sesiones
            | Generado: <?= date('d/m/Y H:i:s') ?>
            | Tests: <?= $tests_passed ?>/<?= $total_tests ?>
        </small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh cada 30 segundos si está en modo auto
    if (window.location.search.includes('auto=1')) {
        console.log('Auto-refresh activado');
        setTimeout(() => {
            window.location.reload();
        }, 30000);
        
        // Mostrar countdown
        let countdown = 30;
        const interval = setInterval(() => {
            countdown--;
            document.title = `CFM Test (${countdown}s) - CFM Joyas`;
            if (countdown <= 0) {
                clearInterval(interval);
            }
        }, 1000);
    }
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    console.log('CFM Joyas Session Test loaded');
    console.log('Session ID:', '<?= session_id() ?>');
    console.log('Session Status:', '<?= session_status() ?>');
    console.log('Tests passed:', '<?= $tests_passed ?>/<?= $total_tests ?>');
});

// Función para copiar información de debug
function copyDebugInfo() {
    const debugInfo = `
CFM JOYAS - DEBUG INFO
======================
Fecha: <?= date('Y-m-d H:i:s') ?>
PHP Version: <?= phpversion() ?>
Server: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?>
Host: <?= $_SERVER['HTTP_HOST'] ?? 'Unknown' ?>

SESSION INFO:
- Session ID: <?= session_id() ?>
- Session Status: <?= session_status() ?>
- Session Path: <?= session_save_path() ?>
- Path Exists: <?= is_dir(session_save_path()) ? 'YES' : 'NO' ?>
- Path Writable: <?= is_writable(session_save_path()) ? 'YES' : 'NO' ?>

TESTS RESULTS:
- Session Config: <?= $test1_passed ? 'PASS' : 'FAIL' ?>
- Read/Write: <?= $test2_passed ? 'PASS' : 'FAIL' ?>
- Database: <?= $db_test_passed ? 'PASS' : 'FAIL' ?>
- Cookies: <?= $cookie_test_passed ? 'PASS' : 'FAIL' ?>

Overall: <?= $tests_passed ?>/<?= $total_tests ?> (<?= $percentage ?>%)
    `.trim();
    
    navigator.clipboard.writeText(debugInfo).then(function() {
        alert('Información de debug copiada al portapapeles');
    }, function(err) {
        console.error('Error al copiar: ', err);
        // Fallback para navegadores que no soportan clipboard API
        const textArea = document.createElement('textarea');
        textArea.value = debugInfo;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Información copiada al portapapeles');
    });
}

// Función para mostrar/ocultar detalles técnicos
function toggleDetails(elementId) {
    const element = document.getElementById(elementId);
    if (element.style.display === 'none') {
        element.style.display = 'block';
    } else {
        element.style.display = 'none';
    }
}

// Efectos visuales para las cards
const cards = document.querySelectorAll('.test-card');
cards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        card.style.transition = 'all 0.5s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, index * 150);
});

// Highlight de resultados
const statusElements = document.querySelectorAll('.status-ok, .status-error, .status-warning');
statusElements.forEach(element => {
    element.addEventListener('mouseover', function() {
        this.style.textShadow = '0 0 10px currentColor';
    });
    
    element.addEventListener('mouseout', function() {
        this.style.textShadow = 'none';
    });
});
</script>

</body>
</html>