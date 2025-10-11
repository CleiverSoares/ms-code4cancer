web: heroku-php-nginx -C nginx.conf public/
release: php artisan migrate --force && php artisan optimize:clear
