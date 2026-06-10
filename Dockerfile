FROM php:8.2-apache

# Web OneCheck usa a API REST — não conecta direto ao banco.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/start-web.sh /usr/local/bin/start-web.sh
RUN sed -i 's/\r$//' /usr/local/bin/start-web.sh \
    && chmod +x /usr/local/bin/start-web.sh

WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

ENV ONECHECK_BASE_PATH=
ENV ONECHECK_API_URL=https://onecheck-api.onrender.com/api/v1

EXPOSE 80
CMD ["/bin/bash", "/usr/local/bin/start-web.sh"]
