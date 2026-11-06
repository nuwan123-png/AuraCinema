# syntax=docker/dockerfile:1

# ---------- 1) Front-end assets (Tailwind + Vite) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---------- 2) Runtime ----------
FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx ffmpeg libpng libjpeg-turbo libwebp libzip oniguruma \
    && apk add --no-cache --virtual .build-deps libpng-dev libjpeg-turbo-dev libwebp-dev libzip-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif bcmath gd zip opcache \
    && apk del .build-deps

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# Dependencies first so this layer is cached until composer.lock changes.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && rm -rf /root/.composer /root/.cache

# Built CSS/JS from stage 1 (public/build is not in git).
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p /run/nginx storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

# Fail the BUILD (not the deploy) if the web server or PHP-FPM config is invalid.
RUN nginx -t && php-fpm -t

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s CMD wget -qO- http://127.0.0.1/up >/dev/null || exit 1
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]