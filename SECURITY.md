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
