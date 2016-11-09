FROM szeist/phalcon-apache2

RUN apt-get update
RUN apt-get install phpunit --assume-yes
RUN pecl install xdebug

COPY php.ini /etc/php5/apache2/
RUN service apache2 restart