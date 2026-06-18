FROM php:8.2-cli

# Cài đặt các thư viện hệ thống cần thiết cho PostgreSQL và các công cụ khác
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis

# Cài đặt Composer trực tiếp từ image chính thức
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Chạy lệnh phục vụ mặc định (cho môi trường dev)
CMD php artisan optimize:clear && php artisan serve --host=0.0.0.0 --port=8000
