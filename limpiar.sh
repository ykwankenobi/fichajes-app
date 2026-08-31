#!/bin/bash

echo "Limpiando cachés Laravel..."

php artisan route:clear || exit 1
php artisan view:clear || exit 1
php artisan config:clear || exit 1
php artisan cache:clear || exit 1

echo "Compilando assets..."

npm run build || exit 1

echo "Todo completado correctamente."