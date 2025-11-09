#!/bin/bash
# ================================================
# SCRIPT DE LIMPIEZA DE SEGURIDAD - CFM JOYAS
# ================================================

echo "🔒 Iniciando limpieza de seguridad del repositorio..."
echo ""

# Verificar que estamos en un repositorio git
if [ ! -d .git ]; then
    echo "❌ Error: No estás en un repositorio Git"
    exit 1
fi

# 1. Eliminar error_log del índice (pero mantenerlo local)
echo "📝 Paso 1: Removiendo error_log del repositorio..."
git rm --cached error_log 2>/dev/null || echo "  - error_log ya no está en el índice"

# 2. Mejorar .gitignore
echo "📝 Paso 2: Actualizando .gitignore..."
cat > .gitignore << 'EOF'
# Environment Variables
.env
.env.local
.env.production

# Dependencies
vendor/

# Logs - CRÍTICO: Nunca subir logs
error_log
*.log
logs/

# Temporary files
tmp/sessions/
tmp/*.tmp
tmp/

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Backup files
*.bak
*.backup

# Archivos sensibles adicionales
composer.lock
phpunit.xml
.phpunit.result.cache
EOF

# 3. Crear archivo README de seguridad
echo "📝 Paso 3: Creando SECURITY.md..."
cat > SECURITY.md << 'EOF'
# 🔒 Seguridad - CFM Joyas

## Variables de Entorno Requeridas

Este proyecto requiere un archivo `.env` con las siguientes variables:

```env
# Database Configuration
DB_HOST=localhost
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
DB_DATABASE=your_database_name

# Access Codes (comma-separated)
# IMPORTANTE: Cambia estos códigos después de clonar
ACCESS_CODES=CODE1,CODE2,CODE3

# Security Settings
# IMPORTANTE: Genera una clave única y segura
AUTH_SECRET_KEY=your_unique_secret_key_here

# Email Configuration (Optional)
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_PORT=587
```

## ⚠️ IMPORTANTE - Después de Clonar

1. **Copia el archivo de ejemplo:**
   ```bash
   cp .env.example .env
   ```

2. **CAMBIA INMEDIATAMENTE:**
   - Todos los códigos de acceso (ACCESS_CODES)
   - La clave secreta (AUTH_SECRET_KEY)
   - Las credenciales de base de datos
   - Las credenciales de email

3. **Nunca subas:**
   - Archivos `.env` con datos reales
   - Logs (`error_log`, `*.log`)
   - Backups de base de datos con datos reales

## Reportar Vulnerabilidades

Si encuentras una vulnerabilidad de seguridad, contacta directamente a:
- Email: cfmjoyas@gmail.com
EOF

# 4. Actualizar README con advertencia de seguridad
echo "📝 Paso 4: Actualizando README.md con nota de seguridad..."
if ! grep -q "IMPORTANTE: SEGURIDAD" README.md; then
    # Crear un backup del README
    cp README.md README.md.backup
    
    # Agregar advertencia de seguridad al inicio (después del título)
    awk '/^# / && !done {print; print ""; print "## ⚠️ IMPORTANTE: SEGURIDAD"; print ""; print "**ANTES de usar este proyecto:**"; print "1. Lee el archivo [SECURITY.md](SECURITY.md)"; print "2. Cambia TODOS los códigos de acceso"; print "3. Genera nuevas claves secretas"; print "4. Nunca subas archivos .env o logs al repositorio"; print ""; done=1; next} 1' README.md.backup > README.md
    rm README.md.backup
fi

# 5. Mostrar el estado
echo ""
echo "📊 Estado de los cambios:"
git status

echo ""
echo "✅ Limpieza completada!"
echo ""
echo "📋 Próximos pasos:"
echo "   1. Revisa los cambios con: git diff"
echo "   2. Haz commit: git add -A && git commit -m 'Security: Remove sensitive files and improve .gitignore'"
echo "   3. Sube cambios: git push origin main"
echo "   4. 🔑 CRÍTICO: Cambia el código de acceso 'CFM2025' en tu servidor de producción"
echo ""
