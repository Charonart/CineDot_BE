FROM php:8.2-apache

# 1. Cài đặt các thư viện hệ thống cần thiết
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# 2. Bật mod_rewrite của Apache (cần thiết cho Laravel routing)
RUN a2enmod rewrite

# 3. Đổi thư mục gốc (DocumentRoot) của Apache trỏ vào thư mục /public của Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Thiết lập thư mục làm việc
WORKDIR /var/www/html

# 6. Copy TOÀN BỘ source code vào trong container
COPY . /var/www/html

# 7. Cài đặt các thư viện PHP bằng Composer (chế độ production)
RUN composer install --no-dev --optimize-autoloader

# 8. Phân quyền cho Apache user (www-data) có quyền ghi vào storage và cache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# 9. Copy cấu hình Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 10. Lệnh mặc định khi chạy container: khởi động Supervisor (sẽ bật cả Apache và Worker)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
