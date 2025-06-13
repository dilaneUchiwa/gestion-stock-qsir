FROM ubuntu:20.04

# Install PHP 8.2 and nginx
RUN apt-get update && \
    apt-get install -y software-properties-common && \
    add-apt-repository ppa:ondrej/php && \
    add-apt-repository ppa:libreoffice/ppa &&\
    apt-get update && \
    apt-get install -y --no-install-recommends \
        nginx \
        php8.2 \
        php8.2-common \
        php8.2-pgsql && \

    rm -rf /var/lib/apt/lists/*
    

# Create necessary directories to php8.2-fpm
RUN mkdir -p /run/php && chown www-data:www-data /run/php

# Copy configuration files
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY config/php/php.ini /etc/php/8.2/fpm/php.ini
COPY config/nginx/default.conf /etc/nginx/nginx.conf

# Set permissions for the web directory
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start supervisord to launch nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
