<?php
// =======================================================
// ADMIN DASHBOARD - CFM JOYAS - VERSIÓN FINAL CORREGIDA
// admin/dashboard.php
// =======================================================

// CONFIGURAR SESIONES SEGURAS
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

// CONFIGURACIÓN DE BASE DE DATOS - USAR INCLUDES CENTRALIZADO
// IMPORTANTE: Las credenciales están ahora en variables de entorno, no en el código
require_once __DIR__ . '/../includes/db.php';

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
            return false;
        }
        
        // Verificar expiración
        if ($auth_data['expire'] < time()) {
            return false;
        }
        
        return $auth_data;
        
    } catch (Exception $e) {
        return false;
    }
}

// VERIFICAR AUTENTICACIÓN - MÉTODO DUAL (SESIÓN + COOKIES)
$user_authenticated = false;
$user_id = null;
$user_name = 'Admin';
$user_email = '';

// MÉTODO 1: Verificar sesión PHP
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Verificar que la sesión no haya expirado
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) < 3600) {
        $user_authenticated = true;
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_name'] ?? 'Admin';
        $user_email = $_SESSION['user_email'] ?? '';
        
        error_log("CFM Dashboard: Autenticado por SESIÓN - user_id: $user_id");
    } else {
        // Sesión expirada
        error_log("CFM Dashboard: Sesión expirada para user_id: " . $_SESSION['user_id']);
        session_destroy();
    }
}

// MÉTODO 2: Si no hay sesión válida, verificar cookie
if (!$user_authenticated) {
    $auth_data = verifyAuthCookie();
    if ($auth_data) {
        $user_authenticated = true;
        $user_id = $auth_data['user_id'];
        $user_name = $auth_data['user_name'];
        $user_email = $auth_data['user_email'];
        
        // Recrear sesión desde cookie válida
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user_name;
        $_SESSION['user_email'] = $user_email;
        $_SESSION['login_time'] = time();
        
        error_log("CFM Dashboard: Autenticado por COOKIE - user_id: $user_id");
    }
}

// Si no está autenticado por ningún método, redirigir al login
if (!$user_authenticated || !$user_id) {
    error_log("CFM Dashboard: NO AUTENTICADO - Redirigiendo a login");
    header('Location: login.php');
    exit;
}

// PROCESAR ELIMINACIÓN DE PRODUCTOS
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    try {
        error_log("CFM Delete: Intentando eliminar producto ID: $id");
        
        // Obtener la imagen antes de eliminar
        $stmt = $conn->prepare("SELECT imagen, nombre FROM productos WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $imagen_path = "../" . $row['imagen'];
            $nombre_producto = $row['nombre'];
            
            error_log("CFM Delete: Producto encontrado - Nombre: $nombre_producto, Imagen: $imagen_path");
            
            // Eliminar producto de la base de datos
            $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                // Eliminar imagen del servidor si existe
                if (file_exists($imagen_path)) {
                    unlink($imagen_path);
                    error_log("CFM Delete: Imagen eliminada: $imagen_path");
                }
                $_SESSION['success'] = "Producto '$nombre_producto' eliminado exitosamente.";
                error_log("CFM Delete: Producto eliminado exitosamente - ID: $id");
            } else {
                $_SESSION['error'] = 'Error al eliminar el producto: ' . $stmt->error;
                error_log("CFM Delete: Error BD: " . $stmt->error);
            }
        } else {
            $_SESSION['error'] = 'Producto no encontrado.';
            error_log("CFM Delete: Producto no encontrado - ID: $id");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error del sistema: ' . $e->getMessage();
        error_log("CFM Delete: Excepción: " . $e->getMessage());
    }
    
    header('Location: dashboard.php');
    exit;
}

// OBTENER PRODUCTOS CON FILTROS
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$search = isset($_GET['search']) ? limpiar_input($_GET['search']) : '';

$query = "SELECT p.* FROM productos p WHERE 1=1";
$params = [];
$types = '';

if ($categoria_filtro && $categoria_filtro !== 'todas') {
    $query .= " AND p.categoria = ?";
    $params[] = $categoria_filtro;
    $types .= 's';
}

