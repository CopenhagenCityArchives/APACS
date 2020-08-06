FROM php:7.4-fpm-alpine

# install make tools, clone phalcon, build it and remove tmp folders and packages



# enable pdo_mysql extension
RUN docker-php-ext-install pdo pdo_mysql

# download, build and install Phalcon
RUN \
    apk add git autoconf file g++ gcc libc-dev make pkgconf re2c libtool \
    check check-dev cyrus-sasl-dev libsodium-dev libssh2-dev imagemagick-dev yaml-dev --no-cache && \
    cd ~ && git clone https://github.com/phalcon/cphalcon.git && \
    cd ~/cphalcon && git checkout v3.4.2 && \
    cd ~/cphalcon/build && ./install && \
    docker-php-ext-enable phalcon && \
    rm -rf ~/cphalcon && \
    apk del git autoconf make m4 perl g++ gcc \
    musl-dev libc-dev check-dev cyrus-sasl-dev libsodium-dev libssh2-dev yaml-dev openssl-dev imagemagick-dev zlib-dev

# add libpng to enable image manipulation with PHP
RUN apk add --no-cache libpng libpng-dev libjpeg-turbo libjpeg-turbo-dev freetype freetype-dev
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd
RUN apk del libpng-dev libjpeg-turbo-dev freetype-dev


# Install xdebug (unsupported at the moment)
#RUN pecl install xdebug-2.5.5 && docker-php-ext-enable xdebug
#RUN yes | pecl install xdebug \
#    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
#    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
#    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /code

#ENV MAX_EXECUTION_TIME=90

# Set Max execution time
#RUN sed -i "s@^max_execution_time = .*@max_execution_time = $MAX_EXECUTION_TIME@" /etc/php5/apache2/php.ini
#RUN sed -i "s@^output_buffering = .*@output_buffering = Off@" /etc/php5/apache2/php.ini

#RUN echo 'xdebug.remote_enable = 1 \n\
#xdebug.remote_host = 192.168.10.124 \n\
#xdebug.remote_port = 9000 \n\
#xdebug.remote_autostart=1 \n\
#xdebug.remote_handler = dbgp \n\
#xdebug.remote_mode = req \n\
#xdebug.profiler_enable=0 \n\
#xdebug.remote_log="/tmp/xdebug.log" \n\
#xdebug.profiler_enable_trigger=1 \n\
#xdebug.idekey="xdebug" \n\
#xdebug.profiler_output_dir = "/var/www/html"' >> /etc/php5/apache2/php.ini
