FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive

# Installer PHP 7.4 et nginx sans utiliser de dépôt externe
RUN apt-get update && apt-get install -y --no-install-recommends \
    php \
    php-common \
    php-pgsql \
    php-fpm \
    nginx \
    curl \
    ca-certificates \
    supervisor && \
    rm -rf /var/lib/apt/lists/*

# Préparer PHP-FPM
RUN mkdir -p /run/php && chown www-data:www-data /run/php

# Copier les fichiers de config
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY config/php/php.ini /etc/php/7.4/fpm/php.ini
COPY config/nginx/default.conf /etc/nginx/nginx.conf

# Définir les permissions du répertoire web
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Exposer le port
EXPOSE 80

# Lancer supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
