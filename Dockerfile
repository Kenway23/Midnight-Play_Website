FROM php:8.2-cli

# Install ekstensi database
RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /app
COPY . .

# Railway pakai PORT dari env
CMD ["sh", "-c", "php -S 0.0.0.0:$PORT -t ."]
