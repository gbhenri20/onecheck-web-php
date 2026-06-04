FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    postgresql-client \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql fileinfo \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/assets/uploads \
    && chmod -R 775 /var/www/html/assets/uploads

COPY docker/apache-onecheck.conf /etc/apache2/sites-available/000-default.conf
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY scripts/migrate-db.sh /usr/local/bin/migrate-db.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/migrate-db.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
