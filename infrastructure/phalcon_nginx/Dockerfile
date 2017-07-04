FROM artburkart/nginx-php-fpm-phalcon:latest

COPY /apacs /var/www/html
RUN cd /var/www/html && php composer.phar install
COPY /infrastructure/phalcon_nginx/default.conf /etc/nginx/sites-enabled/default.conf
COPY /infrastructure/phalcon_nginx/php.ini /etc/php5/fpm/php.ini

EXPOSE 80
