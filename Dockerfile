FROM artburkart/nginx-php-fpm-phalcon:latest

COPY /apacs /var/www/html
RUN cd /var/www/html && php composer.phar install
COPY default.conf /etc/nginx/sites-enabled/default.conf
COPY php.ini /etc/php5/fpm/php.ini

EXPOSE 80