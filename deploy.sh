#!/usr/bin/env bash
# ==============================================================================
# Script de Despliegue Automatizado para PichoDescargas en VPS con aaPanel
# ==============================================================================
set -e

echo "🚀 Iniciando despliegue de PichoDescargas..."

# 1. Permitir ejecución de Composer como root en aaPanel sin bloquear plugins
export COMPOSER_ALLOW_SUPERUSER=1

# 2. Asegurar que los directorios críticos existan y tengan permisos de escritura
echo "📁 Preparando directorios y permisos de almacenamiento y caché..."
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/framework/testing storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache
chown -R www:www storage bootstrap/cache 2>/dev/null || true

# 3. Modo de mantenimiento temporal (solo si vendor ya está instalado)
if [ -f "vendor/autoload.php" ]; then
    php artisan down --render="errors::503" --retry=60 || true
fi

# 4. Obtener los últimos cambios desde la rama main de GitHub
echo "📦 Descargando últimos cambios desde GitHub..."
git checkout -- deploy.sh 2>/dev/null || true
git pull origin main

# 5. Asegurar nuevamente permisos tras el pull
chmod -R 777 storage bootstrap/cache
chown -R www:www storage bootstrap/cache 2>/dev/null || true

# 6. Instalar dependencias de PHP optimizadas para producción
echo "⚡ Instalando dependencias de Composer..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 7. Asegurar existencia del archivo .env y su APP_KEY
if [ ! -f ".env" ]; then
    echo "⚠️ Archivo .env no encontrado. Creando a partir de .env.production.example..."
    cp .env.production.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "🔑 Generando APP_KEY en .env..."
    php artisan key:generate --force
fi

# 8. Ejecutar migraciones de base de datos
echo "🗄️ Ejecutando migraciones de base de datos..."
php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force || true
php artisan admin:setup || true

# 9. Recompilar caché optimizada de Laravel
echo "🧹 Limpiando y optimizando cachés de Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 10. Asegurar enlace simbólico de almacenamiento público
php artisan storage:link || true

# 11. Ajustar permisos finales para el usuario del servidor web de aaPanel (www:www)
echo "🔒 Ajustando permisos finales..."
chown -R www:www storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache

# 12. Reiniciar trabajadores de colas (Supervisor)
echo "🔄 Reiniciando workers de colas..."
php artisan queue:restart || true

# 13. Desactivar modo de mantenimiento
php artisan up

echo "✅ ¡Despliegue completado con éxito en aaPanel!"
