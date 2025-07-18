<?php
// =======================================================
// SUBIR PRODUCTO - CFM JOYAS - VERSIÓN HOSTGATOR ESPECÍFICA
// admin/subir_producto.php
// =======================================================

// CONFIGURAR SESIONES SEGURAS PARA HOSTGATOR
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

// CONFIGURACIÓN DIRECTA DE BD PARA HOSTGATOR - SIN INCLUDES
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// CREDENCIALES ESPECÍFICAS DE HOSTGATOR
$host = 'localhost';
$username = 'cfmjoyas_cfmuser';  // ← CONFIRMAR ESTE
$password = '4-gt?YU1;1xS';      // ← CONFIRMAR ESTE  
$database = 'cfmjoyas_cfmjoyas'; // ← CONFIRMAR ESTE

// Log de intento de conexión
error_log("CFM Upload: Intentando conectar a BD - Host: $host, User: $username, DB: $database");

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset("utf8");
    
    if ($conn->ping()) {
        error_log("CFM Upload: Conexión BD exitosa");
    } else {
        error_log("CFM Upload: Conexión BD falló - ping failed");
    }
} catch (mysqli_sql_exception $e) {
    error_log("CFM Upload: Error conexión BD: " . $e->getMessage());
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// FUNCIONES NECESARIAS (copiar de includes/db.php)
function limpiar_input($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim(htmlspecialchars($data)));
}

// VERIFICAR AUTENTICACIÓN SIMPLE
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log("CFM Upload: Usuario no autenticado");
    $_SESSION['error'] = 'Debe iniciar sesión para agregar productos.';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
error_log("CFM Upload: Usuario autenticado - ID: $user_id");

// PROCESAR SOLO SI ES POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método no permitido.';
    header('Location: dashboard.php');
    exit;
}

// LOG DE TODOS LOS DATOS RECIBIDOS
error_log("CFM Upload: POST data: " . print_r($_POST, true));
error_log("CFM Upload: FILES data: " . print_r($_FILES, true));

// OBTENER Y VALIDAR DATOS
$nombre = isset($_POST['nombre']) ? limpiar_input($_POST['nombre']) : '';
$precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
$categoria = isset($_POST['categoria']) ? limpiar_input($_POST['categoria']) : '';
$instagram = isset($_POST['instagram']) ? limpiar_input($_POST['instagram']) : '';

error_log("CFM Upload: Datos procesados - Nombre: '$nombre', Precio: $precio, Categoria: '$categoria', Instagram: '$instagram'");

// VALIDACIONES
$errores = [];

if (empty($nombre) || strlen($nombre) < 3) {
    $errores[] = 'Nombre inválido';
    error_log("CFM Upload: Error - Nombre inválido: '$nombre'");
}

if ($precio <= 0) {
    $errores[] = 'Precio inválido';
    error_log("CFM Upload: Error - Precio inválido: $precio");
}

if (empty($categoria)) {
    $errores[] = 'Categoría vacía';
    error_log("CFM Upload: Error - Categoría vacía");
}

if (empty($instagram) || !filter_var($instagram, FILTER_VALIDATE_URL)) {
    $errores[] = 'Instagram inválido';
    error_log("CFM Upload: Error - Instagram inválido: '$instagram'");
}

// VALIDAR IMAGEN
if (!isset($_FILES['imagen'])) {
    $errores[] = 'No se recibió archivo';
    error_log("CFM Upload: Error - No se recibió archivo de imagen");
} elseif ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    $errores[] = 'Error en upload: ' . $_FILES['imagen']['error'];
    error_log("CFM Upload: Error - Upload error: " . $_FILES['imagen']['error']);
} else {
    $imagen = $_FILES['imagen'];
    error_log("CFM Upload: Imagen recibida - Nombre: " . $imagen['name'] . ", Tamaño: " . $imagen['size'] . ", Tipo: " . $imagen['type']);
    
    // Validar tipo
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($imagen['type'], $allowed_types)) {
        $errores[] = 'Tipo de imagen no válido: ' . $imagen['type'];
        error_log("CFM Upload: Error - Tipo inválido: " . $imagen['type']);
    }
    
    // Validar tamaño (5MB)
    if ($imagen['size'] > 5 * 1024 * 1024) {
        $errores[] = 'Imagen muy grande: ' . round($imagen['size'] / 1024 / 1024, 2) . 'MB';
        error_log("CFM Upload: Error - Imagen muy grande: " . $imagen['size'] . " bytes");
    }
}

// Si hay errores, mostrar y salir
if (!empty($errores)) {
    $error_msg = 'Errores: ' . implode(', ', $errores);
    $_SESSION['error'] = $error_msg;
    error_log("CFM Upload: Errores encontrados: $error_msg");
    header('Location: dashboard.php');
    exit;
}

