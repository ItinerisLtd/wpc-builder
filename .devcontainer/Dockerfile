FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    curl \
    mariadb-client \
    unzip \
  && docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
