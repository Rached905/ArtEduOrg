# ArtEduOrg – Symfony app image (PHP 8.2 + Apache)
FROM php:8.2-apache

# PHP extensions required by Symfony / Doctrine
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    unzip \
    git \
    && docker-php-ext-install -j$(nproc) intl pdo_mysql zip opcache \
    && docker-php-ext-enable opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Document root = Symfony public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# Dependencies first (better layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

# Finish Composer (scripts, autoload)
RUN composer dump-autoload --optimize --classmap-authoritative \
    && composer run-script auto-scripts 2>/dev/null || true

# Permissions for var and uploads
RUN chown -R www-data:www-data var public/uploads public/invoices 2>/dev/null || true \
    && chmod -R 775 var public/uploads public/invoices 2>/dev/null || true

# Entrypoint: wait for DB, run migrations, then start Apache
RUN cp /var/www/html/.docker/entrypoint.sh /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

EXPOSE 80
