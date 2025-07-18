-- ================================================
-- SQL CORREGIDO PARA HOSTGATOR - CFM JOYAS
-- Solo crear tablas (sin CREATE DATABASE)
-- ================================================

-- ================================================
-- TABLA DE USUARIOS (con seguridad mejorada)
-- ================================================
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) UNIQUE NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  codigo_acceso VARCHAR(20) NOT NULL,
  activo BOOLEAN DEFAULT TRUE,
  ultimo_acceso TIMESTAMP NULL,
  intentos_fallidos INT DEFAULT 0,
  bloqueado_hasta TIMESTAMP NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ================================================
-- TABLA DE CATEGORÍAS (con las nuevas categorías)
-- ================================================
CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) UNIQUE NOT NULL,
  descripcion TEXT,
  activa BOOLEAN DEFAULT TRUE,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ================================================
-- TABLA DE PRODUCTOS (con precio y categoría)
-- ================================================
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  precio DECIMAL(10,2) DEFAULT 0,
  categoria VARCHAR(50) DEFAULT 'joyas',
  instagram VARCHAR(255) NOT NULL,
  imagen VARCHAR(255) NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_categoria (categoria),
  INDEX idx_fecha (fecha)
);

-- ================================================
-- INSERTAR TODAS LAS CATEGORÍAS (originales + nuevas)
-- ================================================
INSERT INTO categorias (nombre, descripcion) VALUES 
-- Categorías ORIGINALES (las que ya tenías)
('joyas', 'Anillos, collares, pulseras, aretes y accesorios de joyería'),
('ceramicas', 'Productos de cerámica artesanal y decorativa'),
('otros', 'Otros accesorios y productos especiales'),

-- Categorías NUEVAS (específicas para joyas)
('collares', 'Collares y cadenas con diferentes estilos y materiales'),
('pulseras', 'Pulseras artesanales y con diseños únicos'),
('aretes', 'Aretes de diferentes tamaños y estilos'),
('anillos', 'Anillos con piedras y diseños especiales');

-- ================================================
-- MENSAJE DE CONFIRMACIÓN
-- ================================================
SELECT 'CFM JOYAS - Tablas creadas exitosamente' AS mensaje;
SELECT 'Recuerda crear tu usuario admin en /admin/register.php' AS recordatorio;