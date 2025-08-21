<?php
// =======================================================
// EDITAR PRODUCTO - CFM JOYAS - VERSIÓN FINAL CORREGIDA
// admin/edit_producto.php
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

// VERIFICAR AUTENTICACIÓN
$user_authenticated = false;
$user_id = null;

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) < 3600) {
        $user_authenticated = true;
        $user_id = $_SESSION['user_id'];
    }
}

if (!$user_authenticated) {
    $auth_data = verifyAuthCookie();
    if ($auth_data) {
        $user_authenticated = true;
        $user_id = $auth_data['user_id'];
        $_SESSION['user_id'] = $auth_data['user_id'];
        $_SESSION['user_name'] = $auth_data['user_name'];
        $_SESSION['user_email'] = $auth_data['user_email'];
        $_SESSION['login_time'] = time();
    }
}

if (!$user_authenticated || !$user_id) {
    $_SESSION['error'] = 'Debe iniciar sesión para editar productos.';
    header('Location: login.php');
    exit;
}

// OBTENER ID DEL PRODUCTO
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    $_SESSION['error'] = 'ID de producto no válido.';
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// OBTENER PRODUCTO ACTUAL
try {
    $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if (!$product) {
        $_SESSION['error'] = 'Producto no encontrado.';
        header('Location: dashboard.php');
        exit;
    }
    
    error_log("CFM Edit: Producto cargado - ID: $id, Nombre: " . $product['nombre']);
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error buscando producto: ' . $e->getMessage();
    error_log("CFM Edit: Error buscando producto: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}

// OBTENER CATEGORÍAS
try {
    $categories = $conn->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre");
} catch (Exception $e) {
    $error = 'Error cargando categorías: ' . $e->getMessage();
    error_log("CFM Edit: Error categorías: " . $e->getMessage());
}

// PROCESAR ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiar_input($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $categoria = limpiar_input($_POST['categoria']);
    $instagram = limpiar_input($_POST['instagram']);
    
    error_log("CFM Edit: Datos recibidos - Nombre: '$nombre', Precio: $precio, Categoria: '$categoria'");
    
    // Validaciones
    if (empty($nombre) || strlen($nombre) < 3) {
        $error = 'El nombre debe tener al menos 3 caracteres.';
    } elseif ($precio < 0) {
        $error = 'El precio debe ser mayor a 0.';
    } elseif (empty($categoria)) {
        $error = 'Debe seleccionar una categoría.';
    } elseif (empty($instagram) || !filter_var($instagram, FILTER_VALIDATE_URL)) {
        $error = 'El enlace de Instagram no es válido.';
    } else {
        // Validar categoría en BD
        try {
            $stmt = $conn->prepare("SELECT nombre FROM categorias WHERE nombre = ? AND activa = 1");
            $stmt->bind_param('s', $categoria);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $error = 'Categoría no válida.';
            }
        } catch (Exception $e) {
            $error = 'Error validando categoría: ' . $e->getMessage();
        }
        
        if (!$error) {
            $ruta_imagen = $product['imagen']; // Mantener imagen actual por defecto
            
            // Procesar nueva imagen si se subió
            if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $imagen = $_FILES['imagen'];
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                
                error_log("CFM Edit: Nueva imagen recibida - Nombre: " . $imagen['name'] . ", Tipo: " . $imagen['type']);
                
                if (!in_array($imagen['type'], $allowed_types)) {
                    $error = 'Tipo de imagen no válido. Use JPG, PNG, GIF o WebP.';
                } elseif ($imagen['size'] > 5 * 1024 * 1024) {
                    $error = 'La imagen es demasiado grande. Máximo 5MB.';
                } else {
                    // Validar que es imagen real
                    $image_info = getimagesize($imagen['tmp_name']);
                    if ($image_info === false) {
                        $error = 'El archivo no es una imagen válida.';
                    } else {
                        // Generar nombre único
                        $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
                        $nombre_imagen = 'producto_' . time() . '_' . uniqid() . '.' . $extension;
                        $directorio_destino = "../img/productos/";
                        $ruta_completa = $directorio_destino . $nombre_imagen;
                        $ruta_imagen = "img/productos/" . $nombre_imagen;
                        
                        // Crear directorio si no existe
                        if (!is_dir($directorio_destino)) {
                            mkdir($directorio_destino, 0755, true);
                        }
                        
                        if (!move_uploaded_file($imagen['tmp_name'], $ruta_completa)) {
                            $error = 'Error al guardar la nueva imagen.';
                            error_log("CFM Edit: Error moviendo imagen");
                        } else {
                            // Eliminar imagen anterior si existe y es diferente
                            $imagen_anterior = "../" . $product['imagen'];
                            if (file_exists($imagen_anterior) && $imagen_anterior !== $ruta_completa) {
                                unlink($imagen_anterior);
                                error_log("CFM Edit: Imagen anterior eliminada: $imagen_anterior");
                            }
                            error_log("CFM Edit: Nueva imagen guardada: $ruta_completa");
                        }
                    }
                }
            }
            
            // Actualizar en base de datos si no hay errores
            if (!$error) {
                try {
                    $stmt = $conn->prepare("UPDATE productos SET nombre=?, precio=?, categoria=?, instagram=?, imagen=? WHERE id=?");
                    $stmt->bind_param('sdsssi', $nombre, $precio, $categoria, $instagram, $ruta_imagen, $id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Producto '$nombre' actualizado exitosamente.";
                        error_log("CFM Edit: Producto ID $id actualizado exitosamente");
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = 'Error al actualizar el producto: ' . $stmt->error;
                        error_log("CFM Edit: Error BD: " . $stmt->error);
                    }
                } catch (Exception $e) {
                    $error = 'Error del sistema: ' . $e->getMessage();
                    error_log("CFM Edit: Excepción: " . $e->getMessage());
                }
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
    <title>Editar Producto - CFM Joyas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Essential custom styles that can't be replaced with Bootstrap */
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .edit-card {
            border: none;
        }

        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }

        .current-image {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 100%;
            height: auto;
        }

        .info-box {
            background: linear-gradient(145deg, #e3f2fd, #f3e5f5);
            border-left: 4px solid #2196f3;
        }

        .navbar {
            background: linear-gradient(135deg, #212529 0%, #000 100%) !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark shadow">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
        <span class="text-light">
            <i class="fas fa-edit"></i> Editando Producto #<?= $id ?>
        </span>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card edit-card shadow-lg rounded-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-edit"></i> Editar Producto: <?= htmlspecialchars($product['nombre']) ?>
                    </h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger border-0 rounded-3">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="editForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-tag"></i> Nombre del Producto
                                    </label>
                                    <input name="nombre" class="form-control rounded-3 border-2" style="border-color: #e9ecef; transition: all 0.3s ease;"
                                           value="<?= htmlspecialchars($product['nombre']) ?>" 
                                           required minlength="3" maxlength="100">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-money-bill"></i> Precio (CLP)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text rounded-start-3 border-2" style="border-color: #e9ecef;">$</span>
                                        <input name="precio" type="number" class="form-control rounded-end-3 border-2" style="border-color: #e9ecef; transition: all 0.3s ease;"
                                               min="0" step="1"
                                               value="<?= htmlspecialchars($product['precio']) ?>" required>
                                    </div>
                                    <small class="text-muted">
                                        Precio actual: $<?= number_format($product['precio'], 0, ',', '.') ?>
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-list"></i> Categoría
                                    </label>
                                    <select name="categoria" class="form-select rounded-3 border-2" style="border-color: #e9ecef; transition: all 0.3s ease;" required>
                                        <?php if ($categories): ?>
                                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                                <option value="<?= $cat['nombre'] ?>" 
                                                        <?= $product['categoria'] === $cat['nombre'] ? 'selected' : '' ?>>
                                                    <?= ucfirst($cat['nombre']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fab fa-instagram"></i> Enlace Instagram
                                    </label>
                                    <input name="instagram" type="url" class="form-control rounded-3 border-2" style="border-color: #e9ecef; transition: all 0.3s ease;"
                                           value="<?= htmlspecialchars($product['instagram']) ?>" required>
                                    <small class="text-muted">
                                        <a href="<?= htmlspecialchars($product['instagram']) ?>" target="_blank" class="text-decoration-none">
                                            <i class="fab fa-instagram"></i> Ver enlace actual
                                        </a>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Imagen Actual</label><br>
                                    <img src="../<?= htmlspecialchars($product['imagen']) ?>" 
                                         alt="<?= htmlspecialchars($product['nombre']) ?>" 
                                         class="current-image mb-3" style="max-width: 300px;">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-image"></i> Nueva Imagen (opcional)
                                    </label>
                                    <input name="imagen" type="file" class="form-control rounded-3 border-2" style="border-color: #e9ecef; transition: all 0.3s ease;" accept="image/*">
                                    <small class="text-muted">
                                        Formatos: JPG, PNG, GIF, WebP. Máximo 5MB.
                                        <br>Si no selecciona imagen, se mantendrá la actual.
                                    </small>
                                </div>
                                
                                <div class="info-box p-3 rounded-3">
                                    <h6><i class="fas fa-info-circle"></i> Información del Producto</h6>
                                    <small>
                                        <strong>ID:</strong> <?= $product['id'] ?><br>
                                        <strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($product['fecha'])) ?><br>
                                        <strong>Categoría actual:</strong> <?= ucfirst($product['categoria']) ?><br>
                                        <strong>Archivo actual:</strong> <?= basename($product['imagen']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="dashboard.php" class="btn btn-secondary rounded-3">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary rounded-3" id="submitBtn">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Loading state en submit
    form.addEventListener('submit', function() {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        submitBtn.disabled = true;
    });
    
    // Validación de imagen
    const imageInput = document.querySelector('input[name="imagen"]');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamaño
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
    
    // Auto-format price
    const priceInput = document.querySelector('input[name="precio"]');
    if (priceInput) {
        priceInput.addEventListener('input', function() {
            // Allow any numeric value, no restriction to multiples of 500
            let value = parseInt(this.value);
            if (value && value < 0) {
                this.value = 0;
            }
        });
    }
    
    console.log('CFM Edit Product loaded for ID:', <?= $id ?>);
});
</script>

</body>
</html>