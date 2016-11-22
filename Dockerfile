FROM artburkart/nginx-php-fpm-phalcon:latest

#RUN apt-get update
#RUN apt-get upgrade nginx -y

#COPY apacs/app /var/www/html/app
#COPY apacs/public /var/www/html/public
#COPY apacs/vendor /var/www/html/vendor
COPY default.conf /etc/nginx/sites-enabled/default.conf

EXPOSE 80

#RUN service nginx restart

#VOLUME apacs /mnt/tmp