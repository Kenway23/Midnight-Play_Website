FROM php:8.2-apache

# Disable MPM lain, aktifkan prefork
RUN a2dismod mpm_event mpm_worker \
    && a2enmod mpm_prefork

# Install ekstensi PHP MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project ke Apache
COPY . /var/www/html/

# Permission
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
