<?php
// =======================================================
// LOGOUT COMPLETO - CFM JOYAS
// admin/logout.php - VERSIÓN CORREGIDA
// =======================================================

// CONFIGURAR SESIONES ANTES QUE NADA
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

// Incluir funciones de base de datos para las cookies
require_once '../includes/db.php';

// Log del logout para debugging
$user_info = '';
if (isset($_SESSION['user_name'])) {
    $user_info = $_SESSION['user_name'] . ' (ID: ' . ($_SESSION['user_id'] ?? 'unknown') . ')';
    error_log("CFM Logout: Usuario $user_info cerrando sesión");
}

// PASO 1: LIMPIAR SESIÓN PHP COMPLETAMENTE
if (session_status() === PHP_SESSION_ACTIVE) {
    // Unset todas las variables de sesión
    $_SESSION = array();
    
    // Eliminar cookie de sesión PHP si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}

// PASO 2: LIMPIAR TODAS LAS COOKIES DE AUTENTICACIÓN
// Cookie principal de autenticación
if (isset($_COOKIE['cfm_auth'])) {
    setcookie('cfm_auth', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    unset($_COOKIE['cfm_auth']);
}

// Cookie de test (si existe)
if (isset($_COOKIE['cfm_test'])) {
    setcookie('cfm_test', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    unset($_COOKIE['cfm_test']);
}

// PASO 3: LIMPIAR OTRAS POSIBLES COOKIES DE CFM JOYAS
$cookie_names = ['cfm_remember', 'cfm_session_backup', 'cfm_admin', 'cfmjoyas_auth'];
foreach ($cookie_names as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        setcookie($cookie_name, '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        unset($_COOKIE[$cookie_name]);
    }
}

// PASO 4: HEADERS ADICIONALES PARA EVITAR CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Fecha en el pasado

// PASO 5: LOG DE CONFIRMACIÓN
error_log("CFM Logout: Logout completo para $user_info - " . date('Y-m-d H:i:s'));

// PASO 6: REDIRECCIÓN CON MENSAJE DE CONFIRMACIÓN
header('Location: login.php?logout=success');
exit;
?>