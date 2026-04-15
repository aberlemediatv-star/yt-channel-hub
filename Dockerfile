FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Damit Umgebungsvariablen aus docker-compose in PHP ankommen (ConfigMerge / $_ENV).
RUN sed -i 's/^;clear_env = no/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN if [ -f laravel/composer.json ]; then \
      cd laravel && composer install --no-dev --no-interaction --optimize-autoloader; \
    fi

RUN if [ -f composer.json ] && [ -d laravel ]; then \
      composer install --no-dev --no-interaction --optimize-autoloader; \
    fi

COPY docker/php/docker-entrypoint.sh /usr/local/bin/app-docker-entrypoint.sh
RUN chmod +x /usr/local/bin/app-docker-entrypoint.sh

ENTRYPOINT ["app-docker-entrypoint.sh"]
CMD ["php-fpm"]