if ($search) {
    $query .= " AND (p.nombre LIKE ? OR p.categoria LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$query .= " ORDER BY p.fecha DESC";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// OBTENER CATEGORÍAS
$categories = $conn->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre");

// ESTADÍSTICAS
$stats_query = "SELECT 
    COUNT(*) as total_productos,
    COUNT(CASE WHEN categoria = 'joyas' THEN 1 END) as total_joyas,
    COUNT(CASE WHEN categoria = 'ceramicas' THEN 1 END) as total_ceramicas,
    COUNT(CASE WHEN categoria = 'otros' THEN 1 END) as total_otros,
    AVG(precio) as precio_promedio,
    MAX(fecha) as ultimo_producto
    FROM productos";

try {
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    error_log("CFM Dashboard: Productos cargados - Total: " . $products->num_rows);
} catch (Exception $e) {
    $stats = [
        'total_productos' => 0,
        'total_joyas' => 0,
        'total_ceramicas' => 0,
        'total_otros' => 0,
        'precio_promedio' => 0,
        'ultimo_producto' => null
    ];
    error_log("CFM Dashboard: Error BD stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - CFM Joyas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Essential custom styles that can't be replaced with Bootstrap */
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .stats-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .product-img { 
            width: 60px; 
            height: 60px; 
            object-fit: cover; 
        }
        
        .price-badge { 
            background: linear-gradient(45deg, #28a745, #20c997); 
            color: white;
            font-weight: 600;
        }
        
        .navbar {
            background: linear-gradient(135deg, #212529 0%, #000 100%) !important;
        }
        
        .admin-info {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
        
        .btn-outline-primary:hover .fa-edit {
            transform: rotate(15deg);
        }
        
        .btn-outline-danger:hover .fa-trash {
            transform: scale(1.2);
        }

        /* Mobile responsive improvements */
        @media (max-width: 767px) {
            .navbar-collapse {
                background: rgba(0,0,0,0.95);
                border-radius: 10px;
                margin-top: 10px;
                padding: 15px;
            }
            
            /* Mobile admin panel styling */
            .mobile-admin-panel {
                order: -1; /* Show at top of collapsed menu */
                width: 100%;
            }
            
            .admin-info-mobile {
                margin-bottom: 1rem;
            }
            
            /* Fix admin dropdown positioning on mobile */
            .dropdown-menu-end {
                right: 0 !important;
                left: auto !important;
                min-width: 200px;
                max-width: calc(100vw - 20px);
                transform: translateX(0) !important;
            }
            
            /* Ensure dropdown doesn't go off screen */
            .dropdown {
                position: static;
            }
            
            .dropdown-menu {
                position: absolute;
                right: 10px;
                left: auto;
                margin-top: 5px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
            }
            
            .product-img {
                width: 40px !important;
                height: 40px !important;
            }
            
            .btn-group {
                display: flex !important;
                flex-direction: column !important;
                gap: 2px !important;
            }
            
            .btn-group .btn {
                border-radius: 4px !important;
                margin: 0 !important;
                font-size: 0.75rem !important;
                padding: 4px 8px !important;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR MEJORADA -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <i class="fas fa-gem me-2 text-warning"></i> 
            <span class="fw-bold">CFM Joyas Admin</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="test_session.php" target="_blank">
                        <i class="fas fa-bug"></i> Test Sesiones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Ver Sitio
                    </a>
                </li>
            </ul>
            
            <!-- Mobile admin panel - visible on mobile only -->
            <div class="d-md-none mobile-admin-panel">
                <div class="admin-info-mobile text-white mb-3">
                    <div class="text-center p-3" style="background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 10px; margin: 10px 0;">
                        <i class="fas fa-user-shield fs-4"></i> 
                        <div class="fw-bold"><?= htmlspecialchars($user_name) ?></div>
                        <small class="opacity-75"><?= htmlspecialchars($user_email) ?></small>
                        <hr class="my-2 border-light">
                        <a href="#" onclick="logoutUser(); return false;" class="btn btn-outline-light btn-sm w-100">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Admin info - visible on desktop only -->
                <div class="admin-info text-white d-none d-md-block">
                    <i class="fas fa-user-shield"></i> 
                    <span class="fw-bold"><?= htmlspecialchars($user_name) ?></span>
                    <small class="d-block opacity-75"><?= htmlspecialchars($user_email) ?></small>
                </div>
                
                <!-- Desktop dropdown - visible on desktop only -->
                <div class="dropdown d-none d-md-block">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" 
                            id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i>
                        <span class="ms-1">Admin</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="test_session.php">
                            <i class="fas fa-bug"></i> Test Sesiones
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="logoutUser(); return false;">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">
    <!-- ALERTAS DE SISTEMA -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 rounded-3" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- ESTADÍSTICAS -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stats-card bg-primary text-white border-0 rounded-4 shadow-sm" style="transition: transform 0.2s ease;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1 fw-semibold">Total Productos</h6>
                            <h3 class="mb-0 fw-bold"><?= $stats['total_productos'] ?></h3>
                        </div>
                        <i class="fas fa-boxes fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stats-card bg-success text-white border-0 rounded-4 shadow-sm" style="transition: transform 0.2s ease;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1 fw-semibold">Joyas</h6>
                            <h3 class="mb-0 fw-bold"><?= $stats['total_joyas'] ?></h3>
                        </div>
                        <i class="fas fa-gem fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stats-card bg-warning text-dark border-0 rounded-4 shadow-sm" style="transition: transform 0.2s ease;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1 fw-semibold">Cerámicas</h6>
                            <h3 class="mb-0 fw-bold"><?= $stats['total_ceramicas'] ?></h3>
                        </div>
                        <i class="fas fa-palette fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stats-card bg-info text-white border-0 rounded-4 shadow-sm" style="transition: transform 0.2s ease;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1 fw-semibold">Precio Promedio</h6>
                            <h3 class="mb-0 fw-bold">$<?= number_format($stats['precio_promedio'], 0, ',', '.') ?></h3>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- FORMULARIO PARA AGREGAR PRODUCTOS -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-lg">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo Producto
                    </h5>
                </div>
                <div class="card-body">
                    <form action="subir_producto.php" method="POST" enctype="multipart/form-data" id="addProductForm">
                        
                        <div class="form-floating mb-3">
                            <input name="nombre" id="nombre" class="form-control" 
                                   placeholder="Nombre del producto" required>
                            <label for="nombre">
                                <i class="fas fa-tag"></i> Nombre del Producto
                            </label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input name="precio" id="precio" type="number" class="form-control" 
                                   min="0" step="1" placeholder="15000" required>
                            <label for="precio">
                                <i class="fas fa-money-bill"></i> Precio (CLP)
                            </label>
                            <small class="text-muted">Precio en pesos chilenos</small>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <select name="categoria" id="categoria" class="form-select" required>
                                <?php 
                                $categories->data_seek(0); // Reset pointer
                                while ($cat = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?= $cat['nombre'] ?>">
                                        <?= ucfirst($cat['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <label for="categoria">
                                <i class="fas fa-list"></i> Categoría
                            </label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input name="instagram" id="instagram" type="url" class="form-control" 
                                   placeholder="https://instagram.com/p/..." required>
                            <label for="instagram">
                                <i class="fab fa-instagram"></i> Enlace de Instagram
                            </label>
                            <small class="text-muted">URL completa del post</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="imagen" class="form-label fw-semibold">
                                <i class="fas fa-image"></i> Imagen del Producto
                            </label>
                            <input name="imagen" id="imagen" type="file" class="form-control" 
                                   accept="image/*" required>
                            <small class="text-muted">JPG, PNG, GIF o WebP. Máximo 5MB</small>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 fw-semibold">
                            <i class="fas fa-plus"></i> Agregar Producto
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTA DE PRODUCTOS -->
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-inventory"></i> Productos Existentes
                            <span class="badge bg-light text-dark ms-2"><?= $products->num_rows ?></span>
                        </h5>
                        
                        <!-- FILTROS -->
                        <div class="d-flex gap-2">
                            <form method="GET" class="d-flex gap-2">
                                <select name="categoria" class="form-select form-select-sm" 
                                        onchange="this.form.submit()" style="min-width: 120px;">
                                    <option value="todas">Todas las categorías</option>
                                    <?php 
                                    $categories->data_seek(0);
                                    while ($cat = $categories->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $cat['nombre'] ?>" 
                                                <?= $categoria_filtro === $cat['nombre'] ? 'selected' : '' ?>>
                                            <?= ucfirst($cat['nombre']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                
                                <input type="text" name="search" class="form-control form-control-sm" 
                                       placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>"
                                       style="min-width: 120px;">
                                <button type="submit" class="btn btn-outline-light btn-sm">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if ($search || $categoria_filtro): ?>
                                    <a href="dashboard.php" class="btn btn-outline-warning btn-sm" title="Limpiar filtros">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <?php if ($products->num_rows === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay productos que mostrar</h5>
                            <p class="text-muted">
                                <?php if ($search || $categoria_filtro): ?>
                                    No se encontraron productos con los filtros aplicados.
                                    <br><a href="dashboard.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-undo"></i> Ver Todos los Productos
                                    </a>
                                <?php else: ?>
                                    Comienza agregando tu primer producto usando el formulario de la izquierda.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="80"><i class="fas fa-image"></i> Imagen</th>
                                        <th><i class="fas fa-tag"></i> Nombre</th>
                                        <th width="120"><i class="fas fa-money-bill"></i> Precio</th>
                                        <th width="100"><i class="fas fa-list"></i> Categoría</th>
                                        <th width="100" class="d-none d-lg-table-cell"><i class="fas fa-calendar"></i> Fecha</th>
                                        <th width="140" class="d-none d-md-table-cell"><i class="fas fa-cogs"></i> Acciones</th>
                                        <th width="60" class="d-md-none text-center"><i class="fas fa-cogs"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $products->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <img src="../<?= htmlspecialchars($row['imagen']) ?>" 
                                                     class="product-img" 
                                                     alt="<?= htmlspecialchars($row['nombre']) ?>"
                                                     loading="lazy">
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark">
                                                    <?= htmlspecialchars($row['nombre']) ?>
                                                </div>
                                                <small class="text-muted">ID: <?= $row['id'] ?></small>
                                            </td>
                                            <td>
                                                <span class="price-badge">
                                                    $<?= number_format($row['precio'], 0, ',', '.') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary rounded-pill">
                                                    <?= ucfirst(htmlspecialchars($row['categoria'])) ?>
                                                </span>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i>
                                                    <?= date('d/m/Y', strtotime($row['fecha'])) ?>
                                                    <br>
                                                    <span style="font-size: 0.8rem;">
                                                        <?= date('H:i', strtotime($row['fecha'])) ?>
                                                    </span>
                                                </small>
                                            </td>
                                            <td>
                                                <!-- Desktop view: horizontal buttons -->
                                                <div class="btn-group d-none d-md-flex" role="group">
                                                    <a href="edit_producto.php?id=<?= $row['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Editar producto">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= htmlspecialchars($row['instagram']) ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-info" 
                                                       title="Ver en Instagram">
                                                        <i class="fab fa-instagram"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteProduct(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nombre']) ?>')"
                                                            title="Eliminar producto">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Mobile view: dropdown menu -->
                                                <div class="dropdown d-md-none d-none">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" 
                                                            data-bs-toggle="dropdown" 
                                                            aria-expanded="false">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="edit_producto.php?id=<?= $row['id'] ?>">
                                                                <i class="fas fa-edit text-primary"></i> Editar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="<?= htmlspecialchars($row['instagram']) ?>" target="_blank">
                                                                <i class="fab fa-instagram text-info"></i> Instagram
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" 
                                                               onclick="deleteProduct(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nombre']) ?>'); return false;">
                                                                <i class="fas fa-trash"></i> Eliminar
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- FOOTER DEL DASHBOARD -->
<footer class="mt-5 py-4 bg-dark text-white">
    <div class="container text-center">
        <div class="row">
            <div class="col-md-4">
                <h6><i class="fas fa-gem text-warning"></i> CFM Joyas</h6>
                <small>Panel de Administración</small>
            </div>
            <div class="col-md-4">
                <h6>Estadísticas</h6>
                <small><?= $stats['total_productos'] ?> productos en total</small>
            </div>
            <div class="col-md-4">
                <h6>Última actualización</h6>
                <small><?= date('d/m/Y H:i') ?></small>
            </div>
        </div>
        <hr class="my-3">
        <small>
            <i class="fas fa-user-shield"></i> 
            Sesión activa: <?= htmlspecialchars($user_name) ?>
        </small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
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
    
    // Animate stats cards on load
    const statCards = document.querySelectorAll('.stats-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Form validation and loading state
    const addProductForm = document.getElementById('addProductForm');
    if (addProductForm) {
        addProductForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Image preview functionality
    const imageInput = document.getElementById('imagen');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamaño (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('La imagen es demasiado grande. Máximo 5MB.');
                    this.value = '';
                    return;
                }
                
                // Validar tipo
                if (!file.type.startsWith('image/')) {
                    alert('Por favor selecciona un archivo de imagen válido.');
                    this.value = '';
                    return;
                }
            }
        });
    }
    
    // Auto-format price input - Allow any value
    const priceInput = document.getElementById('precio');
    if (priceInput) {
        priceInput.addEventListener('input', function() {
            // Allow any numeric value, no restriction to multiples of 500
            let value = parseInt(this.value);
            if (value && value < 0) {
                this.value = 0;
            }
        });
    }

    // MOBILE IMPROVEMENTS - Navbar collapse behavior
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('#navbarNav');
    
    if (navbarToggler && navbarCollapse) {
        // Show mobile admin info when navbar is toggled
        navbarToggler.addEventListener('click', function() {
            const mobileAdminInfo = document.querySelector('.admin-info.d-md-none');
            if (mobileAdminInfo) {
                setTimeout(() => {
                    const isExpanded = navbarCollapse.classList.contains('show');
                    mobileAdminInfo.style.display = isExpanded ? 'block' : 'none';
                }, 350);
            }
        });
        
        // Close dropdown when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                const dropdownToggle = dropdown.previousElementSibling;
                if (!dropdown.contains(event.target) && !dropdownToggle.contains(event.target)) {
                    const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                    if (bsDropdown) {
                        bsDropdown.hide();
                    }
                }
            });
        });
    }
    
    console.log('CFM Joyas Dashboard loaded successfully');
    console.log('User authenticated:', '<?= $user_authenticated ? "true" : "false" ?>');
    console.log('Products loaded:', '<?= $products->num_rows ?>');
});

// Function to delete product with confirmation
function deleteProduct(productId, productName) {
    // Custom confirmation dialog
    const confirmed = confirm(
        `⚠️ ¿Estás seguro de que quieres eliminar este producto?\n\n` +
        `"${productName}"\n\n` +
        `Esta acción no se puede deshacer. El producto y su imagen serán eliminados permanentemente.`
    );
    
    if (confirmed) {
        // Show loading state
        const deleteBtn = event.target.closest('button');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteBtn.disabled = true;
        }
        
        // Redirect to delete
        window.location.href = `dashboard.php?delete=${productId}`;
    }
}

// Función mejorada para logout
function logoutUser() {
    // Confirmar logout
    const confirmed = confirm('¿Estás seguro de que quieres cerrar sesión?');
    
    if (confirmed) {
        // Mostrar loading
        const logoutBtn = document.querySelector('a[href="logout.php"]');
        if (logoutBtn) {
            logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cerrando...';
        }
        
        // Limpiar localStorage y sessionStorage
        try {
            localStorage.clear();
            sessionStorage.clear();
            
            // Limpiar datos específicos de CFM
            const cfmKeys = ['cfm_form_backup', 'cfm_user_data', 'cfm_session'];
            cfmKeys.forEach(key => {
                localStorage.removeItem(key);
                sessionStorage.removeItem(key);
            });
        } catch (e) {
            console.log('No se pudo limpiar el storage local');
        }
        
        // Limpiar cookies desde JavaScript (adicional al PHP)
        document.cookie = "cfm_auth=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        document.cookie = "cfm_test=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        
        // Redirigir al logout
        window.location.href = 'logout.php';
    }
}

// Auto-logout después de inactividad (30 minutos)
let inactivityTimer;
let warningTimer;
const INACTIVITY_TIME = 30 * 60 * 1000; // 30 minutos
const WARNING_TIME = 25 * 60 * 1000; // 25 minutos

function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    clearTimeout(warningTimer);
    
    // Warning a los 25 minutos
    warningTimer = setTimeout(() => {
        const continueSession = confirm(
            '⚠️ Tu sesión expirará en 5 minutos por inactividad.\n\n' +
            '¿Quieres continuar trabajando?'
        );
        
        if (!continueSession) {
            logoutUser();
        }
    }, WARNING_TIME);
    
    // Auto-logout a los 30 minutos
    inactivityTimer = setTimeout(() => {
        alert('Tu sesión ha expirado por inactividad. Serás redirigido al login.');
        logoutUser();
    }, INACTIVITY_TIME);
}

// Eventos que resetean el timer de inactividad
['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
    document.addEventListener(event, resetInactivityTimer, true);
});

// Iniciar el timer
resetInactivityTimer();

console.log('=== CFM JOYAS DASHBOARD DEBUG ===');
console.log('Session Status:', '<?= session_status() ?>');
console.log('Session ID:', '<?= session_id() ?>');
console.log('User ID:', '<?= $user_id ?>');
console.log('User Name:', '<?= $user_name ?>');
console.log('Auth Method:', '<?= isset($_SESSION["user_id"]) ? "Session" : "Cookie" ?>');
console.log('Products Count:', '<?= $products->num_rows ?>');
console.log('=============================');
</script>

</body>
</html>