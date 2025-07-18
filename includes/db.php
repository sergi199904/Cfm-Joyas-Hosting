<?php
// =======================================================
// CONFIGURACIÓN DE BASE DE DATOS PARA HOSTGATOR - CFM JOYAS
// includes/db.php - VERSIÓN CORREGIDA COMPLETA
// =======================================================

// *** SOLUCIÓN PARA SESIONES EN HOSTGATOR ***
$session_dir = dirname(__DIR__) . '/tmp/sessions';
if (!is_dir($session_dir)) {
    mkdir($session_dir, 0755, true);
}

// Configurar PHP para usar nuestro directorio de sesiones
ini_set('session.save_path', $session_dir);
ini_set('session.gc_maxlifetime', 3600); // 1 hora
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Configuración de errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL & ~E_NOTICE);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_log');

// CONFIGURACIÓN PARA HOSTGATOR - DOMINIO: cfmjoyas.cl
$host = 'localhost';
$username = 'cfmjoyas_cfmuser';
$password = '4-gt?YU1;1xS';
$database = 'cfmjoyas_cfmjoyas';

// Crear conexión con manejo de errores mejorado
try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset("utf8");
    
    // Configurar opciones de conexión
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    
    // Log de conexión exitosa (solo para debug)
    if ($conn->ping()) {
        error_log("CFM Joyas: BD conectada exitosamente en " . date('Y-m-d H:i:s'));
    }
    
} catch (mysqli_sql_exception $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error de conexión a la base de datos. Intente más tarde.");
}

// FUNCIONES DE UTILIDAD - MANTENER IGUAL
function limpiar_input($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim(htmlspecialchars($data)));
}

function validar_codigo_acceso($codigo) {
    $codigos_validos = ['CFM2025', 'JOYAS2025', 'ADMIN2025'];
    return in_array($codigo, $codigos_validos);
}

function verificar_intentos_login($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT intentos_fallidos, bloqueado_hasta FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Si está bloqueado y aún no ha pasado el tiempo
        if ($row['bloqueado_hasta'] && strtotime($row['bloqueado_hasta']) > time()) {
            return false; // Bloqueado
        }
        
        // Si tiene muchos intentos fallidos
        if ($row['intentos_fallidos'] >= 3) {
            return false; // Bloqueado por intentos
        }
    }
    
    return true; // Puede intentar
}

function registrar_intento_fallido($email) {
    global $conn;
    $stmt = $conn->prepare("UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
}

function limpiar_intentos($email) {
    global $conn;
    $stmt = $conn->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
}

// =======================================================
// FUNCIONES DE AUTENTICACIÓN CON COOKIES (ALTERNATIVA)
// =======================================================
function createAuthCookie($user_id, $user_name, $user_email) {
    $secret_key = 'CFM_JOYAS_SECRET_2025_' . $user_id; // Clave única por usuario
    $expire_time = time() + 3600; // 1 hora
    
    $data = json_encode([
        'user_id' => $user_id,
        'user_name' => $user_name,
        'user_email' => $user_email,
        'expire' => $expire_time,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $signature = hash_hmac('sha256', $data, $secret_key);
    $cookie_value = base64_encode($data . '|' . $signature);
    
    // Crear cookie segura
    setcookie('cfm_auth', $cookie_value, $expire_time, '/', '', isset($_SERVER['HTTPS']), true);
    
    error_log("CFM Auth: Cookie creada para usuario $user_id");
    return true;
}

function verifyAuthCookie() {
    if (!isset($_COOKIE['cfm_auth'])) {
        return false;
    }
    
    try {
        $cookie_value = base64_decode($_COOKIE['cfm_auth']);
        
        if (strpos($cookie_value, '|') === false) {
            return false;
        }
        
        list($data, $signature) = explode('|', $cookie_value, 2);
        $auth_data = json_decode($data, true);
        
        if (!$auth_data || !isset($auth_data['user_id'])) {
            return false;
        }
        
        $secret_key = 'CFM_JOYAS_SECRET_2025_' . $auth_data['user_id'];
        
        // Verificar firma
        if (!hash_equals(hash_hmac('sha256', $data, $secret_key), $signature)) {
            error_log("CFM Auth: Firma inválida para usuario " . $auth_data['user_id']);
            return false;
        }
        
        // Verificar expiración
        if ($auth_data['expire'] < time()) {
            error_log("CFM Auth: Cookie expirada para usuario " . $auth_data['user_id']);
            clearAuthCookie();
            return false;
        }
        
        // Verificar IP (opcional, comentar si causa problemas)
        /*
        if ($auth_data['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            error_log("CFM Auth: IP diferente para usuario " . $auth_data['user_id']);
            clearAuthCookie();
            return false;
        }
        */
        
        return $auth_data;
        
    } catch (Exception $e) {
        error_log("CFM Auth Error: " . $e->getMessage());
        return false;
    }
}

function clearAuthCookie() {
    setcookie('cfm_auth', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// =======================================================
// FUNCIÓN PARA INICIAR SESIÓN SEGURA
// =======================================================
function iniciar_sesion_segura() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurar directorio de sesiones si no se hizo antes
        $session_dir = dirname(__DIR__) . '/tmp/sessions';
        if (!is_dir($session_dir)) {
            mkdir($session_dir, 0755, true);
        }
        
        ini_set('session.save_path', $session_dir);
        session_start();
    }
}

// =======================================================
// TEST DE CONEXIÓN (comentar en producción)
// =======================================================
/*
if ($conn->ping()) {
    error_log("CFM Joyas DB Test: Conexión OK - " . date('Y-m-d H:i:s'));
} else {
    error_log("CFM Joyas DB Test: Conexión FALLÓ - " . date('Y-m-d H:i:s'));
}
*/
?>