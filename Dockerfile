FROM php:8.2-apache

# Install MySQL extensions (INI YANG PENTING)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy semua file project ke apache
COPY . /var/www/html/

# Permission
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
