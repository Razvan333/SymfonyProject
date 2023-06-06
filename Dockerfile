FROM ubuntu:latest

FROM php:7.4-fpm

WORKDIR /var/www/html

RUN apt-get update && \
    apt-get install -y \
        zip \
        unzip \
        curl \
        default-mysql-client \
        && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql

CMD ["php-fpm"]

RUN apt-get update && apt-get install -y rabbitmq-server

RUN apt-get update && apt-get install -y redis-server

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

CMD service mysql start && php-fpm
