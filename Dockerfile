FROM php:5.6-apache
COPY apacs/ /var/www/html/
COPY apacs/app/config/php.ini /usr/local/etc/php/