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

# PHP Upload limits සැකසීම (100MB videos සඳහා)
RUN echo "upload_max_filesize = 120M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 120M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Composer ස්ථාපනය කිරීම
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Project files copy කිරීම
COPY . .

# Composer dependencies ස්ථාපනය කිරීම
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permissions සැකසීම
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx config එක සැකසීම (Routing + 120MB Body Size)
RUN mkdir -p /run/nginx && \
    rm -f /etc/nginx/http.d/default.conf && \
    echo 'server {' > /etc/nginx/http.d/default.conf && \
    echo '    listen 80 default_server;' >> /etc/nginx/http.d/default.conf && \
    echo '    listen [::]:80 default_server;' >> /etc/nginx/http.d/default.conf && \
    echo '    client_max_body_size 120M;' >> /etc/nginx/http.d/default.conf && \
    echo '    root /var/www/html/public;' >> /etc/nginx/http.d/default.conf && \
    echo '    index index.php index.html;' >> /etc/nginx/http.d/default.conf && \
    echo '    location / {' >> /etc/nginx/http.d/default.conf && \
    echo '        try_files $uri $uri/ /index.php?$query_string;' >> /etc/nginx/http.d/default.conf && \
    echo '    }' >> /etc/nginx/http.d/default.conf && \
    echo '    location ~ \.php$ {' >> /etc/nginx/http.d/default.conf && \
    echo '        fastcgi_pass 127.0.0.1:9000;' >> /etc/nginx/http.d/default.conf && \
    echo '        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;' >> /etc/nginx/http.d/default.conf && \
    echo '        fastcgi_read_timeout 300;' >> /etc/nginx/http.d/default.conf && \
    echo '        include fastcgi_params;' >> /etc/nginx/http.d/default.conf && \
    echo '    }' >> /etc/nginx/http.d/default.conf && \
    echo '    location ~ /\.(?!well-known).* {' >> /etc/nginx/http.d/default.conf && \
    echo '        deny all;' >> /etc/nginx/http.d/default.conf && \
    echo '    }' >> /etc/nginx/http.d/default.conf && \
    echo '}' >> /etc/nginx/http.d/default.conf

EXPOSE 80

CMD php-fpm -D && nginx -g "daemon off;"