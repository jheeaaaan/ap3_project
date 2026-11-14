FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/^expose_php = On/expose_php = Off/' "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html
COPY . /var/www/html/
COPY start.sh /usr/local/bin/start-ap3

RUN chmod 0755 /usr/local/bin/start-ap3 \
    && apachectl -t

EXPOSE 10000
CMD ["/usr/local/bin/start-ap3"]
