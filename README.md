# PichoDescargas 🚀

> Portal moderno de descargas de aplicaciones, juegos para PC, apps Android y software verificado con soporte para enlaces directos (DDL), Magnet y Torrents de alta velocidad.

---

## 🌟 Características Principales

- **Arquitectura Laravel 11 & PHP 8.3:** Rendimiento optimizado, Eloquent ORM y soporte de transacciones y caché.
- **Frontend Futurista & Responsive:**
  - Sistema de temas adaptativo: **Modo Oscuro Cyberpunk HUD** y **Modo Claro Minimalista Acrílico** (`/impeccable`).
  - Navegación unificada y centrada en escritorio y cajón lateral fluido en móviles.
  - Tarjetas interactivas con esquinas HUD, micro-píldoras de estado (`TORRENT`, `DIRECT`, `UPDATED`) y telemetría de peso/versión.
- **Búsqueda Instantánea Spotlight:**
  - Modal flotante tipo macOS Spotlight (`Ctrl + K` / `Cmd + K`).
  - Búsqueda en tiempo real con debounce y registro analítico de términos.
- **Motor de Almacenamiento Dual & Localización de Torrents:**
  - Soporte de almacenamiento local y en la nube con **Cloudflare R2**.
  - Localizador/brandeador automático de torrents para descargas branded (`PichoDescargas.torrent`).
- **Scraper & Sincronización Automatizada:**
  - Importador y parser configurable para actualizaciones automáticas de catálogos y versiones.
  - Clasificación asistida por IA para metadatos y descripciones enriquecidas.
- **Panel Administrativo Completo:**
  - Gestión de publicaciones, categorías, reportes de enlaces caídos y configuración del sitio.

---

## 🛠 Requisitos del Entorno

- **PHP:** >= 8.3 con extensiones `pdo`, `mbstring`, `openssl`, `curl`, `json`
- **Composer:** >= 2.x
- **Node.js:** >= 20.x y **NPM**
- **Servidor Web:** Apache / Nginx / Caddy (o servidor local tipo MAMP / Herd / Sail)
- **Base de Datos:** MySQL / MariaDB o SQLite

---

## 🚀 Instalación y Puesta en Marcha

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/tu-usuario/pichodescargas.git
   cd pichodescargas
   ```

2. **Instalar dependencias de PHP:**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```
   *(o `composer install` para entorno de desarrollo)*

3. **Configurar el entorno:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Configura tus credenciales de base de datos y Cloudflare R2 (si aplica) en `.env`.

4. **Ejecutar migraciones y seeders:**
   ```bash
   php artisan migrate --force
   php artisan db:seed
   ```

5. **Instalar y compilar assets del frontend:**
   ```bash
   npm install
   npm run build
   ```

6. **Crear enlace simbólico de almacenamiento:**
   ```bash
   php artisan storage:link
   ```

7. **Iniciar servidor local de pruebas (opcional):**
   ```bash
   php artisan serve
   ```

---

## 🧪 Pruebas Unitarias y Estilo de Código

El proyecto cuenta con una suite completa de pruebas automatizadas:

```bash
# Ejecutar toda la suite de pruebas (104 tests)
php artisan test

# Formatear el código con Laravel Pint
vendor/bin/pint --format agent
```

---

## 🔒 Despliegue en Producción

Antes de desplegar a producción:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

---

## 📄 Licencia

Este proyecto está bajo la licencia [MIT](LICENSE).
