#!/usr/bin/env bash
# ==============================================================================
# Script de Despliegue Automatizado para PichoDescargas en VPS con aaPanel
# ==============================================================================
set -e

echo "🚀 Iniciando despliegue de PichoDescargas..."

# 1. Modo de mantenimiento temporal durante la actualización
php artisan down --render="errors::503" --retry=60 || true

# 2. Obtener los últimos cambios desde la rama main de GitHub
echo "📦 Descargando últimos cambios desde GitHub..."
git pull origin main

# 3. Instalar dependencias de PHP optimizadas para producción
echo "⚡ Instalando dependencias de Composer..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 4. Ejecutar migraciones de base de datos
echo "🗄️ Ejecutando migraciones de base de datos..."
php artisan migrate --force

# 5. Recompilar caché optimizada de Laravel
echo "🧹 Limpiando y optimizando cachés de Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Asegurar enlace simbólico de almacenamiento público
php artisan storage:link || true

# 7. Ajustar permisos para el usuario del servidor web de aaPanel (www:www)
echo "🔒 Ajustando permisos de directorios..."
chown -R www:www storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# 8. Reiniciar trabajadores de colas (Supervisor)
echo "🔄 Reiniciando workers de colas..."
php artisan queue:restart || true

# 9. Desactivar modo de mantenimiento
php artisan up

echo "✅ ¡Despliegue completado con éxito en aaPanel!"