// VERIFICAR CATEGORÍA EN BD
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM categorias WHERE nombre = ? AND activa = 1");
    $stmt->bind_param('s', $categoria);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    error_log("CFM Upload: Verificando categoría '$categoria' - Encontradas: " . $row['count']);
    
    if ($row['count'] == 0) {
        $_SESSION['error'] = "Categoría '$categoria' no válida";
        error_log("CFM Upload: Error - Categoría no encontrada: '$categoria'");
        header('Location: dashboard.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error verificando categoría: ' . $e->getMessage();
    error_log("CFM Upload: Error BD categoría: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}

// PROCESAR IMAGEN
$imagen = $_FILES['imagen'];
$extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
$nombre_archivo = 'producto_' . time() . '_' . uniqid() . '.' . $extension;

// RUTAS ESPECÍFICAS PARA HOSTGATOR
$directorio_relativo = "../img/productos/";
$directorio_absoluto = $_SERVER['DOCUMENT_ROOT'] . "/img/productos/";
$ruta_completa = $directorio_relativo . $nombre_archivo;
$ruta_bd = "img/productos/" . $nombre_archivo;

error_log("CFM Upload: Rutas - Relativa: $directorio_relativo, Absoluta: $directorio_absoluto");
error_log("CFM Upload: Archivo: $nombre_archivo, Ruta completa: $ruta_completa");

// CREAR DIRECTORIO SI NO EXISTE
if (!is_dir($directorio_relativo)) {
    error_log("CFM Upload: Creando directorio: $directorio_relativo");
    if (!mkdir($directorio_relativo, 0755, true)) {
        $_SESSION['error'] = 'No se pudo crear directorio de imágenes';
        error_log("CFM Upload: Error - No se pudo crear directorio");
        header('Location: dashboard.php');
        exit;
    }
}

// VERIFICAR PERMISOS
if (!is_writable($directorio_relativo)) {
    $_SESSION['error'] = 'Directorio sin permisos de escritura';
    error_log("CFM Upload: Error - Sin permisos de escritura en: $directorio_relativo");
    header('Location: dashboard.php');
    exit;
}

// MOVER ARCHIVO
error_log("CFM Upload: Moviendo archivo de " . $imagen['tmp_name'] . " a $ruta_completa");

if (!move_uploaded_file($imagen['tmp_name'], $ruta_completa)) {
    $_SESSION['error'] = 'Error guardando imagen en servidor';
    error_log("CFM Upload: Error - No se pudo mover archivo");
    header('Location: dashboard.php');
    exit;
}

error_log("CFM Upload: Imagen guardada exitosamente en: $ruta_completa");

// VERIFICAR QUE EL ARCHIVO SE GUARDÓ
if (!file_exists($ruta_completa)) {
    $_SESSION['error'] = 'Archivo no se guardó correctamente';
    error_log("CFM Upload: Error - Archivo no existe después de mover");
    header('Location: dashboard.php');
    exit;
}

$file_size = filesize($ruta_completa);
error_log("CFM Upload: Archivo verificado - Tamaño: $file_size bytes");

// INSERTAR EN BASE DE DATOS
try {
    error_log("CFM Upload: Insertando en BD - Nombre: '$nombre', Precio: $precio, Categoria: '$categoria', Instagram: '$instagram', Imagen: '$ruta_bd'");
    
    $stmt = $conn->prepare("INSERT INTO productos (nombre, precio, categoria, instagram, imagen, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('sdsss', $nombre, $precio, $categoria, $instagram, $ruta_bd);
    
    if ($stmt->execute()) {
        $producto_id = $conn->insert_id;
        $_SESSION['success'] = "¡Producto '$nombre' agregado exitosamente! (ID: $producto_id)";
        error_log("CFM Upload: ¡ÉXITO! Producto creado - ID: $producto_id");
    } else {
        // Si falla la BD, eliminar imagen
        if (file_exists($ruta_completa)) {
            unlink($ruta_completa);
            error_log("CFM Upload: Imagen eliminada por error en BD");
        }
        $_SESSION['error'] = 'Error insertando en BD: ' . $stmt->error;
        error_log("CFM Upload: Error BD insert: " . $stmt->error);
    }
    
} catch (Exception $e) {
    // Si hay excepción, eliminar imagen
    if (file_exists($ruta_completa)) {
        unlink($ruta_completa);
        error_log("CFM Upload: Imagen eliminada por excepción");
    }
    $_SESSION['error'] = 'Excepción BD: ' . $e->getMessage();
    error_log("CFM Upload: Excepción BD: " . $e->getMessage());
}

// CERRAR CONEXIÓN
$conn->close();

// REDIRIGIR
error_log("CFM Upload: Redirigiendo a dashboard.php");
header('Location: dashboard.php');
exit;
?>