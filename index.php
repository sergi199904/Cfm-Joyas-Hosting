<?php
require_once 'includes/db.php';

// Filtro por categoría
$categoria_filtro = isset($_GET['categoria']) ? limpiar_input($_GET['categoria']) : '';

// Consulta de productos con filtro
$query = "SELECT * FROM productos WHERE 1=1";
$params = [];
$types = '';

if ($categoria_filtro && $categoria_filtro !== 'todas') {
    $query .= " AND categoria = ?";
    $params[] = $categoria_filtro;
    $types .= 's';
}

$query .= " ORDER BY fecha DESC";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Obtener categorías para el filtro
$categories = $conn->query("SELECT nombre, COUNT(*) as total FROM productos GROUP BY categoria ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>CFM Joyas - Venta de Joyas & Accesorios</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="description" content="CFM Joyas - Especialistas en joyas, cerámicas y accesorios únicos. Encuentra las mejores piezas con precios accesibles.">
  
  <!-- Normalize CSS para consistencia entre navegadores -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- CSS personalizado - RUTA CORREGIDA -->
  <link href="css/style.css" rel="stylesheet">
  <style>
    .admin-btn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
      background: linear-gradient(45deg, #007bff, #0056b3);
      border: none;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 15px rgba(0,123,255,0.3);
      transition: all 0.3s ease;
      font-size: 1.2rem;
    }
    .admin-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0,123,255,0.4);
    }
    
    nav{ background: linear-gradient(135deg, #212529 0%, #000 100%) !important; }
    
/* ===== CARRUSEL RESPONSIVE CON BOOTSTRAP ===== */
.carousel {
  position: relative;
  overflow: hidden;
  width: 100%;
  margin-bottom: 0;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

/* Imagen contenedora */
.carousel-image-container {
  position: relative;
  width: 100%;
  height: 75vh;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Imagen del carrusel */
.carousel-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
}

@media (max-width: 768px) {
  .carousel-image-container {
    height: 60vh;
  }

  .carousel-content {
    padding: 15px 20px;
    max-height: 55vh;
    overflow-y: auto;
    border-radius: 15px;
  }

  .carousel-title {
    font-size: 1.3rem;
  }

  .carousel-description {
    font-size: 0.9rem;
    line-height: 1.4;
  }

  .btn-carousel {
    padding: 10px 20px;
    font-size: 0.8rem;
  }

  .carousel-caption {
    bottom: 10px;
  }
}

/* Transiciones fade */
.carousel-fade .carousel-item {
  opacity: 0;
  transition-duration: 1s;
  transition-property: opacity;
}

.carousel-fade .carousel-item.active {
  opacity: 1;
}

/* Estilos del caption y contenido */
.carousel-caption {
  position: absolute;
  bottom: 40px;
  left: 0;
  right: 0;
  z-index: 4;
  background: none;
  padding: 25px;
  text-align: center;
}

.carousel-content {
  max-width: 700px;
  margin: 0 auto;
  animation: slideUpFade 0.8s ease-out forwards;
  opacity: 0;
  transform: translateY(30px);
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 25px;
  border: 1px solid rgba(255,255,255,0.1);
}

.carousel-item.active .carousel-content {
  animation: slideUpFade 0.8s ease-out 0.3s forwards;
}

@keyframes slideUpFade {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.carousel-title {
  font-family: 'Playfair Display', serif;
  font-size: 2.5rem;
  font-weight: 700;
  color: #fff;
  margin-bottom: 15px;
  text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
}

.carousel-description {
  font-family: 'Inter', sans-serif;
  font-size: 1.2rem;
  color: rgba(255,255,255,0.95);
  margin-bottom: 25px;
  text-shadow: 1px 1px 4px rgba(0,0,0,0.8);
  line-height: 1.6;
}


/* Botón personalizado */
.btn-carousel {
  background: linear-gradient(45deg, #ffd700, #ffb347);
  border: none;
  color: #000;
  font-weight: 600;
  font-family: 'Inter', sans-serif;
  padding: 15px 35px;
  border-radius: 50px;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  font-size: 1rem;
  transition: all 0.4s ease;
  box-shadow: 0 6px 20px rgba(255,215,0,0.4);
  text-decoration: none;
  display: inline-block;
}

.btn-carousel:hover {
  background: linear-gradient(45deg, #ffb347, #ffd700);
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 10px 30px rgba(255,215,0,0.6);
  color: #000;
}

/* Indicadores del carrusel */
.carousel-indicators {
  bottom: 25px;
  margin-bottom: 0;
}

.carousel-indicators button {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 3px solid rgba(255,255,255,0.4);
  background: transparent;
  margin: 0 6px;
  transition: all 0.4s ease;
}

.carousel-indicators button.active {
  background: #ffd700;
  border-color: #ffd700;
  transform: scale(1.4);
  box-shadow: 0 0 15px rgba(255,215,0,0.8);
}

.carousel-indicators button:hover {
  border-color: rgba(255,255,255,0.8);
  transform: scale(1.2);
}

    footer {
      margin-bottom: 0 !important;
      padding-bottom: 0 !important;
    }
  </style>
</head>

<body>
  <!-- Botón de administración -->
  <a href="admin/login.php" class="btn admin-btn text-white" title="Panel de Administración">
    <i class="fas fa-cog"></i>
  </a>

  <!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow ">

  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/">
      <img src="img/logooficial.jpg" alt="Logo" class="rounded-circle me-2" style="height: 50px; width: 50px; object-fit: cover;">
      <span class="fw-bold">CFM Joyas</span>
    </a>
    
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link text-uppercase fw-semibold" href="#historia">Historia</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-uppercase fw-semibold" href="#productos">Productos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-uppercase fw-semibold" href="#contacto">Contacto</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-uppercase fw-semibold" href="#ubicacion">Ubicación</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
  <!-- Header -->
  <header class="text-center py-5 bg-light" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;">
    <div class="container">
      <h1 class="header-title mb-3">CFM Joyas</h1>
      <p class="lead text-muted fst-italic">"Venta de Joyas & Accesorios"</p>
      <p class="text-muted d-flex justify-content-center align-items-center flex-wrap gap-3">
        <span><i class="fas fa-gem text-warning"></i> Joyas únicas</span>
        <span><i class="fas fa-palette text-info"></i> Cerámicas artesanales</span>
        <span><i class="fas fa-star text-warning"></i> Accesorios especiales</span>
      </p>
    </div>
  </header>

  <!-- CARRUSEL OPTIMIZADO -->
  <div id="mainCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
    <div class="carousel-indicators">
      <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active"></button>
      <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1"></button>
      <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2"></button>
      <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="3"></button>
      <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="4"></button>
      <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="5"></button>
      <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="6"></button>
    </div>
    
    <div class="carousel-inner">
      <!-- Slide 1: Imagen 6 - ÚNICA CON TEXTO DE BIENVENIDA -->
      <div class="carousel-item active">
        <div class="carousel-image-container">
          <img src="img/Carrusel/imagen6.jpg" class="d-block carousel-img img-fluid" alt="Bienvenidos a CFM Joyas" loading="lazy">
          <div class="carousel-overlay"></div>
        </div>
        <div class="carousel-caption">
          <div class="carousel-content">
            <h2 class="carousel-title">Bienvenidos a CFM Joyas</h2>
            <p class="carousel-description">Un espacio donde el arte, la creatividad y la calidad se encuentran para ofrecerte la mejor experiencia en joyas y cerámicas.</p>
            <a href="#contacto" class="btn-carousel">
              <i class="fas fa-store"></i> Visítanos
            </a>
          </div>
        </div>
      </div>

      <!-- Slide 2: Imagen 1 - SOLO IMAGEN -->
      <div class="carousel-item">
        <div class="carousel-image-container">
          <img src="img/Carrusel/imagen1.jpg" class="d-block carousel-img img-fluid" alt="Joyas artesanales exclusivas" loading="lazy">
        </div>
      </div>

      <!-- Slide 3: Imagen 2 - SOLO IMAGEN -->
      <div class="carousel-item">
        <div class="carousel-image-container">
          <img src="img/Carrusel/imagen2.jpg" class="d-block carousel-img img-fluid" alt="Collares con piedras naturales de colores" loading="lazy">
        </div>
      </div>

      <!-- Slide 4: Imagen 7 - SOLO IMAGEN -->
      <div class="carousel-item">
        <div class="carousel-image-container">
          <img src="img/Carrusel/imagen7.jpg" class="d-block carousel-img img-fluid" alt="Pulseras artesanales con diferentes estilos" loading="lazy">
        </div>
      </div>

      <!-- Slide 5: Imagen 5 - SOLO IMAGEN -->
      <div class="carousel-item">
        <div class="carousel-image-container">
          <img src="img/Carrusel/imagen5.jpg" class="d-block carousel-img img-fluid" alt="Colección premium de joyas variadas" loading="lazy">
        </div>
      </div>

      <!-- Slide 6: Imagen 3 - SOLO IMAGEN -->
      <div class="carousel-item">
        <div class="carousel-image-container">
          <img src="img/Carrusel/imagen3.jpg" class="d-block carousel-img img-fluid" alt="Cerámicas artesanales hechas a mano" loading="lazy">
        </div>
      </div>

      <!-- Slide 7: Imagen 4 - SOLO IMAGEN -->
      <div class="carousel-item">
        <div class="carousel-image-container">
          <img src="img/Carrusel/imagen4.jpg" class="d-block carousel-img img-fluid" alt="Piezas únicas de cerámica funcional" loading="lazy">
        </div>
      </div>
    </div>
    
    <!-- Controles -->
    <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
      <div class="carousel-control-icon">
        <i class="fas fa-chevron-left"></i>
      </div>
      <span class="visually-hidden">Anterior</span>
    </button>
    
    <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
      <div class="carousel-control-icon">
        <i class="fas fa-chevron-right"></i>
      </div>
      <span class="visually-hidden">Siguiente</span>
    </button>
  </div>

  <!-- Sección Historia -->
  <section id="historia" class="py-5 text-center" style="padding-top: 100px !important; margin-top: -20px; scroll-margin-top: 90px;">
    <div class="container">
      <h2 class="mb-4 position-relative">Una parte de nosotros</h2>
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <p class="lead">
           Me cambié a Zapallar en busca de una vida más tranquila, donde el arte se convirtió en mi forma de expresión. Desde la cerámica hasta la joyería, cada pieza refleja mi creatividad . En esta página comparto no solo mis creaciones, sino también mi mundo.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Filtro de Categorías ACTUALIZADO -->
  <section class="py-3" id="filtros" style="scroll-margin-top: 90px;">
    <div class="container">
      <div class="category-filter text-center">
        <h5 class="mb-3"><i class="fas fa-filter"></i> Filtrar por categoría</h5>
        <div class="d-flex flex-wrap justify-content-center gap-2">
          <!-- Filtro "Todas" -->
          <a href="index.php#productos" class="btn filter-btn <?= empty($categoria_filtro) ? 'active' : '' ?>">
            <i class="fas fa-th"></i> Todas
          </a>
          
          <!-- Categorías principales ORIGINALES -->
          <a href="index.php?categoria=joyas#productos" 
             class="btn filter-btn <?= $categoria_filtro === 'joyas' ? 'active' : '' ?>">
            <i class="fas fa-gem"></i> Joyas
          </a>
          
          <a href="index.php?categoria=ceramicas#productos" 
             class="btn filter-btn <?= $categoria_filtro === 'ceramicas' ? 'active' : '' ?>">
            <i class="fas fa-palette"></i> Cerámicas
          </a>
          
          <a href="index.php?categoria=otros#productos" 
             class="btn filter-btn <?= $categoria_filtro === 'otros' ? 'active' : '' ?>">
            <i class="fas fa-star"></i> Otros
          </a>
          
          <!-- NUEVAS categorías específicas -->
          <a href="index.php?categoria=collares#productos" 
             class="btn filter-btn <?= $categoria_filtro === 'collares' ? 'active' : '' ?>">
            <i class="fas fa-circle-notch"></i> Collares
          </a>
          
          <a href="index.php?categoria=pulseras#productos" 
             class="btn filter-btn <?= $categoria_filtro === 'pulseras' ? 'active' : '' ?>">
            <i class="fas fa-link"></i> Pulseras
          </a>
          
          <a href="index.php?categoria=aretes#productos" 
             class="btn filter-btn <?= $categoria_filtro === 'aretes' ? 'active' : '' ?>">
            <i class="fas fa-earring"></i> Aretes
          </a>
          
          <a href="index.php?categoria=anillos#productos" 
             class="btn filter-btn <?= $categoria_filtro === 'anillos' ? 'active' : '' ?>">
            <i class="fas fa-ring"></i> Anillos
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Sección Productos SOLO CON BOOTSTRAP -->
<section id="productos" class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-4">
      Nuestros productos
      <?php if ($categoria_filtro): ?>
        <small class="text-muted d-block mt-2">- <?= ucfirst($categoria_filtro) ?></small>
      <?php endif; ?>
    </h2>
    
    <?php if ($result->num_rows === 0): ?>
      <div class="text-center py-5">
        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">No hay productos en esta categoría</h4>
        <a href="index.php#productos" class="btn btn-primary mt-3">Ver todos los productos</a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="col-6 col-sm-6 col-md-4 col-lg-3">
            <a href="<?= htmlspecialchars($row['instagram']) ?>" 
               target="_blank" 
               class="text-decoration-none">
              <div class="card h-100 shadow-sm border-0">
                <!-- Badge de categoría -->
                <div class="position-absolute top-0 start-0 m-2 z-index-1">
                  <span class="badge bg-dark"><?= ucfirst(htmlspecialchars($row['categoria'])) ?></span>
                </div>
                
                <!-- Imagen con ratio cuadrado -->
                <div class="ratio ratio-1x1">
                  <img src="<?= htmlspecialchars($row['imagen']) ?>" 
                       class="card-img-top object-fit-cover" 
                       alt="<?= htmlspecialchars($row['nombre']) ?>"
                       loading="lazy">
                </div>
                
                <!-- Info del producto -->
                <div class="card-body bg-dark text-white">
                  <h6 class="card-title text-center mb-2">
                    <?= htmlspecialchars($row['nombre']) ?>
                  </h6>
                  
                  <p class="card-text text-center mb-2">
                    <span class="h5 text-warning fw-bold">
                      $<?= number_format($row['precio'], 0, ',', '.') ?> CLP
                    </span>
                  </p>
                  
                  <div class="d-flex justify-content-between align-items-center small">
                    <span>
                      <i class="fas fa-tag"></i> <?= ucfirst(htmlspecialchars($row['categoria'])) ?>
                    </span>
                    <span>
                      <i class="fab fa-instagram"></i> Ver
                    </span>
                  </div>
                </div>
              </div>
            </a>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

  <!-- Sección Contacto MEJORADA -->
  <section id="contacto" class="py-5" style="padding-top: 100px !important; margin-top: -20px; scroll-margin-top: 90px;">
    <div class="container">
      <h2 class="text-center mb-4 position-relative">Contáctanos</h2>
      <div class="row justify-content-center">
        <div class="col-md-6">
          <div class="card shadow-lg border-0 rounded-4">
            <div class="card-body p-4">
              <form id="contactForm" action="send_email.php" method="POST" novalidate>
                <!-- Campo Nombre -->
                <div class="mb-3">
                  <label for="name" class="form-label fw-semibold">
                    <i class="fas fa-user text-primary"></i> Nombre <span class="text-danger">*</span>
                  </label>
                  <input type="text" name="name" id="name" class="form-control rounded-3 border-2" style="border-color: #e9ecef; transition: all 0.3s ease;"
                         placeholder="Tu nombre completo" required minlength="2" maxlength="50">
                  <div class="invalid-feedback">
                    El nombre debe tener entre 2 y 50 caracteres.
                  </div>
                  <div class="valid-feedback">
                    ¡Perfecto!
                  </div>
                </div>

                <!-- Campo Email -->
                <div class="mb-3">
                  <label for="email" class="form-label fw-semibold">
                    <i class="fas fa-envelope text-primary"></i> Correo Electrónico <span class="text-danger">*</span>
                  </label>
                  <input type="email" name="email" id="email" class="form-control rounded-3 border-2" style="border-color: #e9ecef; transition: all 0.3s ease;"
                         placeholder="tu@email.com" required>
                  <div class="invalid-feedback">
                    Por favor ingresa un email válido.
                  </div>
                  <div class="valid-feedback">
                    ¡Email válido!
                  </div>
                </div>

                <!-- Campo Mensaje -->
                <div class="mb-3">
                  <label for="message" class="form-label fw-semibold">
                    <i class="fas fa-comment text-primary"></i> Mensaje <span class="text-danger">*</span>
                  </label>
                  <textarea name="message" id="message" class="form-control rounded-3 border-2" style="border-color: #e9ecef; transition: all 0.3s ease;" rows="4" 
                            placeholder="Cuéntanos sobre tu consulta, producto de interés, o cualquier pregunta..." 
                            required minlength="10" maxlength="500"></textarea>
                  <div class="invalid-feedback">
                    El mensaje debe tener entre 10 y 500 caracteres.
                  </div>
                  <div class="valid-feedback">
                    ¡Mensaje perfecto!
                  </div>
                  <small class="text-muted">
                    <span id="charCount">0</span>/500 caracteres
                  </small>
                </div>

                <!-- Botón Submit -->
                <div class="text-center">
                  <button type="submit" id="submitBtn" class="btn btn-dark btn-lg rounded-3 px-4" style="background: linear-gradient(45deg, #333, #000) !important;">
                    <i class="fas fa-paper-plane"></i> 
                    <span class="btn-text">Enviar Mensaje</span>
                  </button>
                </div>

                <!-- Indicador de carga -->
                <div id="loadingSpinner" class="text-center mt-3" style="display: none;">
                  <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Enviando...</span>
                  </div>
                  <p class="mt-2 text-muted">Enviando tu mensaje...</p>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Sección Ubicación -->
  <section id="ubicacion" class="py-5 bg-light" style="padding-top: 100px !important; margin-top: -20px; scroll-margin-top: 90px;">
    <div class="container text-center">
      <h2 class="mb-4 position-relative">Ubicación</h2>
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <iframe src="https://maps.google.com/maps?q=-32.5541667,-71.4577222&output=embed"
                  width="100%" height="300" style="border:0;" class="shadow rounded-3"></iframe>
          <p class="mt-3 text-muted d-flex justify-content-center align-items-center">
            <i class="fas fa-map-marker-alt text-danger me-2"></i> Zapallar, Región de Valparaíso, Chile
          </p>
          <p class="text-muted small d-flex justify-content-center align-items-center">
            <i class="fas fa-compass text-info me-2"></i> 32°33'15.0"S 71°27'27.8"W
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Redes Sociales -->
  <div class="text-center py-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
      <h4 class="mb-3" style="font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 600; color: #333;">¡Síguenos en nuestras redes sociales!</h4>
      <div class="d-flex justify-content-center gap-4 mt-3">
        <a href="https://www.instagram.com/cfmjoyas/"
           target="_blank" class="text-decoration-none p-3 bg-white rounded-circle shadow-lg" 
           style="transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center;">
          <img src="https://upload.wikimedia.org/wikipedia/commons/9/95/Instagram_logo_2022.svg"
               alt="Instagram" width="40" height="40" style="transition: transform 0.3s ease;">
        </a>
        <a href="https://wa.me/+56998435160"
           target="_blank" class="text-decoration-none p-3 bg-white rounded-circle shadow-lg"
           style="transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center;">
          <img src="https://cdn.jsdelivr.net/npm/simple-icons@v3/icons/whatsapp.svg"
               alt="WhatsApp" width="40" height="40" style="transition: transform 0.3s ease;">
        </a>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="py-4 bg-dark text-center text-white" style="background: linear-gradient(135deg, #212529 0%, #000 100%) !important;">
    <div class="container">
      <div class="row">
        <div class="col-md-4">
          <h5 class="text-warning" style="font-family: 'Playfair Display', serif; font-weight: 600;"><i class="fas fa-gem"></i> CFM Joyas</h5>
          <p class="small text-light">Especialistas en joyas y accesorios únicos</p>
        </div>
        <div class="col-md-4">
          <h6 class="text-warning" style="font-family: 'Playfair Display', serif; font-weight: 600;">Categorías</h6>
          <p class="small text-light">
            <i class="fas fa-gem"></i> Joyas<br>
            <i class="fas fa-palette"></i> Cerámicas<br>
            <i class="fas fa-star"></i> Otros Accesorios
          </p>
        </div>
        <div class="col-md-4">
          <h6 class="text-warning" style="font-family: 'Playfair Display', serif; font-weight: 600;">Contacto</h6>
          <p class="small text-light">
            <i class="fab fa-whatsapp"></i> +56 9 9843 5160<br>
            <i class="fas fa-envelope"></i> cfmjoyas@gmail.com
          </p>
        </div>
      </div>
      <hr class="my-3 border-secondary">
      <p class="mb-0 text-light">&copy; 2025 CFM Joyas. Todos los derechos reservados.</p>
    </div>
  </footer>

  <!-- MODALES PARA FORMULARIO DE CONTACTO -->

  <!-- Modal de Éxito -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-success text-white border-0">
          <h5 class="modal-title" id="successModalLabel">
            <i class="fas fa-check-circle"></i> ¡Mensaje Enviado!
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center py-4">
          <div class="mb-3">
            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
          </div>
          <h4 class="text-success mb-3">¡Gracias por contactarnos!</h4>
          <p class="text-muted mb-3">
            Tu mensaje ha sido enviado exitosamente. Nos pondremos en contacto contigo a la brevedad.
          </p>
          <div class="alert alert-info border-0" style="background: rgba(255, 215, 0, 0.1);">
            <i class="fas fa-info-circle text-warning"></i>
            <strong>Tiempo de respuesta:</strong> 24-48 horas hábiles
          </div>
        </div>
        <div class="modal-footer border-0 justify-content-center">
          <button type="button" class="btn btn-warning px-4" data-bs-dismiss="modal">
            <i class="fas fa-gem"></i> Continuar explorando
          </button>
          <a href="https://wa.me/+56998435160" target="_blank" class="btn btn-success">
            <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Polyfill para navegadores antiguos -->
  <script src="https://polyfill.io/v3/polyfill.min.js?features=default%2CIntersectionObserver"></script>
  
  <script>
    // Detectar navegador y agregar clase al body
    (function() {
      var ua = navigator.userAgent.toLowerCase();
      var isChrome = /chrome/.test(ua) && !/edge/.test(ua);
      var isFirefox = /firefox/.test(ua);
      var isSafari = /safari/.test(ua) && !/chrome/.test(ua);
      var isEdge = /edge/.test(ua);
      
      if (isChrome) document.body.classList.add('browser-chrome');
      else if (isFirefox) document.body.classList.add('browser-firefox');
      else if (isSafari) document.body.classList.add('browser-safari');
      else if (isEdge) document.body.classList.add('browser-edge');
      
      // Fix para viewport en iOS
      if (isSafari && /iphone|ipad|ipod/.test(ua)) {
        document.body.classList.add('ios-device');
      }
    })();

    document.addEventListener('DOMContentLoaded', () => {
      // Inicializar carrusel con configuraciones personalizadas
      const carousel = new bootstrap.Carousel('#mainCarousel', {
        interval: 5000,
        wrap: true,
        touch: true
      });

      // Pausar en hover para mejor experiencia de usuario
      const carouselElement = document.getElementById('mainCarousel');
      
      carouselElement.addEventListener('mouseenter', () => {
        carousel.pause();
      });
      
      carouselElement.addEventListener('mouseleave', () => {
        carousel.cycle();
      });

      // Animación de entrada para productos
      document.querySelectorAll('.product-link').forEach((el, i) => {
        el.style.animationDelay = (i * 0.1) + 's';
        el.classList.add('visible');
      });
      
      // SCROLL AUTOMÁTICO A PRODUCTOS SI HAY FILTRO APLICADO
      <?php if ($categoria_filtro): ?>
        // Si hay una categoría seleccionada, hacer scroll a productos
        setTimeout(() => {
          document.getElementById('productos').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }, 100);
      <?php endif; ?>
      
      // Smooth scroll para navegación con offset para navbar
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
          e.preventDefault();
          const targetId = this.getAttribute('href');
          const target = document.querySelector(targetId);
          if (target) {
            // Calcular posición con offset para navbar (90px)
            const targetPosition = target.offsetTop - 90;
            window.scrollTo({
              top: targetPosition,
              behavior: 'smooth'
            });
          }
        });
      });
      
      // Efecto hover para el botón de admin
      const adminBtn = document.querySelector('.admin-btn');
      adminBtn.addEventListener('mouseenter', () => {
        adminBtn.innerHTML = '<i class="fas fa-unlock"></i>';
      });
      adminBtn.addEventListener('mouseleave', () => {
        adminBtn.innerHTML = '<i class="fas fa-cog"></i>';
      });

      // FIX PARA EL MENÚ HAMBURGUESA - CÓDIGO CORREGIDO
      const navbarToggler = document.querySelector('.navbar-toggler');
      const navbarCollapse = document.querySelector('#navbarNav');
      const navLinks = document.querySelectorAll('.nav-link');

      // Cerrar menú cuando se hace click en un enlace
      navLinks.forEach(link => {
        link.addEventListener('click', function() {
          if (navbarCollapse.classList.contains('show')) {
            bootstrap.Collapse.getInstance(navbarCollapse).hide();
          }
        });
      });

      // Cerrar menú cuando se hace click fuera
      document.addEventListener('click', function(event) {
        const isClickInside = navbarCollapse.contains(event.target) || navbarToggler.contains(event.target);
        
        if (!isClickInside && navbarCollapse.classList.contains('show')) {
          bootstrap.Collapse.getInstance(navbarCollapse).hide();
        }
      });

      // SCRIPT PARA VALIDACIONES DEL FORMULARIO DE CONTACTO
      const form = document.getElementById('contactForm');
      const nameInput = document.getElementById('name');
      const emailInput = document.getElementById('email');
      const messageInput = document.getElementById('message');
      const submitBtn = document.getElementById('submitBtn');
      const charCount = document.getElementById('charCount');
      const loadingSpinner = document.getElementById('loadingSpinner');

      // Solo ejecutar si existen los elementos
      if (form && nameInput && emailInput && messageInput) {
        
        // Contador de caracteres
        messageInput.addEventListener('input', function() {
          const count = this.value.length;
          charCount.textContent = count;
          
          if (count > 450) {
            charCount.className = 'text-danger';
          } else if (count > 400) {
            charCount.className = 'text-warning';
          } else {
            charCount.className = 'text-muted';
          }
        });

        // Validación en tiempo real
        function validateField(field) {
          const value = field.value.trim();
          let isValid = true;

          // Limpiar clases previas
          field.classList.remove('is-valid', 'is-invalid');

          if (field.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isValid = emailRegex.test(value);
          } else if (field.hasAttribute('minlength')) {
            isValid = value.length >= parseInt(field.getAttribute('minlength'));
          } else {
            isValid = value.length > 0;
          }

          // Aplicar clase de validación
          if (value.length > 0) {
            field.classList.add(isValid ? 'is-valid' : 'is-invalid');
          }

          return isValid;
        }

        // Eventos de validación
        [nameInput, emailInput, messageInput].forEach(field => {
          field.addEventListener('blur', () => validateField(field));
          field.addEventListener('input', () => {
            if (field.classList.contains('is-invalid')) {
              validateField(field);
            }
          });
        });

        // Envío del formulario
        form.addEventListener('submit', function(e) {
          e.preventDefault();

          // Validar todos los campos
          const nameValid = validateField(nameInput);
          const emailValid = validateField(emailInput);
          const messageValid = validateField(messageInput);

          if (!nameValid || !emailValid || !messageValid) {
            // Enfocar el primer campo inválido
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
              firstInvalid.focus();
              firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
          }

          // Mostrar loading
          submitBtn.classList.add('loading');
          submitBtn.disabled = true;
          if (loadingSpinner) {
            loadingSpinner.style.display = 'block';
          }

          // Envío real
          const formData = new FormData(form);

          fetch('send_email.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            // Ocultar loading
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            if (loadingSpinner) {
              loadingSpinner.style.display = 'none';
            }

            if (data.includes('éxito') || data.includes('enviado')) {
              // Mostrar modal de éxito
              const successModal = new bootstrap.Modal(document.getElementById('successModal'));
              successModal.show();
              
              // Limpiar formulario
              form.reset();
              [nameInput, emailInput, messageInput].forEach(field => {
                field.classList.remove('is-valid', 'is-invalid');
              });
              charCount.textContent = '0';
            } else {
              // Mostrar modal de error
              document.getElementById('errorMessage').textContent = data;
              const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
              errorModal.show();
            }
          })
          .catch(error => {
            // Ocultar loading y mostrar error
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            if (loadingSpinner) {
              loadingSpinner.style.display = 'none';
            }
            
            document.getElementById('errorMessage').textContent = 'Error de conexión. Verifica tu internet e intenta nuevamente.';
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
          });
        });
      }
    });
  </script>
</body>
</html>

  <!-- Modal de Error -->
  <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-danger text-white border-0">
          <h5 class="modal-title" id="errorModalLabel">
            <i class="fas fa-exclamation-triangle"></i> Error al Enviar
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center py-4">
          <div class="mb-3">
            <i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i>
          </div>
          <h4 class="text-danger mb-3">Oops, algo salió mal</h4>
          <p class="text-muted mb-3" id="errorMessage">
            Hubo un problema al enviar tu mensaje. Por favor intenta nuevamente.
          </p>
          <div class="alert alert-warning border-0">
            <i class="fas fa-lightbulb text-warning"></i>
            <strong>Alternativa:</strong> Puedes contactarnos directamente por WhatsApp
          </div>
        </div>
        <div class="modal-footer border-0 justify-content-center">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-redo"></i> Intentar nuevamente
          </button>
     <a href="https://wa.me/+56998435160" target="_blank" class="btn btn-success">
            <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Polyfill para navegadores antiguos -->
  <script src="https://polyfill.io/v3/polyfill.min.js?features=default%2CIntersectionObserver"></script>
  
  <script>
    // Detectar navegador y agregar clase al body
    (function() {
      var ua = navigator.userAgent.toLowerCase();
      var isChrome = /chrome/.test(ua) && !/edge/.test(ua);
      var isFirefox = /firefox/.test(ua);
      var isSafari = /safari/.test(ua) && !/chrome/.test(ua);
      var isEdge = /edge/.test(ua);
      
      if (isChrome) document.body.classList.add('browser-chrome');
      else if (isFirefox) document.body.classList.add('browser-firefox');
      else if (isSafari) document.body.classList.add('browser-safari');
      else if (isEdge) document.body.classList.add('browser-edge');
      
      // Fix para viewport en iOS
      if (isSafari && /iphone|ipad|ipod/.test(ua)) {
        document.body.classList.add('ios-device');
      }
    })();

    document.addEventListener('DOMContentLoaded', () => {
      // Inicializar carrusel con configuraciones personalizadas
      const carousel = new bootstrap.Carousel('#mainCarousel', {
        interval: 5000,
        wrap: true,
        touch: true
      });

      // Pausar en hover para mejor experiencia de usuario
      const carouselElement = document.getElementById('mainCarousel');
      
      carouselElement.addEventListener('mouseenter', () => {
        carousel.pause();
      });
      
      carouselElement.addEventListener('mouseleave', () => {
        carousel.cycle();
      });

      // Animación de entrada para productos
      document.querySelectorAll('.product-link').forEach((el, i) => {
        el.style.animationDelay = (i * 0.1) + 's';
        el.classList.add('visible');
      });
      
      // SCROLL AUTOMÁTICO A PRODUCTOS SI HAY FILTRO APLICADO
      <?php if ($categoria_filtro): ?>
        // Si hay una categoría seleccionada, hacer scroll a productos
        setTimeout(() => {
          document.getElementById('productos').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }, 100);
      <?php endif; ?>
      
      // Smooth scroll para navegación con offset para navbar
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
          e.preventDefault();
          const targetId = this.getAttribute('href');
          const target = document.querySelector(targetId);
          if (target) {
            // Calcular posición con offset para navbar (90px)
            const targetPosition = target.offsetTop - 90;
            window.scrollTo({
              top: targetPosition,
              behavior: 'smooth'
            });
          }
        });
      });
      
      // Efecto hover para el botón de admin
      const adminBtn = document.querySelector('.admin-btn');
      adminBtn.addEventListener('mouseenter', () => {
        adminBtn.innerHTML = '<i class="fas fa-unlock"></i>';
      });
      adminBtn.addEventListener('mouseleave', () => {
        adminBtn.innerHTML = '<i class="fas fa-cog"></i>';
      });

     document.addEventListener('DOMContentLoaded', function() {
  // Cerrar menú móvil al hacer click en un enlace
  const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
  const navbarCollapse = document.querySelector('.navbar-collapse');
  
  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      if (navbarCollapse.classList.contains('show')) {
        const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
        bsCollapse.hide();
      }
    });
  });
});
 const links = document.querySelectorAll('.nav-link');
  const navbar = document.getElementById('navbarNav');

  links.forEach(link => {
    link.addEventListener('click', () => {
      const bsCollapse = new bootstrap.Collapse(navbar, { toggle: false });
      bsCollapse.hide();
    });
  });

      // SCRIPT PARA VALIDACIONES DEL FORMULARIO DE CONTACTO
      const form = document.getElementById('contactForm');
      const nameInput = document.getElementById('name');
      const emailInput = document.getElementById('email');
      const messageInput = document.getElementById('message');
      const submitBtn = document.getElementById('submitBtn');
      const charCount = document.getElementById('charCount');
      const loadingSpinner = document.getElementById('loadingSpinner');

      // Solo ejecutar si existen los elementos
      if (form && nameInput && emailInput && messageInput) {
        
        // Contador de caracteres
        messageInput.addEventListener('input', function() {
          const count = this.value.length;
          charCount.textContent = count;
          
          if (count > 450) {
            charCount.className = 'text-danger';
          } else if (count > 400) {
            charCount.className = 'text-warning';
          } else {
            charCount.className = 'text-muted';
          }
        });

        // Validación en tiempo real
        function validateField(field) {
          const value = field.value.trim();
          let isValid = true;

          // Limpiar clases previas
          field.classList.remove('is-valid', 'is-invalid');

          if (field.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isValid = emailRegex.test(value);
          } else if (field.hasAttribute('minlength')) {
            isValid = value.length >= parseInt(field.getAttribute('minlength'));
          } else {
            isValid = value.length > 0;
          }

          // Aplicar clase de validación
          if (value.length > 0) {
            field.classList.add(isValid ? 'is-valid' : 'is-invalid');
          }

          return isValid;
        }

        // Eventos de validación
        [nameInput, emailInput, messageInput].forEach(field => {
          field.addEventListener('blur', () => validateField(field));
          field.addEventListener('input', () => {
            if (field.classList.contains('is-invalid')) {
              validateField(field);
            }
          });
        });

        // Envío del formulario
        form.addEventListener('submit', function(e) {
          e.preventDefault();

          // Validar todos los campos
          const nameValid = validateField(nameInput);
          const emailValid = validateField(emailInput);
          const messageValid = validateField(messageInput);

          if (!nameValid || !emailValid || !messageValid) {
            // Enfocar el primer campo inválido
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
              firstInvalid.focus();
              firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
          }

          // Mostrar loading
          submitBtn.classList.add('loading');
          submitBtn.disabled = true;
          if (loadingSpinner) {
            loadingSpinner.style.display = 'block';
          }

          // Envío real
          const formData = new FormData(form);

          fetch('send_email.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            // Ocultar loading
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            if (loadingSpinner) {
              loadingSpinner.style.display = 'none';
            }

            if (data.includes('éxito') || data.includes('enviado')) {
              // Mostrar modal de éxito
              const successModal = new bootstrap.Modal(document.getElementById('successModal'));
              successModal.show();
              
              // Limpiar formulario
              form.reset();
              [nameInput, emailInput, messageInput].forEach(field => {
                field.classList.remove('is-valid', 'is-invalid');
              });
              charCount.textContent = '0';
            } else {
              // Mostrar modal de error
              document.getElementById('errorMessage').textContent = data;
              const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
              errorModal.show();
            }
          })
          .catch(error => {
            // Ocultar loading y mostrar error
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            if (loadingSpinner) {
              loadingSpinner.style.display = 'none';
            }
            
            document.getElementById('errorMessage').textContent = 'Error de conexión. Verifica tu internet e intenta nuevamente.';
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
          });
        });
      }
    });
  </script>
</body>
</html>