FROM php:8.4-fpm-alpine

# System dependencies ස්ථාපනය කිරීම
RUN apk add --no-cache \
    nginx \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    libzip-dev

# PHP Extensions ස්ථාපනය කිරීම
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Composer ස්ථාපනය කිරීම
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Project files copy කිරීම
COPY . .

# Composer dependencies ස්ථාපනය කිරීම
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permissions සැකසීම
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx configuration සකස් කිරීම
RUN mkdir -p /run/nginx
COPY <<EOF /etc/nginx/http.d/default.conf
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    root /var/www/html/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

EXPOSE 80

# PHP-FPM සහ Nginx එකවර ධාවනය කිරීම
CMD php-fpm -D && nginx -g "daemon off;"