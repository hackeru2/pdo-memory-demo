FROM php:8.3-cli

RUN docker-php-ext-install pdo_mysql

COPY php.ini /usr/local/etc/php/conf.d/demo.ini
COPY src/ /app/src/
COPY public/ /app/public/

WORKDIR /app

# Render injects PORT; the built-in server is plenty for a single-user demo.
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public"]
