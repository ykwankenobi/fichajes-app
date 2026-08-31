# Fichajes App

Aplicación multiinstancia de control horario construida con Laravel 12 y Filament 5.
El repositorio contiene un único código común. Cada empresa mantiene fuera de Git
su `.env`, base de datos, logo, archivos subidos y configuración de CloudPanel.

## Requisitos

- PHP 8.2 o superior con las extensiones requeridas por Laravel y PDO MySQL.
- Composer 2.
- Node.js 22 y npm.
- MySQL o MariaDB.
- Nginx con la raíz del sitio apuntando a `public/`.

## Nueva instancia en CloudPanel

```bash
git clone https://github.com/ykwankenobi/fichajes-app.git htdocs/dominio
cd htdocs/dominio
cp .env.example .env
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Completa las credenciales y el dominio en `.env` antes de ejecutar las migraciones.
El propietario PHP-FPM del sitio debe poder escribir en `storage/` y
`bootstrap/cache/`.

## Personalización por empresa

```dotenv
APP_NAME="Nombre de la empresa"
APP_URL=https://fichajes.empresa.example
BRAND_PRIMARY_COLOR=red
BRAND_LOGO=images/logo.png
```

Los colores admitidos son `red`, `sky`, `blue`, `green`, `amber` e `indigo`.
Si `BRAND_LOGO` queda vacío o el archivo no existe, se muestra `APP_NAME`.
El logo es específico de cada servidor y no debe añadirse al repositorio.

Después de cambiar `.env`:

```bash
php artisan optimize:clear
php artisan optimize
```

## Actualización de una instancia

Haz primero una copia de la base de datos y de `storage/app`. Después:

```bash
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

## Desarrollo y pruebas

```bash
composer install
npm ci
composer test
npm run build
```

Nunca deben subirse `.env`, bases de datos, copias de seguridad, logs, `vendor`,
`node_modules`, archivos de usuarios ni certificados.
