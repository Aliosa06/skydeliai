FROM php:8.2-apache

RUN apt-get update \
  && apt-get install -y libzip-dev zip unzip --no-install-recommends \
  && docker-php-ext-install mysqli pdo pdo_mysql \
  && a2enmod rewrite \
  && rm -rf /var/lib/apt/lists/*

COPY ./src /var/www/html