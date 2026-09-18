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
# Opcional. Si queda vacío, se genera una ruta distinta por instancia.
KIOSK_PATH=
```

Los colores admitidos son `red`, `sky`, `blue`, `green`, `amber` e `indigo`.
Si `BRAND_LOGO` queda vacío o el archivo no existe, se muestra `APP_NAME`.
El logo es específico de cada servidor y no debe añadirse al repositorio.

## Terminal de fichajes con PIN

La portada de la instancia abre el inicio de sesión de usuario. El fichaje con
PIN está pensado para un terminal del centro y usa una ruta privada distinta en
cada instancia, derivada de `APP_KEY` salvo que se configure `KIOSK_PATH`.

Un administrador puede abrirla desde **Ajustes de empresa → Terminal de
fichajes**. En Android, abre el enlace desde Chrome y pulsa **Instalar app**.
La PWA se inicia directamente en el terminal, sin barra de direcciones.

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

## Despliegue centralizado

El flujo de GitHub Actions `.github/workflows/deploy.yml` actualiza todas las
instancias después de cada cambio en `main`. Solo descarga código y ejecuta las
migraciones propias de cada base de datos; no sincroniza `.env`, logos,
`storage/` ni archivos de usuarios.

Antes de activarlo, configura estos valores en **GitHub → Settings → Secrets
and variables → Actions**:

- Variable `DEPLOY_TARGETS`: lista JSON con los servidores y directorios de
  cada instancia. Ejemplo:

  ```json
  [
    {"name":"Elcos","host":"servidor.example.com","user":"deploy","path":"/home/deploy/htdocs/fichaje.elcos.es","port":22},
    {"name":"Empresa 2","host":"servidor.example.com","user":"deploy","path":"/home/deploy/htdocs/fichaje.empresa2.es","port":22}
  ]
  ```

- Secreto `DEPLOY_SSH_PRIVATE_KEY`: clave privada del usuario de despliegue.
- Secreto `DEPLOY_SSH_KNOWN_HOSTS`: salida de `ssh-keyscan` para los servidores.

El usuario SSH debe poder ejecutar los comandos de actualización en cada
directorio de la aplicación. Mientras `DEPLOY_TARGETS` esté vacío, el flujo no
intentará desplegar nada. Cuando se haya configurado, ejecuta manualmente
**Actions → Desplegar instancias → Run workflow** para desplegar el commit
actual; después se ejecutará en cada actualización de `main`.

## Desarrollo y pruebas

```bash
composer install
npm ci
composer test
npm run build
```

Nunca deben subirse `.env`, bases de datos, copias de seguridad, logs, `vendor`,
`node_modules`, archivos de usuarios ni certificados.
