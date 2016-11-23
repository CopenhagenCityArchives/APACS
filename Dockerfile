FROM artburkart/nginx-php-fpm-phalcon:latest

COPY /apacs /var/www/html
COPY /apacs/app/config/config.php /var/www/html/app/config/config.php
RUN cd /var/www/html && php composer.phar install
COPY default.conf /etc/nginx/sites-enabled/default.conf

EXPOSE 80